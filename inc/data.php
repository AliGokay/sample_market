<?php

class PageData {
    private $page_url;
    private $api_type;

    /**
    * @param $pageUrl  [data çekilecek sayfa]
    * @param $apiType  [çakilen api]
    */

    public function __construct($pageUrl,$apiType=''){
        $this->page_url=$pageUrl;
        $this->api_type=$apiType;
    }

    /**
     * Apiden data alma işlemleri
     */
    
    public function returnData(){
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $this->page_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
        if($this->api_type == 3) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,"type=approved");
        }

        $data = curl_exec($ch);
        curl_close($ch);

        return json_decode($data);
    }
}
?>