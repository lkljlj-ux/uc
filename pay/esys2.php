<?php
$json_string= file_get_contents('php://input');
$res=json_decode($json_string,true);
  if ($res['status']=="success") {
  $dec =openssl_decrypt($res['data'], 'AES-128-ECB', "c252bHEyajB1eA==");
  $resp=json_decode($dec,true);
  file_put_contents("res.txt", $resp);
}
//file_put_contents("res.txt", $json_string);
//$Bearerfile = fopen("test.php", "w") or die("Unable to open file!");
//fwrite($Bearerfile, $json_string);

echo "Success1";
?>