<?php
    /**
     * 
     * Tarih formatını kayıt için düzenler
     */

    function replace_date($date){
        $dateArr = explode(".",str_ireplace("T"," ",$date));
        return current($dateArr);
    }
    
    /**
     * Dakikada 30 adet istek kontrolü
     */
    function apiSendTimeControl($timeArray){
        if(count($timeArray)>=30) {
            $frsTime = new DateTime(current($timeArray)); 
            $now = new DateTime();
            $secondsDifArr = $frsTime->diff($now);
            $secondsWait = ($secondsDifArr->i * 60) + $secondsDifArr->s;
            if($secondsWait<60){
                sleep(60-$secondsWait);
            }
            array_shift($timeArray);
            $timeArray[] = date("Y-m-d H:i:s");
        } else {
            $timeArray[] = date("Y-m-d H:i:s");
        }
        return $timeArray;
    }
?>