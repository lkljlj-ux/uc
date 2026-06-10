<?php 
$pages='stest2';
include('layout/header.php');
if(!isset($_SESSION['user_token']) || $_SESSION['user_type']!='admin'){ 
    header("location:login.php");
    exit();
}
?>
<link rel="stylesheet" href="assets/css/lib/datatable/dataTables.bootstrap.min.css">
        <div class="content">
        	<div class="animated fadeIn">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <strong class="card-title">Save Test 2</strong>
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
                                                <label class="control-label mb-1">machine</label>
                                                <input type="text" name="machine"  class="form-control">
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label mb-1">mac id</label>
                                                <input type="text" name="macid"  class="form-control">
                                            </div>
											<div class="form-group">
                                                <label class="control-label mb-1">Number</label>
                                                <input type="text" name="extra"  class="form-control">
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
					<div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <strong class="card-title">Delete Test 2</strong>
                            </div>
                            <div class="card-body">
                                <!-- Credit Card -->
                                <div id="pay-invoice">
                                    <div class="card-body">
                                        <form action="" method="post" enctype="multipart/form-data"  accept-charset="UTF-8">
											<div class="form-group">
                                                <label class="control-label mb-1">machine</label>
                                                <input type="text" name="machine"  class="form-control">
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
                </div>
            </div>        
        </div>
        <?php 
        if(isset($_POST['save'])){ 
            $user_name = $_POST['user_name'];
            $machine = $_POST['machine']; 
		     $extra=$_POST['extra']; 
		     $macid = $_POST['macid']; 
			 
		    $query=mysqli_query($link,"Select * from test2 where machine='$machine'"); 
			$query_count=mysqli_num_rows($query);
			
			if($query_count>0){
				
				$update_query = "UPDATE test2 SET user_name='$user_name' WHERE machine='$machine'";
				mysqli_query($link,$update_query);
			}else{
				$max_query = mysqli_query($link,"SELECT MAX(id) FROM test2");
				$row = mysqli_fetch_row($max_query);
				$last_id = $row[0];
				$id=$last_id+1;
				$insery_query = "INSERT INTO test2(id, user_name, machine, extra, macid) VALUES ('$id','$user_name','$machine',' $extra', '$macid')";
				
				mysqli_query($link,$insery_query);
			}
		}
		if(isset($_POST['delete'])){
            $machine=$_POST['machine']; 
		   
			$delete_query = "DELETE FROM test2  WHERE machine='$machine'";
			mysqli_query($link,$delete_query);
			
		}
        ?>
       
        <div class="content">
        	<div class="animated fadeIn">
                <div class="row">
                    <div class="col-lg-12 col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <strong class="card-title">All Test 2 Data</strong>
                            </div>
                            <div class="card-body">
                                <table class="table"  id="bootstrap-data-table">
                                    <thead>
                                        <tr>
										    <th>id</th>
                                            <th>user_name</th>
                                            <th>machine</th>
											 <th>Number</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
										  $query=mysqli_query($link,"SELECT * FROM test2 ");
										  
										  while($data=mysqli_fetch_array($query)){?>
                                        <tr>
											<td><?php echo $data['id']; ?></td>
                                            <td><?php echo $data['user_name']; ?></td>
                                            <td><?php echo $data['machine']; ?></td>
											 <td><?php echo $data['extra']; ?></td>
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