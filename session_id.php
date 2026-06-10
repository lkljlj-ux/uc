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
	 $data=$article['user_b'];
}  

echo $data; 

?>