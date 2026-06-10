<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

include('database.php');

http_response_code(200);

if(isset($_POST)){
	$user_id = mysqli_real_escape_string($link, $_POST['user_id']);
	$user_token = mysqli_real_escape_string($link,$_POST['user_token']);
	$user_b = mysqli_real_escape_string($link,$_POST['user_b']);
	$user_s = $_POST['user_s'];
	
	
    $json = json_decode($user_s);
    $stationType=$json->stationType;
	$startTime=$json->startTime;
	$expiryTime=$json->expiryTime;
	$registrationSequenceNumber=$json->registrationSequenceNumber;
	

    $key = $json->key;
    $new_key = str_replace(" ","+",$key);
    $json->key=$new_key;
    $key2 = $json->key;
    $user_s2 = json_encode($json);
	
	$delete_query="DELETE from test WHERE user_id='$user_id'";
	mysqli_query($link, $delete_query);
	 
	/*$query=mysqli_query($link,"select * from test where user_id='$user_id'");
	$count=mysqli_num_rows($query);
	if($count<1){
		$insery_query = "INSERT INTO test(user_id, user_token, user_b, user_s, user_s2, stationType, startTime, expiryTime, registrationSequenceNumber) VALUES ('$user_id','$user_token','$user_b','$user_s','$user_s2','$stationType','$startTime','$expiryTime','$registrationSequenceNumber')";
		mysqli_query($link,$insery_query);
	}else{
		$update_query = "UPDATE test SET user_token='$user_token', user_b='$user_b', user_s='$user_s' WHERE user_id='$user_id'";
        mysqli_query($link, $update_query);
	}*/
	
	$insery_query = "INSERT INTO test(user_id, user_token, user_b, user_s, user_s2, stationType, startTime, expiryTime, registrationSequenceNumber) VALUES ('$user_id','$user_token','$user_b','$user_s','$user_s2','$stationType','$startTime','$expiryTime','$registrationSequenceNumber')";
    mysqli_query($link,$insery_query);
	
	
	$get_user_id = $user_id;
	$query = "SELECT * FROM test WHERE user_id='$get_user_id'";
	$article_query=mysqli_query($link,$query);
	$data=[];
	while($article=mysqli_fetch_assoc($article_query)){
		 $data[]=$article;
	}  

	echo json_encode(array(
			"message" => "success",
			"data"=>$data
	));   
	
}

?>