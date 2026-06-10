<?php 
$pages='system_date_data';
include('layout/header.php');
if(!isset($_SESSION['user_token']) || $_SESSION['user_type']!='admin'){ 
    header("location:loginmm.php");
    exit();
}
?>
<link rel="stylesheet" href="assets/css/lib/datatable/dataTables.bootstrap.min.css">
        <div class="content">
        	<div class="animated fadeIn">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <strong class="card-title">Save system_date_data</strong>
                            </div>
                            <div class="card-body">
                                <!-- Credit Card -->
                                <div id="pay-invoice">
                                    <div class="card-body">
                                        <form action="" method="post" enctype="multipart/form-data"  accept-charset="UTF-8">
                                            <div class="form-group">
                                                <label class="control-label mb-1">system_date </label>
                                                <input  name="system_date" type="text" class="form-control" required>
                                            </div>
											<div class="form-group">
                                                <label class="control-label mb-1">user_id</label>
                                                <input type="text" name="user_id"  class="form-control">
                                            </div>
                                            <div>
                                                <button id="payment-button" type="submit" name="save" class="btn btn-lg btn-info btn-block">
                                                    <span id="payment-button-amount">Save</span>
                                                    <span id="payment-button-sending" style="display:none;">Save</span></span>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div> <!-- .card -->
                    </div><!--/.col-->
					<div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <strong class="card-title">Delete system_date_data</strong>
                            </div>
                            <div class="card-body">
                                <!-- Credit Card -->
                                <div id="pay-invoice">
                                    <div class="card-body">
                                        <form action="" method="post" enctype="multipart/form-data"  accept-charset="UTF-8">
											<div class="form-group">
                                                <label class="control-label mb-1">user_id</label>
                                                <input type="text" name="user_id"  class="form-control">
                                            </div>
                                            <div>
                                                <button id="payment-button" type="submit" name="delete" class="btn btn-lg btn-info btn-block">
                                                    <span id="payment-button-amount">Delete</span>
                                                    <span id="payment-button-sending" style="display:none;">Delete</span></span>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div> <!-- .card -->
                    </div><!--/.col-->
					
					<div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <strong class="card-title">REPLACE system_date</strong>
                            </div>
                            <div class="card-body">
                                <!-- Credit Card -->
                                <div id="pay-invoice">
                                    <div class="card-body">
                                        <form action="" method="post" enctype="multipart/form-data"  accept-charset="UTF-8">
                                            <div class="form-group">
                                                <label class="control-label mb-1">user_name </label>
                                                <select  name="user_name" class="form-control" required>
													<option value="Pankaj">Pankaj</option>
													<option value="Pitnu">Pitnu</option>
													<option value="KC">KC</option>
													<option value="Rajiv">Rajiv</option>
													<option value="Rajiv">Rohit</option>
												</select>
                                            </div>
											<div class="form-group">
                                                <label class="control-label mb-1">system_date</label>
                                                <input type="text" name="system_date"  class="form-control">
                                            </div>
                                            <div>
                                                <button id="payment-button" type="submit" name="replace" class="btn btn-lg btn-info btn-block">
                                                    <span id="payment-button-amount">Replace</span>
                                                    <span id="payment-button-sending" style="display:none;">Replace</span></span>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div> <!-- .card -->
                    </div><!--/.col-->
					
					
                </div>
            </div>        
        </div>
        <?php 
        if(isset($_POST['save'])){ 
            $system_date=$_POST['system_date'];
            $user_id=$_POST['user_id']; 
		   
		    $query=mysqli_query($link,"Select * from system_date_data where user_id='$user_id'"); 
			$query_count=mysqli_num_rows($query);
			
			if($query_count>0){
				$update_query = "UPDATE system_date_data SET system_date='$system_date' WHERE user_id='$user_id'";
				mysqli_query($link,$update_query);
			}else{ 
				$insery_query = "INSERT INTO system_date_data(system_date, user_id) VALUES ('$system_date','$user_id')";
				mysqli_query($link,$insery_query);
			}
		}
		if(isset($_POST['delete'])){
            $user_id=$_POST['user_id']; 
		   
			$delete_query = "DELETE FROM system_date_data WHERE user_id='$user_id'";
			mysqli_query($link,$delete_query);
			
		}
		if(isset($_POST['replace'])){ 
            $user_name=$_POST['user_name'];
            $system_date=$_POST['system_date']; 
			
			$query=mysqli_query($link,"Select * from test2 where user_name='$user_name'"); 
			$query_count=mysqli_num_rows($query);
			
			if($query_count>0){
				$machine=[];
				while($test = mysqli_fetch_array($query)){
					$machine[]=$test['machine'];
				}
				$data=[];
				$ids = join("','",$machine);
				$query2=mysqli_query($link,"UPDATE system_date_data SET system_date='$system_date'  where  user_id IN ('$ids')"); 		
				echo "system_date replaced";
				$query2=mysqli_query($link,"Select * from system_date_data where  user_id IN ('$ids')"); 
				while($sdd = mysqli_fetch_array($query2)){
					$data[]=$sdd['user_id'];
				}
			}else{  
				echo "No row with user_name";
			}
		}
		
        ?> 
       <?php if(isset($_POST['replace'])){  ?>
			<div class="content">
        	<div class="animated fadeIn">
                <div class="row">
                    <div class="col-lg-12 col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <strong class="card-title">All replaced system_date Data</strong>
                            </div>
                            <div class="card-body">
                                <table class="table"  id="bootstrap-data-table">
                                    <thead>
                                        <tr>
                                            <th>system_date</th>
                                            <th>user_id</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
										  $query3=mysqli_query($link,"Select * from system_date_data where  user_id IN ('$ids')"); 
										  while($data_=mysqli_fetch_array($query3)){?>
                                        <tr>
                                            <td><?php echo $data_['system_date']; ?></td>
                                            <td><?php echo $data_['user_id']; ?></td>
                                        </tr>
										<?php } ?>
										  	
                                    </tbody>
                                </table>
                            </div> <!-- /.table-stats -->
                        </div>
                    </div>
                </div>
            </div>        
        </div>
	   <?php }else {  ?>
		<div class="content">
        	<div class="animated fadeIn">
                <div class="row">
                    <div class="col-lg-12 col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <strong class="card-title">All system_date_data Data</strong>
                            </div>
                            <div class="card-body">
                                <table class="table"  id="bootstrap-data-table">
                                    <thead>
                                        <tr>
                                            <th>system_date</th>
                                            <th>user_id</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
										  $query=mysqli_query($link,"SELECT * FROM system_date_data ");
										  
										  while($data=mysqli_fetch_array($query)){?>
                                        <tr>
                                            <td><?php echo $data['system_date']; ?></td>
                                            <td><?php echo $data['user_id']; ?></td>
                                        </tr>
										<?php } ?>
										  	
                                    </tbody>
                                </table>
                            </div> <!-- /.table-stats -->
                        </div>
                    </div>
                </div>
            </div>        
        </div>
	   <?php }  ?>

        <!-- /.content -->	
        <script src="assets/js/lib/data-table/datatables.min.js"></script>
	    <script src="assets/js/lib/data-table/dataTables.bootstrap.min.js"></script>
	    <script src="assets/js/lib/data-table/dataTables.buttons.min.js"></script>
	    <script src="assets/js/lib/data-table/buttons.bootstrap.min.js"></script>
	    <script src="assets/js/lib/data-table/jszip.min.js"></script>
	    <script src="assets/js/lib/data-table/vfs_fonts.js"></script>
	    <script src="assets/js/lib/data-table/buttons.html5.min.js"></script>
	    <script src="assets/js/lib/data-table/buttons.print.min.js"></script>
	    <script src="assets/js/lib/data-table/buttons.colVis.min.js"></script>
	    <script src="assets/js/init/datatables-init.js"></script>
        <script type="text/javascript">
	        $(document).ready(function() {
		        $('#bootstrap-data-table-export').DataTable();
		    } );
	    </script>

<?php 
?>