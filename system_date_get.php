<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

include('database.php');

http_response_code(200);

$get_user_id = $_GET['user_id'];
$query = "SELECT * FROM system_date_data WHERE user_id='$get_user_id'";
$article_query=mysqli_query($link,$query);
$data="";
while($article=mysqli_fetch_array($article_query)){
	 $data=$article['system_date'];
}  
$json = json_decode($data);
echo $data;

?>