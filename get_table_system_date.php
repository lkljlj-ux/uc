<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
$rest_json = file_get_contents("php://input");
//$_POST = json_decode($rest_json, true);

include('database.php');

http_response_code(200);

 if(isset($_GET['system_date'])){
	
	$system_date=$_GET['system_date'];
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
				$table_qry=mysqli_query($link,"Select * from system_name_data where system_date='$system_date' order by id desc");
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
	
	$table_qry=mysqli_query($link,"Select sum(user_count) as u_count from system_name_data where system_date='$system_date' ");
				if($row=mysqli_fetch_array($table_qry))
					{
					echo $row['u_count'];
					}

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