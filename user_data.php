<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

include('database.php');

http_response_code(200);

if(isset($_POST)){
	$user_id = mysqli_real_escape_string($link, $_POST['user_id']);
	$ForcedCapture =mysqli_real_escape_string($link, $_POST['ForcedCapture']);
	$StartTime =mysqli_real_escape_string($link, $_POST['StartTime']);
	$EndTime =mysqli_real_escape_string($link, $_POST['EndTime']);
	$Score =mysqli_real_escape_string($link, $_POST['Score']);
	$SampleDataByteArray =mysqli_real_escape_string($link, $_POST['SampleDataByteArray']);
	$RenderData =mysqli_real_escape_string($link, $_POST['RenderData']);
	$Deleted =mysqli_real_escape_string($link, $_POST['Deleted']);
    $FtcFlag=mysqli_real_escape_string($link, $_POST['FtcFlag']);
	$delete_query="DELETE from test1 WHERE user_id='$user_id'";
	mysqli_query($link, $delete_query);
	 
	$insery_query = "INSERT INTO test1(user_id, ForcedCapture, StartTime, EndTime, Score, SampleDataByteArray, RenderData, Deleted, FtcFlag) VALUES ('$user_id', '$ForcedCapture', '$StartTime', '$EndTime', '$Score', '$SampleDataByteArray', '$RenderData', '$Deleted','$FtcFlag')";
        mysqli_query($link,$insery_query);

	$get_user_id = $user_id;
	$query = "SELECT * FROM test1 WHERE user_id='$get_user_id'";
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