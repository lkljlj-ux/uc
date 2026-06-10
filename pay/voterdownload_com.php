<?php
$json_string= file_get_contents('php://input');
$res=json_decode($json_string,true);
  if ($res['status']=="success") {
  $dec =openssl_decrypt($res['data'], 'AES-128-ECB', "ZDloeGtwNmZzNA==");
  file_put_contents("res.txt", $dec);
  $curl = curl_init();
  curl_setopt_array($curl, array(
    CURLOPT_URL => "https://voterdownload.com/Retailer/Wlt_TopupOnlineCallBack.aspx",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => $dec,
  ));
  $response = curl_exec($curl);
  curl_close($curl);
}
echo "Success1";
?>