<?php
session_start();
unset($_SESSION['dist_id']);
unset($_SESSION['dist_name']);
header("location:distributor_login.php");
exit();
