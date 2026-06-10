<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
$rest_json = file_get_contents("php://input");
$_POST = json_decode($rest_json, true);

include('database.php');


if(isset($_POST['system_date'])){
	
	$system_date=$_POST['system_date'];
	$query=mysqli_query($link,"Select * from system_date_data where system_date='$system_date'"); 
	$query_count=mysqli_num_rows($query);
	if($query_count>0){
		$data=mysqli_fetch_assoc($query);
		echo json_encode(array(
				"status" => true, 
				"message" => "Single Data Fetch Successfully",
				"data"=>$data,
			));
	}else{ 
			echo json_encode(array(
				"status" => false, 
				"message" => "No Data fund related to this Yet"
			));
	}
}else{
	$query=mysqli_query($link,"Select * from system_date_data"); 
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
?>