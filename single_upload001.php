<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
$rest_json = file_get_contents("php://input");
//$_POST = json_decode($rest_json, true);

if(isset($_FILES['doc']['name'])){ 

	if(!empty($_FILES['doc']['name'])){
		
		$filename = $_FILES["doc"]["name"];
		$file_basename = substr($filename, 0, strripos($filename, '.'));
		$file_ext = substr($filename, strripos($filename, '.'));
		$allowed_file_types = array('.xml');
		
		 
		if(!in_array($file_ext,$allowed_file_types)){
                 echo json_encode(array(
			"status" => false, 
			"message" => "invalid file format (#001)"
		));
		rerturn;
             }
             else{
		
		
		$target = "upload25/";
		$target=$target.basename($_FILES['doc']['name']);
		move_uploaded_file($_FILES['doc']['tmp_name'],$target);
		
		echo json_encode(array( 
			"status" => true, 
			"message" => "doc Uploaded Sucessfully",
		//	"doc_url" => "http://localhost/test_a/upload/"
		));
		}
	}else{
		echo json_encode(array(
			"status" => false, 
			"message" => "No doc file found"
		));
	}
}else{
		echo json_encode(array(
			"status" => false, 
			"message" => "Provide 'doc' parameter for file upload"
		));
}
?>