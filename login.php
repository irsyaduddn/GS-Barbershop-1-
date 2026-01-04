<?php
// Start session at the very beginning
session_start();
ob_start(); // Start output buffering to prevent header errors

// Initialize session variables
$_SESSION["user"] = "";
$_SESSION["usertype"] = "";

// Set timezone and date
date_default_timezone_set('Asia/Kuala_Lumpur');
$date = date('Y-m-d');
$_SESSION["date"] = $date;

// Database connection
include("db_connect.php");

// Initialize error message
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['useremail'];
    $password = $_POST['userpassword'];
    
    $result = $conn->query("SELECT * FROM web_users WHERE email='$email'");

    if ($result && $result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $usertype = $row['user_type'];

        $user_found = false;
        $wrong_password = false;

        if ($usertype == 'admin') {
            $user_check = $conn->query("SELECT * FROM admin WHERE admin_email='$email'");
            if ($user_check && $user_check->num_rows == 1) {
                $user_found = true;
                $admin = $user_check->fetch_assoc();
                if ($admin['admin_password'] != $password) {
                    $wrong_password = true;
                } else {
                    $_SESSION['user'] = $email;
                    $_SESSION['usertype'] = 'admin';
                    header('location: admin_dashboard.php');
                    exit();
                }
            }
        } elseif ($usertype == 'barber') {
            $user_check = $conn->query("SELECT * FROM barbers WHERE barber_email='$email'");
            if ($user_check && $user_check->num_rows == 1) {
                $user_found = true;
                $barber = $user_check->fetch_assoc();
                if ($barber['barber_password'] != $password) {
                    $wrong_password = true;
                } else {
                    $_SESSION['user'] = $email;
                    $_SESSION['usertype'] = 'barber';
                    header('location: barber_dashboard.php');
                    exit();
                }
            }
        } elseif ($usertype == 'customer') {
            $user_check = $conn->query("SELECT * FROM customers WHERE customer_email='$email'");
            if ($user_check && $user_check->num_rows == 1) {
                $user_found = true;
                $customer = $user_check->fetch_assoc();
                if ($customer['customer_password'] != $password) {
                    $wrong_password = true;
                } else {
                    $_SESSION['user'] = $email;
                    $_SESSION['usertype'] = 'customer';
                    header('location: home.php');
                    exit();
                }
            }
        }

        if ($wrong_password) {
            $error = '<div class="error-message"><i class="fas fa-exclamation-circle"></i> The password you entered is incorrect</div>';
        } elseif (!$user_found) {
            $error = '<div class="error-message"><i class="fas fa-exclamation-circle"></i> Account not found for this email</div>';
        }
    } else {
        $error = '<div class="error-message"><i class="fas fa-exclamation-circle"></i> Account not found for this email</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/animations.css">  
    <link rel="stylesheet" href="css/main.css">  
    <link rel="stylesheet" href="css/login.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #e67e22;
            --accent-color: #3498db;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --error-color: #e74c3c;
            --success-color: #2ecc71;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background: linear-gradient(135deg, #ecf0f1 0%, #dfe6e9 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            padding: 40px;
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.6s ease-out;
            transition: transform 0.3s ease;
        }

        .container:hover {
            transform: translateY(-5px);
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .logo-container {
            text-align: center;
            margin-bottom: 25px;
            animation: slideDown 0.5s ease-out;
        }

        .logo {
            width: 100px;
            height: auto;
            transition: transform 0.3s ease;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .header-text {
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            margin: 10px 0 5px;
            color: var(--primary-color);
            letter-spacing: 0.5px;
        }

        .sub-text {
            font-size: 14px;
            color: #7f8c8d;
            text-align: center;
            margin-bottom: 30px;
            font-weight: 500;
        }

        .form-container {
            animation: fadeInUp 0.6s ease-out;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark-color);
            transition: all 0.3s ease;
        }

        .input-container {
            position: relative;
        }

        .input-text {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #e1e5ee;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }

        .input-text:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            outline: none;
            background-color: white;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #95a5a6;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .input-text:focus + .input-icon {
            color: var(--accent-color);
        }

        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            margin-top: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            background: linear-gradient(90deg, var(--secondary-color), var(--primary-color));
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .non-style-link {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .non-style-link:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        .error-message {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--error-color);
            text-align: center;
            padding: 12px;
            border-radius: 8px;
            margin: 15px 0;
            font-weight: 600;
            border: 1px solid rgba(231, 76, 60, 0.3);
            animation: shake 0.5s ease;
        }

        .login-link {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            color: #7f8c8d;
        }

        .forgot-password {
            text-align: right;
            margin-top: -15px;
            margin-bottom: 20px;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeInUp {
            from { 
                opacity: 0;
                transform: translateY(20px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideDown {
            from { 
                opacity: 0;
                transform: translateY(-20px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }

        /* Responsive adjustments */
        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }
            
            .header-text {
                font-size: 24px;
            }
            
            .input-text {
                padding: 12px 15px 12px 40px;
            }
        }
    </style>
    <title>Login - GS Barbershop</title>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img src="logo.jpg" alt="GS Barbershop Logo" class="logo">
            <p class="header-text">GS BARBERSHOP</p>
            <p class="header-text">Welcome!</p>
        </div>
        
        <?php echo $error; ?>
        
        <div class="form-container">
            <form action="" method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-container">
                        <input type="email" name="useremail" class="input-text" placeholder="Enter your email" required>
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-container">
                        <input type="password" name="userpassword" class="input-text" placeholder="Enter your password" required>
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                </div>
                
                <input type="submit" value="Log In" class="login-btn">
            </form>
            
            <p class="login-link">Don't have an account? <a href="signup.php" class="non-style-link">Sign Up Now</a></p>
        </div>
    </div>
</body>
</html>
<?php ob_end_flush(); ?>