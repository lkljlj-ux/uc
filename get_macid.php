<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
$rest_json = file_get_contents("php://input");
//$_POST = json_decode($rest_json, true);

include('database.php');

http_response_code(200);

 if(isset($_GET['user_id'])){
	
	$user_id=$_GET['user_id'];
	
	$query = mysqli_query($link,"Select macid from test2 where machine='$user_id'"); 
	$result = mysqli_fetch_array($query);
	if (mysqli_num_rows($query) > 0) {
echo $result['macid'];
}

}