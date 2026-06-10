<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

include('database.php');

http_response_code(200);

if(isset($_POST)){
	$user_id = mysqli_real_escape_string($link, $_POST['user_id']);
	$user_s = $_POST['user_s'];
	
	$delete_query="DELETE from system_date_data WHERE user_id='$user_id'";
	mysqli_query($link, $delete_query);
	 
	$query=mysqli_query($link,"select * from system_date_data where user_id='$user_id'");
	$count=mysqli_num_rows($query);
	if($count<1){
		$insery_query = "INSERT INTO system_date_data(user_id, system_date) VALUES ('$user_id','$user_s')";
        mysqli_query($link,$insery_query);
	}
	
echo $user_id; 
	
}

?>