<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
if((isset($_GET["encdata"]) && !empty($_GET['encdata'])) && isset($_GET["encKey"]) && !empty($_GET['encKey'])){
	$encdata = $_GET["encdata"];
	$encKey = $_GET["encKey"];
	//$dec = openssl_decrypt($encdata, 'AES-128-ECB', "ZDloeGtwNmZzNA==");
	$dec = openssl_decrypt($encdata, 'AES-128-ECB', $encKey);
	http_response_code(200);
	$dec = json_decode($dec);
	print json_encode($dec);
}
?>