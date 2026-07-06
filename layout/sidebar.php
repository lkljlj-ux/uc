<style>
        .switch {
                position: relative;
                display: inline-block;
                width: 50px;
                height: 30px;
        }
        input:checked + .slider:before {
                -webkit-transform: translateX(20px);
                -ms-transform: translateX(20px);
                transform: translateX(20px);
        }
        .slider:before {
                height: 20px;
                width: 20px;
                left: 5px;
                bottom: 5px;
        }
</style>

<!-- Left Panel -->
    <aside id="left-panel" class="left-panel">
        <nav class="navbar navbar-expand-sm navbar-default">
            <div id="main-menu" class="main-menu collapse navbar-collapse">
                <ul class="nav navbar-nav">
                                <?php $page=basename($_SERVER['PHP_SELF']); ?>
                    <li class="<?php if($page=='index.php'){ echo 'active';} ?>">
                        <a href="dashboard.php"><i class="menu-icon fa fa-laptop"></i>Dashboard </a>
                    </li>

                    <?php if(isset($_SESSION['user_type']) && $_SESSION['user_type']=='admin'){?>
                                                <li class=" <?php if($page=='new_operator.php'){ echo 'active';} ?>">
                                                        <a href="new_operator.php"><i class="menu-icon fa fa-archive"></i> New Operator</a>
                                                </li>
                                                <li class=" <?php if($page=='operator_add.php'){ echo 'active';} ?>">
                                                        <a href="operator_add.php"><i class="menu-icon fa fa-user-plus"></i> Operator Add</a>
                                                </li>
                                                <li class=" <?php if($page=='operator_xml_upload.php'){ echo 'active';} ?>">
                                                        <a href="operator_xml_upload.php"><i class="menu-icon fa fa-file-code-o"></i> XML Upload</a>
                                                </li>
                                                <li class=" <?php if($page=='distributor.php' && !isset($_GET['list'])){ echo 'active';} ?>">
                                                        <a href="distributor.php"><i class="menu-icon fa fa-truck"></i> Distributor</a>
                                                </li>
                                                <li class=" <?php if($page=='distributor.php' && isset($_GET['list'])){ echo 'active';} ?>">
                                                        <a href="distributor.php?list=1"><i class="menu-icon fa fa-list-ul"></i> Distributors List</a>
                                                </li>
                                                <li class=" <?php if($page=='distributor_login.php'){ echo 'active';} ?>">
                                                        <a href="distributor_login.php" target="_blank"><i class="menu-icon fa fa-sitemap"></i> Dist. Portal</a>
                                                </li>
                                                <li class=" <?php if($page=='retailer.php'){ echo 'active';} ?>">
                                                        <a href="retailer.php"><i class="menu-icon fa fa-shopping-cart"></i> Retailer</a>
                                                </li>
                                                <li class=" <?php if($page=='map.php'){ echo 'active';} ?>">
                                                        <a href="map.php"><i class="menu-icon fa fa-plus"></i> Map machine</a>
                                                </li>
                                                <li class=" <?php if($page=='mac_coupon.php'){ echo 'active';} ?>">
                                                        <a href="mac_coupon.php"><i class="menu-icon fa fa-ticket"></i> MAC Coupon</a>
                                                </li>
                                                <li class=" <?php if($page=='all_report.php'){ echo 'active';} ?>">
                                                        <a href="all_report.php"><i class="menu-icon fa fa-list"></i> All Report</a>
                                                </li>
                                                <li class=" <?php if($page=='change_password.php'){ echo 'active';} ?>">
                                                        <a href="change_password.php"><i class="menu-icon fa fa-key"></i> Change Password</a>
                                                </li>
                                                <li class="">
                                                        <a href="logout.php"><i class="menu-icon fa fa-sign-out"></i> Logout</a>
                                                </li>
                    <?php }?>
                </ul>
            </div><!-- /.navbar-collapse -->
        </nav>
    </aside>
    <!-- /#left-panel -->