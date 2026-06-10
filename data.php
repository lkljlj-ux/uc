<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

include('database.php');

http_response_code(200);

$get_user_id = $_GET['user_id'];
$query = "SELECT * FROM test WHERE user_id='$get_user_id'";
$article_query=mysqli_query($link,$query);
$data="";
while($article=mysqli_fetch_array($article_query)){
	 $data=$article['user_s'];
}  
//echo $data;
$json = json_decode($data);
$key = $json->key;
$new_key = str_replace(" ","+",$key);
//echo $new_key;
$json->key=$new_key;
$key2 = $json->key;
//echo $key2;
$data2 = json_encode($json);
//echo $data2;
echo $json->id.",".$json->stationType.",".$json->key.",".$json->startTime.",".$json->expiryTime.",".$json->registrationSequenceNumber; 

?>