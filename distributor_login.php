<?php
ob_start();
session_start();
if(isset($_SESSION['dist_id'])){
    header("location:distributor_panel.php");
    exit();
}

$error = '';
if(isset($_POST['login'])){
    include('database.php');
    $username = mysqli_real_escape_string($link, trim($_POST['username']));
    $password = md5(trim($_POST['password']));

    $q = "SELECT * FROM distributors WHERE username='$username' AND password='$password' AND status='active'";
    $res = mysqli_query($link, $q);

    if($res && mysqli_num_rows($res) > 0){
        $row = mysqli_fetch_assoc($res);
        $_SESSION['dist_id']   = $row['id'];
        $_SESSION['dist_name'] = $row['distributor_name'];
        header("location:distributor_panel.php");
        exit();
    } else {
        $error = 'Username ya Password galat hai, ya account inactive hai!';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Distributor Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">
    <link href='https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #0d1b2a 0%, #1b2838 40%, #0a3d62 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        body::before, body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            opacity: 0.07;
            animation: float 8s ease-in-out infinite;
        }
        body::before {
            width: 500px; height: 500px;
            background: #1abc9c;
            top: -150px; right: -100px;
        }
        body::after {
            width: 400px; height: 400px;
            background: #0a3d62;
            bottom: -120px; left: -80px;
            animation-delay: -4s;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px) scale(1); }
            50%       { transform: translateY(-30px) scale(1.05); }
        }

        .login-wrapper {
            width: 100%;
            max-width: 440px;
            padding: 20px;
            position: relative;
            z-index: 10;
        }

        .login-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px;
            padding: 48px 40px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4);
        }

        .login-logo {
            text-align: center;
            margin-bottom: 32px;
        }
        .login-logo .logo-icon {
            width: 72px; height: 72px;
            background: linear-gradient(135deg, #1abc9c, #16a085);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            box-shadow: 0 8px 24px rgba(26,188,156,0.4);
        }
        .login-logo .logo-icon i {
            font-size: 32px;
            color: #fff;
        }
        .login-logo h2 {
            color: #fff;
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .login-logo p {
            color: rgba(255,255,255,0.45);
            font-size: 13px;
            font-weight: 300;
        }

        .alert-err {
            background: rgba(231,76,60,0.15);
            border: 1px solid rgba(231,76,60,0.4);
            color: #ff6b6b;
            border-radius: 12px;
            font-size: 13px;
            padding: 10px 14px;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-group { margin-bottom: 20px; }

        .form-group label {
            color: rgba(255,255,255,0.65);
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 8px;
            display: block;
        }

        .input-wrapper { position: relative; }
        .input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.35);
            font-size: 15px;
            z-index: 2;
        }
        .input-wrapper input {
            width: 100%;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            padding: 13px 16px 13px 44px;
            color: #fff;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            outline: none;
        }
        .input-wrapper input::placeholder { color: rgba(255,255,255,0.25); }
        .input-wrapper input:focus {
            border-color: #1abc9c;
            background: rgba(255,255,255,0.1);
            box-shadow: 0 0 0 3px rgba(26,188,156,0.15);
        }

        .toggle-pass {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.3);
            cursor: pointer;
            font-size: 14px;
            background: none;
            border: none;
            padding: 0;
            z-index: 2;
            transition: color 0.2s;
        }
        .toggle-pass:hover { color: rgba(255,255,255,0.7); }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1abc9c, #16a085);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(26,188,156,0.4);
            letter-spacing: 0.5px;
            margin-top: 8px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(26,188,156,0.5);
        }
        .btn-login:active { transform: translateY(0); }
        .btn-login i { margin-right: 8px; }

        .admin-link {
            text-align: center;
            margin-top: 20px;
        }
        .admin-link a {
            color: rgba(255,255,255,0.35);
            font-size: 12px;
            text-decoration: none;
            transition: color 0.2s;
        }
        .admin-link a:hover { color: rgba(255,255,255,0.7); }

        .login-footer {
            text-align: center;
            margin-top: 12px;
            color: rgba(255,255,255,0.25);
            font-size: 12px;
        }
        .login-footer span { color: #1abc9c; }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">

        <div class="login-logo">
            <div class="logo-icon">
                <i class="fa fa-sitemap"></i>
            </div>
            <h2>Distributor Portal</h2>
            <p>Aadhaar Station Management System</p>
        </div>

        <?php if($error): ?>
            <div class="alert-err">
                <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" autocomplete="off">
            <div class="form-group">
                <label>Username</label>
                <div class="input-wrapper">
                    <i class="fa fa-user"></i>
                    <input type="text" name="username" placeholder="Apna username daalo" required>
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-wrapper">
                    <i class="fa fa-lock"></i>
                    <input type="password" name="password" id="passField" placeholder="Password daalo" required>
                    <button type="button" class="toggle-pass" onclick="togglePass()">
                        <i class="fa fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" name="login" class="btn-login">
                <i class="fa fa-sign-in"></i> Login Karo
            </button>
        </form>

        <div class="admin-link">
            <a href="login.php"><i class="fa fa-lock"></i> Admin Login</a>
        </div>

        <div class="login-footer">
            Powered by <span>OnlineSolution.cloud</span>
        </div>

    </div>
</div>

<script>
function togglePass() {
    var f = document.getElementById('passField');
    var i = document.getElementById('eyeIcon');
    if(f.type === 'password'){
        f.type = 'text';
        i.className = 'fa fa-eye-slash';
    } else {
        f.type = 'password';
        i.className = 'fa fa-eye';
    }
}
</script>
</body>
</html>
