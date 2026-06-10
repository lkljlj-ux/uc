<?php 
include('layout/header.php');
if(!isset($_SESSION['user_token'])){ 
    header("location:loginmm.php");
    exit();
}
?>
        <!-- Content -->
        <div class="content" style="min-height: 610px;">
            <!-- Animated -->
            <div class="animated fadeIn">
                <?php if($_SESSION['user_type']=='admin'){?>
                <!-- Widgets  -->
                <div class="row">
				
                   <!-- <div class="col-lg-3 col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="stat-widget-five">
                                    <div class="stat-icon dib flat-color-1">
                                        <i class="pe-7s-cash"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="text-left dib">
                                            <div class="stat-text">₹<span class="count">0</span></div>
                                            <div class="stat-heading">Revenue</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="stat-widget-five">
                                    <div class="stat-icon dib flat-color-2">
                                        <i class="pe-7s-cart"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="text-left dib">
                                            <div class="stat-text"><span class="count">0</span></div>
                                            <div class="stat-heading">Sales</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
					 -->
					
                    <div class="col-lg-6 col-md-6">
                        <div class="card">
							<a href="products.php">
                            <div class="card-body">
                                <div class="stat-widget-five">
                                    <div class="stat-icon dib flat-color-3">
                                        <i class="pe-7s-browser"></i>
                                    </div>
                                    <div class="stat-content">WELCOME TO ADMIN PANEL
                                    </div>
                                </div>
                            </div>
							</a>
                        </div>
                    </div>
                </div>
                <!-- /Widgets -->
            <?php }?>

            </div>
            <!-- .animated -->
        </div>
        <!-- /.content -->

<?php 
include('layout/footer.php');
?>