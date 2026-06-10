<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

include('database.php');

http_response_code(200);

if(isset($_POST['user_id']) && isset($_POST['system_date'])){
	
	$user_id=$_POST['user_id'];
	$system_date=$_POST['system_date'];
	
	$query=mysqli_query($link,"Select * from system_name_data where user_id='$user_id' AND system_date='$system_date'"); 
	$query_count=mysqli_num_rows($query);
	
	if($query_count>0){
		$data=mysqli_fetch_assoc($query);
		$user_count=$data['user_count']+1;
		
		$update_query = "UPDATE system_name_data SET user_count='$user_count'  WHERE user_id='$user_id' AND system_date='$system_date'";
        mysqli_query($link,$update_query);
		
		
		echo json_encode(array(
				"status" => true, 
				"message" => "Data Found so Count Updated to ".$user_count
			));
	}else{ 
			if(isset($_POST['user_name'])){
				$user_name=$_POST['user_name'];
			}else{
				$user_name=null;
			}
			
			
			$insery_query = "INSERT INTO system_name_data(user_id, user_name, user_count, system_date) VALUES ('$user_id','$user_name',1,'$system_date')";
			mysqli_query($link,$insery_query);
			
			echo json_encode(array(
				"status" => true, 
				"message" => "No Data found so created data with count 1"
			));
	}
}else{
	echo json_encode(array(
			"status" => true, 
			"message" => "Please provide valid variable name"
		));
}
?>