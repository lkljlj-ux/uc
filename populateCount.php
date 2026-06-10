<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

include('database.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

http_response_code(200);
//echo $_GET['macId'];

if(isset($_GET['macId']) && isset($_GET['adhNumber'])){
	
	$macId = $_GET['macId'];
	$adhNumber = $_GET['adhNumber'];
	
	date_default_timezone_set('Asia/Kolkata');
	$system_date =  date("Y-m-d");

	
	
	
	$query= mysqli_query($link,"Select id, user_count from system_name_data where user_id='$adhNumber' AND macId='$macId' AND system_date='$system_date'"); 
	if ($query === false) {
    echo "SQL Error: " . mysqli_error($link);
    exit;
}

	$query_count=mysqli_num_rows($query);
	
	if($query_count>0){
		$data=mysqli_fetch_assoc($query);
		$user_count=$data['user_count']+1;
		
	$update_query = "UPDATE system_name_data SET user_count='$user_count'  WHERE user_id='$adhNumber' AND macId='$macId' AND system_date='$system_date'";
        mysqli_query($link,$update_query);
		
		echo 'success';
		/*echo json_encode(array(
				"status" => true, 
				"message" => "Data Found so Count Updated to ".$user_count
			));*/
	}else{ 
			
			
			$insery_query = "INSERT INTO system_name_data(user_id, macId, user_count, system_date) VALUES ('$adhNumber','$macId',1,'$system_date')";
			mysqli_query($link,$insery_query);
			echo 'success';
			/*echo json_encode(array(
				"status" => true, 
				"message" => "No Data found so created data with count 1"
			));*/
	}
}else{
	/*echo json_encode(array(
			"status" => true, 
			"message" => "Please provide valid variable name"
		));*/
}
?>