<?php
    function replace_date($date){
        $dateArr = explode(".",str_ireplace("T"," ",$date));
        return current($dateArr);
    }
?>