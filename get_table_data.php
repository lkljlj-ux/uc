<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
$rest_json = file_get_contents("php://input");
//$_POST = json_decode($rest_json, true);

include('database.php');

http_response_code(200);

 if(isset($_GET['user_id'])){
	
	$user_id=$_GET['user_id'];
	$table=	"<table>
				<thead> 
					<tr>
						<th>id</th>
						<th></th>
						<th>user_id</th>
						<th></th>
						<th>user_name</th>
						<th></th>
						<th>user_count</th>
						<th></th>
						<th>system_date</th>
					</tr>
				</thead>
				<tbody>";
				$table_qry=mysqli_query($link,"Select * from system_name_data where user_id='$user_id'");
				while($row=mysqli_fetch_array($table_qry))
					{
	$table.=			"<tr>
							<td>".$row['id']."</td> 
							<td></td> 
							<td>".$row['user_id']."</td> 
							<td></td>
							<td>".$row['user_name']."</td> 
							<td></td>
							<td>".$row['user_count']."</td> 
							<td></td>
							<td>".$row['system_date']."</td> 
						</tr>";
					}
	$table.=	"</tbody>
			</table>";
	
	echo $table;

}else{
	$table=	"<table>
				<thead> 
					<tr>
						<th>id</th>
						<th></th>
						<th>user_id</th>
						<th></th>
						<th>user_name</th>
						<th></th>
						<th>user_count</th>
						<th></th>
						<th>system_date</th>
					</tr>
				</thead>
				<tbody>";
				$table_qry=mysqli_query($link,"Select * from system_name_data");
				while($row=mysqli_fetch_array($table_qry))
					{
	$table.=			"<tr>
							<td>".$row['id']."</td> 
							<td></td> 
							<td>".$row['user_id']."</td> 
							<td></td>
							<td>".$row['user_name']."</td> 
							<td></td>
							<td>".$row['user_count']."</td> 
							<td></td>
							<td>".$row['system_date']."</td> 
						</tr>";
					}
	$table.=	"</tbody>
			</table>";
	
	echo $table;
}