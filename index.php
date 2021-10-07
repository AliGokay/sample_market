<?php
    ini_set('max_execution_time', 0);

    require_once 'inc/config.php';
    require_once 'inc/db.php';
    require_once 'inc/data.php';
    require_once 'inc/logs.php';
    require_once 'inc/function.php';
    
    $database = new Database(DB_SERVER,DB_NAME,DB_USER,DB_PASSWORD);
    $database->connect();
    
    $i = 0;
    $reqTimeArray = array();
    do {
        $frsLstCtrl = new Logs($database,"1");
        $lastInsId = $frsLstCtrl->lastListId();

        $strtApiTime = date("Y-m-d H:i:s");
        $reqTimeArray = apiSendTimeControl($reqTimeArray);

        $idAdd = (($lastInsId>0) ? '&id='.$lastInsId : "" );
        $pageData = new PageData('https://sample-market.despatchcloud.uk/api/orders?api_key=$2y$10$bs86Ks22xreLnf.5SWueTOdjUkiLdwSn/6cBHIL2GbaCjCUKJvUx.'.$idAdd,1);
        
        $listData = $pageData->returnData();

        $log = new Logs($database,"1",'https://sample-market.despatchcloud.uk/api/orders?api_key=$2y$10$bs86Ks22xreLnf.5SWueTOdjUkiLdwSn/6cBHIL2GbaCjCUKJvUx.'.$idAdd,serialize($listData));
        $logListId = $log->insertLog();

        if(!empty($listData->data)) {
            $i++;

            $logLastId = 0;

            $newArray = array();
            foreach($listData->data as $key => $val){
                $newArray[$val->id] = $val;
            }

            asort($newArray); 

            foreach($newArray as $key => $val){

                $reqTimeArray = apiSendTimeControl($reqTimeArray);

                $pageDataDetail = new PageData('https://sample-market.despatchcloud.uk/api/orders/'.$val->id.'?api_key=$2y$10$bs86Ks22xreLnf.5SWueTOdjUkiLdwSn/6cBHIL2GbaCjCUKJvUx.',2);
                $detayData = $pageDataDetail->returnData();
               
                $logDetail = new Logs($database,"2",'https://sample-market.despatchcloud.uk/api/orders/'.$val->id.'?api_key=$2y$10$bs86Ks22xreLnf.5SWueTOdjUkiLdwSn/6cBHIL2GbaCjCUKJvUx.',serialize($detayData),$val->id,0);
                $logDetail->insertLog();
    
                $database->query('SELECT * FROM orders WHERE brand_id = :id LIMIT 1');
                $database->bind(':id', $val->id);
                $rowExist = $database->getSingleRow();
                if(!empty($rowExist)) {

                    $reqTimeArray = apiSendTimeControl($reqTimeArray);

                    $pageDataUpdate = new PageData('https://sample-market.despatchcloud.uk/api/orders/'.$val->id.'?api_key=$2y$10$bs86Ks22xreLnf.5SWueTOdjUkiLdwSn/6cBHIL2GbaCjCUKJvUx.',3);
                    $updateData = $pageDataUpdate->returnData();
    
                    $logUpdate = new Logs($database,"3",'https://sample-market.despatchcloud.uk/api/orders/'.$val->id.'?api_key=$2y$10$bs86Ks22xreLnf.5SWueTOdjUkiLdwSn/6cBHIL2GbaCjCUKJvUx.',serialize($updateData),$val->id,0);
                    $logUpdate->insertLog();
    
                    if($updateData->id == $val->id) {
                        $database->query('UPDATE orders SET type = :type WHERE brand_id = :id');
                        $database->bind(':type', $updateData->type);
                        $database->bind(':id', $updateData->id);
                        $database->execute();
                    }
                } else {

                    /**
                     * Yeni gelen orderların  sisteme ekleme işlemleri
                     */
                    $database->query('INSERT INTO orders (brand_id,payment_method,shipping_method,customer_id,company_id,type,billing_address_id,shipping_address_id,total,created_at,updated_at) VALUES 
                    (:id,:payment_method,:shipping_method,:customer_id,:company_id,:type,:billing_address_id,:shipping_address_id,:total,:created_at,:updated_at)');
                    $database->bind(':id', $detayData->id);
                    $database->bind(':payment_method', $detayData->payment_method);
                    $database->bind(':shipping_method', $detayData->shipping_method);
                    $database->bind(':customer_id', $detayData->customer_id);
                    $database->bind(':company_id', $detayData->company_id);
                    $database->bind(':type', $detayData->type);
                    $database->bind(':billing_address_id', $detayData->billing_address_id);
                    $database->bind(':shipping_address_id', $detayData->shipping_address_id);
                    $database->bind(':total', $detayData->total);
                    $database->bind(':created_at', replace_date($detayData->created_at));
                    $database->bind(':updated_at', replace_date($detayData->updated_at));
                    $database->execute();
                    
                    /**
                     * Customer kontrol işlemi
                     */
                    if(!empty($detayData->customer_id)) {
                        $database->query('SELECT * FROM customer WHERE brand_id = :id');
                        $database->bind(':id', $detayData->customer_id);
                        $rowsCustomer = $database->getRows();
                        $customer = $database->rowCount();
                        if(empty($customer)) {
                            /**
                             * Customer kayıtlı değil ise sisteme ekleme işlemleri
                             */
                            $database->query('INSERT INTO customer (brand_id,name,email,phone,created_at,updated_at) VALUES 
                            (:id,:name,:email,:phone,:created_at,:updated_at)');
                            $database->bind(':id', $detayData->customer->id);
                            $database->bind(':name', $detayData->customer->name);
                            $database->bind(':email', $detayData->customer->email);
                            $database->bind(':phone', $detayData->customer->phone);
                            $database->bind(':created_at', replace_date($detayData->customer->created_at));
                            $database->bind(':updated_at', replace_date($detayData->customer->updated_at));
                            $database->execute();
                        }
                    }
    
                    /**
                     * Billing address kontrol işlemi
                     */
                    if(!empty($detayData->billing_address_id)) {
                        $database->query('SELECT * FROM billing_address WHERE brand_id = :id');
                        $database->bind(':id', $detayData->billing_address_id);
                        $rowsBillingAddress = $database->getRows();
                        $billingAddress = $database->rowCount();
                        if(empty($billingAddress)) {
                            /**
                             * Billing address kayıtlı değil ise sisteme ekleme işlemleri
                             */
                            $database->query('INSERT INTO billing_address (brand_id,name,phone,line_1,line_2,city,country,state,postcode,created_at,updated_at) VALUES 
                            (:id,:name,:phone,:line_1,:line_2,:city,:country,:state,:postcode,:created_at,:updated_at)');
                            $database->bind(':id', $detayData->billing_address->id);
                            $database->bind(':name', $detayData->billing_address->name);
                            $database->bind(':phone', $detayData->billing_address->phone);
                            $database->bind(':line_1', $detayData->billing_address->line_1);
                            $database->bind(':line_2', $detayData->billing_address->line_2);
                            $database->bind(':city', $detayData->billing_address->city);
                            $database->bind(':country', $detayData->billing_address->country);
                            $database->bind(':state', $detayData->billing_address->state);
                            $database->bind(':postcode', $detayData->billing_address->postcode);
                            $database->bind(':created_at', replace_date($detayData->billing_address->created_at));
                            $database->bind(':updated_at', replace_date($detayData->billing_address->updated_at));
                            $database->execute();
                        }
                    }
    
                     /**
                     * Shipping address kontrol işlemi
                     */
                    if(!empty($detayData->shipping_address_id)) {
                        $database->query('SELECT * FROM shipping_address WHERE brand_id = :id');
                        $database->bind(':id', $detayData->shipping_address_id);
                        $rowsShippingAddress = $database->getRows();
                        $shippingAddress = $database->rowCount();
                        if(empty($shippingAddress)) {
                            /**
                             * Shipping address kayıtlı değil ise sisteme ekleme işlemleri
                             */
                            $database->query('INSERT INTO shipping_address (brand_id,name,phone,line_1,line_2,city,country,state,postcode,created_at,updated_at) VALUES 
                            (:id,:name,:phone,:line_1,:line_2,:city,:country,:state,:postcode,:created_at,:updated_at)');
                            $database->bind(':id', $detayData->shipping_address->id);
                            $database->bind(':name', $detayData->shipping_address->name);
                            $database->bind(':phone', $detayData->shipping_address->phone);
                            $database->bind(':line_1', $detayData->shipping_address->line_1);
                            $database->bind(':line_2', $detayData->shipping_address->line_2);
                            $database->bind(':city', $detayData->shipping_address->city);
                            $database->bind(':country', $detayData->shipping_address->country);
                            $database->bind(':state', $detayData->shipping_address->state);
                            $database->bind(':postcode', $detayData->shipping_address->postcode);
                            $database->bind(':created_at', replace_date($detayData->shipping_address->created_at));
                            $database->bind(':updated_at', replace_date($detayData->shipping_address->updated_at));
                            $database->execute();
                        }
                    }

                    if(!empty($detayData->order_items)) {
                        foreach($detayData->order_items as $oiKey => $oiArray) {
                            /**
                             * Order items kontrol işlemi
                             */
                            if(!empty($oiArray->id)) {

                                $database->query('SELECT * FROM order_items WHERE brand_id = :id');
                                $database->bind(':id', $oiArray->id);
                                $rowsOrderItems = $database->getRows();
                                $orderItems = $database->rowCount();
                                if(empty($orderItems)) {
                                    /**
                                     * Order items kayıtlı değil ise sisteme ekleme işlemleri
                                     */
                                    $database->query('INSERT INTO order_items (brand_id,order_id,product_id,quantity,subtotal,created_at,updated_at) VALUES 
                                    (:id,:order_id,:product_id,:quantity,:subtotal,:created_at,:updated_at)');
                                    $database->bind(':id', $oiArray->id);
                                    $database->bind(':order_id', $oiArray->order_id);
                                    $database->bind(':product_id', $oiArray->product_id);
                                    $database->bind(':quantity', $oiArray->quantity);
                                    $database->bind(':subtotal', $oiArray->subtotal);
                                    $database->bind(':created_at', replace_date($oiArray->created_at));
                                    $database->bind(':updated_at', replace_date($oiArray->updated_at));
                                    $database->execute();
                                }
                                
                                /**
                                 * Product kontrol işlemi
                                 */
                                $database->query('SELECT * FROM product WHERE brand_id = :id');
                                $database->bind(':id', $oiArray->product_id);
                                $rowsProduct = $database->getRows();
                                $product = $database->rowCount();
                                if(empty($product)) {
                                    /**
                                     * Product kayıtlı değil ise sisteme ekleme işlemleri
                                     */
                                    $database->query('INSERT INTO product (brand_id,title,description,image,sku,price,created_at,updated_at) VALUES 
                                    (:id,:title,:description,:image,:sku,:price,:created_at,:updated_at)');
                                    $database->bind(':id', $oiArray->product->id);
                                    $database->bind(':title', $oiArray->product->title);
                                    $database->bind(':description', $oiArray->product->description);
                                    $database->bind(':image', $oiArray->product->image);
                                    $database->bind(':sku', $oiArray->product->sku);
                                    $database->bind(':price', $oiArray->product->price);
                                    $database->bind(':created_at', replace_date($oiArray->product->created_at));
                                    $database->bind(':updated_at', replace_date($oiArray->product->updated_at));
                                    $database->execute();
                                }
                            }
                        } 
                    }

                    if($val->id > $logLastId) {
                        $logLastId = $val->id;
                    }
                }
            }
            
            $log->updateLog($logLastId, $logListId);

            $database->query("SELECT * FROM orders WHERE type = 'pending'");
            $rows = $database->getRows();
            $orders = $database->rowCount();

            if($orders>0) {
                //Güncellleme kontrol ve 
                foreach ($rows as $key => $updVal) {
                    $reqTimeArray = apiSendTimeControl($reqTimeArray);

                    $pageDataUpdate = new PageData('https://sample-market.despatchcloud.uk/api/orders/'.$updVal['brand_id'].'?api_key=$2y$10$bs86Ks22xreLnf.5SWueTOdjUkiLdwSn/6cBHIL2GbaCjCUKJvUx.',3);
                    $updateData = $pageDataUpdate->returnData();
                    $logUpdate = new Logs($database,"3",'https://sample-market.despatchcloud.uk/api/orders/'.$updVal['brand_id'].'?api_key=$2y$10$bs86Ks22xreLnf.5SWueTOdjUkiLdwSn/6cBHIL2GbaCjCUKJvUx.',serialize($updateData),$updVal['brand_id'],0);
                    $logUpdate->insertLog();

                    if($updateData->id == $updVal['brand_id']) {
                        //Güncelle Sadece "Siparişin türünün onaylandı olarak güncellenmesi" istendiği için bu alanda güncelleme yapılmıştır
                        $database->query('UPDATE orders SET type = :type WHERE brand_id = :id');
                        $database->bind(':type', $updateData->type);
                        $database->bind(':id', $updateData->id);
                        $database->execute();
                    } 
                }
            } 
        }
    } while($i == 0);

    $refreshDate = new DateTime(date("Y-m-d H:i:s"));
    $frsDate = new DateTime($strtApiTime);
    $secondsDifArr = $frsDate->diff($refreshDate);
    $secondsWait = ($secondsDifArr->i * 60) + $secondsDifArr->s;
    if($secondsWait>=60) {
        header("Refresh:0");
    } else {
        header("Refresh:$secondsWait");
    }
?>
    