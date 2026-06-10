<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
$rest_json = file_get_contents("php://input");
//$_POST = json_decode($rest_json, true);

include('database.php');

http_response_code(200);

 if(isset($_GET['user_id'])){
	
	$user_id=$_GET['user_id'];
	
	$query=mysqli_query($link,"Select * from system_name_data where user_id='$user_id'"); 

	$data=[];
	while($dat=mysqli_fetch_assoc($query)){
		 $data[]=$dat;
	} 
	echo json_encode(array(
			"status" => true, 
			"message" => "User Id related Data Fetch Successfully",
			"data"=>$data
		));

}else{
	$query=mysqli_query($link,"Select * from system_name_data"); 
	$data=[];
	while($dat=mysqli_fetch_assoc($query)){
		 $data[]=$dat;
	} 
	echo json_encode(array(
			"status" => true, 
			"message" => "All Data Fetch Successfully",
			"data"=>$data,
		));
}