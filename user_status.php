<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

include('database.php');

http_response_code(200);

$get_user_id = $_GET['macid'];
$query = "SELECT * FROM map WHERE macid='$get_user_id'";
$article_query=mysqli_query($link,$query);
$data="";
while($article=mysqli_fetch_array($article_query)){
	 $data=$article['status'];
}  
$json = json_decode($data);
echo $data;

?>