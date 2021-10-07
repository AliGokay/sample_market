<?php
$api_key = '$2y$10$bs86Ks22xreLnf.5SWueTOdjUkiLdwSn/6cBHIL2GbaCjCUKJvUx.';

$ch = curl_init();

// Liste
curl_setopt($ch, CURLOPT_URL, 'https://sample-market.despatchcloud.uk/api/orders?api_key=' . $api_key.'&page=2');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));


// detay
/*
curl_setopt($ch, CURLOPT_URL, 'https://sample-market.despatchcloud.uk/api/orders/148098?api_key=' . $api_key);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
*/
//Update 
/*
curl_setopt($ch, CURLOPT_URL, 'https://sample-market.despatchcloud.uk/api/orders/148098?api_key=' . $api_key.'');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS,"type=approved");
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
*/

$sonuc = curl_exec($ch);

curl_close($ch);

$data = json_decode($sonuc, true);
echo '<pre>';
var_dump($data);