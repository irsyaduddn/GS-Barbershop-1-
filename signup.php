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
            --primary-color: #3a5a78;
            --secondary-color: #f8b400;
            --accent-color: #28a745;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --error-color: #ff3e3e;
            --success-color: #28a745;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8eb 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            padding: 40px;
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.5s ease-out;
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

        img.logo {
            max-width: 120px;
            height: auto;
            transition: transform 0.3s ease;
        }

        img.logo:hover {
            transform: scale(1.05);
        }

        .header-text {
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 5px;
            color: var(--primary-color);
            letter-spacing: 0.5px;
        }

        .sub-text {
            font-size: 14px;
            color: #6c757d;
            text-align: center;
            margin-bottom: 30px;
            font-weight: 500;
        }

        .form-container {
            animation: fadeInUp 0.6s ease-out;
        }

        .form-group {
            margin-bottom: 20px;
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
            padding: 12px 15px 12px 40px;
            border: 2px solid #e1e5ee;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }

        .input-text:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(58, 90, 120, 0.1);
            outline: none;
            background-color: white;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .input-text:focus + .input-icon {
            color: var(--primary-color);
        }

        .signup-btn {
            width: 100%;
            padding: 14px;
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
        }

        .signup-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.15);
        }

        .signup-btn:active {
            transform: translateY(0);
        }

        .non-style-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .non-style-link:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        .message {
            text-align: center;
            margin-bottom: 20px;
            font-weight: 600;
            padding: 12px;
            border-radius: 8px;
            animation: fadeIn 0.5s ease-out;
        }

        .success {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .error {
            background-color: rgba(255, 62, 62, 0.1);
            color: var(--error-color);
            border: 1px solid rgba(255, 62, 62, 0.3);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #6c757d;
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

        /* Responsive adjustments */
        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }
            
            .header-text {
                font-size: 24px;
            }
        }
    </style>
    <title>Sign Up - GS Barbershop</title>
</head>
<body>
    <?php
    include("db_connect.php");

    $message = "";

    if ($_POST) {
        $name = $_POST['customer_name'];
        $email = $_POST['customer_email'];
        $password = $_POST['customer_password'];
        $confirm_password = $_POST['confirm_password'];
        $phone = $_POST['customer_phone'];
        $age = $_POST['customer_age'];

        if (strlen($password) < 6) {
            $message = "<div class='message error'>Password must be at least 6 characters long!</div>";
        } elseif ($password !== $confirm_password) {
            $message = "<div class='message error'>Passwords do not match!</div>";
        } else {
            // Check if email already exists
            $check_email = $conn->query("SELECT * FROM customers WHERE customer_email = '$email'");

            if ($check_email->num_rows > 0) {
                $message = "<div class='message error'>Email is already registered!</div>";
            } else {
                // Insert new customer into customers table
                $sql_customer = "INSERT INTO customers (customer_name, customer_email, customer_password, customer_phone, customer_age) VALUES ('$name', '$email', '$password', '$phone', '$age')";

                // Insert into web_users table
                $sql_webuser = "INSERT INTO web_users (email, user_type) VALUES ('$email', 'customer')";

                if ($conn->query($sql_customer) === TRUE && $conn->query($sql_webuser) === TRUE) {
                    $message = "<div class='message success'>Registration successful! You can now <a href='login.php' class='non-style-link'>Log In</a>.</div>";
                } else {
                    $message = "<div class='message error'>Error in registration. Please try again!</div>";
                }
            }
        }
    }
    ?>

    <div class="container">
        <div class="logo-container">
            <img src="logo.jpg" alt="GS Barbershop Logo" class="logo">
            <p class="header-text">GS BARBERSHOP</p>
            <p class="header-text">Create Your Account</p>
        </div>
        
        <?php echo $message; ?>
        
        <div class="form-container">
            <form action="" method="POST">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <div class="input-container">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="customer_name" class="input-text" placeholder="Enter your full name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-container">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="customer_email" class="input-text" placeholder="Enter your email" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-container">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="customer_password" class="input-text" placeholder="Enter your password" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-container">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="confirm_password" class="input-text" placeholder="Confirm your password" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <div class="input-container">
                        <i class="fas fa-phone input-icon"></i>
                        <input type="text" name="customer_phone" class="input-text" placeholder="Enter your phone number" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="age">Age</label>
                    <div class="input-container">
                        <i class="fas fa-birthday-cake input-icon"></i>
                        <input type="number" name="customer_age" class="input-text" placeholder="Enter your age" required>
                    </div>
                </div>
                
                <input type="submit" value="SIGN UP" class="signup-btn">
            </form>
            
            <p class="login-link">Already have an account? <a href="login.php" class="non-style-link">Log In Here</a></p>
        </div>
    </div>
</body>
</html>