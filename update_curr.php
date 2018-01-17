<?php

$url = "https://online.moysklad.ru/api/remap/1.1/entity/currency/"; 
$ch = curl_init(); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
curl_setopt($ch, CURLOPT_URL, $url); 
curl_setopt($ch, CURLOPT_USERPWD, "login : password"); 
$result = curl_exec($ch); 
curl_close($ch); 
$respon = json_decode($result, true);

$rub = $respon["rows"][0]["rate"];
$usd = $respon["rows"][1]["rate"];
$eur = $respon["rows"][2]["rate"];

//include MODX API
define('MODX_API_MODE', true);
include_once(dirname(__FILE__)."/index.php");
$modx->db->connect();
if (empty($modx->config)) {
    $modx->getSettings();
}

$tableName = 'modx_site_tmplvar_contentvalues';
$tv_rub = 60;
$tv_usd = 58;
$tv_eur = 59;
$stat_info = 28;

//update RUB
$fields = array('value'  => $rub);  
$result = $modx->db->update( $fields, $tableName, 'tmplvarid = '. $tv_rub .' AND contentid = '. $stat_info );   

//update USD
$fields = array('value'  => $usd);  
$result = $modx->db->update( $fields, $tableName, 'tmplvarid = '. $tv_usd .' AND contentid = '. $stat_info );   

//update EUR
$fields = array('value'  => $eur);  
$result = $modx->db->update( $fields, $tableName, 'tmplvarid = '. $tv_eur .' AND contentid = '. $stat_info );   
