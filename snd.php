<?php 
$pages='system_name_data';
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
					<div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <strong class="card-title">Delete system_name_data</strong>
                            </div>
                            <div class="card-body">
                                <!-- Credit Card -->
                                <div id="pay-invoice">
                                    <div class="card-body">
                                        <form action="" method="post" enctype="multipart/form-data"  accept-charset="UTF-8">
											<div class="form-group">
                                                <label class="control-label mb-1">system_date</label>
                                                <input type="text" name="system_date"  class="form-control">
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
		if(isset($_POST['delete'])){
            $system_date=$_POST['system_date']; 
		   
			$delete_query = "DELETE FROM system_name_data WHERE system_date='$system_date'";
			mysqli_query($link,$delete_query);
			
		}
        ?>	
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