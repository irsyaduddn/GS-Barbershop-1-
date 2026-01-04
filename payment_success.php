<?php
session_start();

// Set timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

if (!isset($_SESSION['stripe_session_id'], $_SESSION['appointment_data'], $_SESSION['checkout_details'])) {
    header("Location: book_appointments.php");
    exit();
}

require __DIR__ . '/vendor/autoload.php';
include("db_connect.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

\Stripe\Stripe::setApiKey("sk_test_51RMP4MQvexRVoRKEdUyX3nwhN2QxUZb8YWdq2Cq9GDm4rr2B4xb1rZkdFOdLWCQ04tJ7xpVnpaebNJldmGbN5ixm00vLfAlDN5");

try {
    $session = \Stripe\Checkout\Session::retrieve($_SESSION['stripe_session_id']);
    $payment_intent = \Stripe\PaymentIntent::retrieve($session->payment_intent);

    if ($payment_intent->status === 'succeeded') {
        $checkout_details = $_SESSION['checkout_details'];
        $appointment_data = $_SESSION['appointment_data'];

        $selected_addons = !empty($checkout_details['addons']) ? 
            implode(',', array_column($checkout_details['addons'], 'addon_id')) : '';

        $sql = "UPDATE appointments SET
            customer_id = ?,
            service_id = ?,
            is_booked = 1,
            status = 'confirmed',
            variation = ?,
            selected_addons = ?,
            total_amount = ?,
            payment_id = ?,
            payment_status = 'paid',
            payment_date = NOW()
            WHERE appointment_id = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $customer_id = (int)$checkout_details['customer_id'];
        $service_id = (int)$checkout_details['service_id'];
        $variation = (string)$checkout_details['variation'];
        $addons_str = (string)$selected_addons;
        $subtotal = (float)$checkout_details['subtotal'];
        $payment_id = (string)$session->id;
        $appointment_id = (int)$appointment_data['appointment_id'];

        // Corrected bind_param to match 7 variables and 7 types
        $stmt->bind_param("iissdsi",
            $customer_id,
            $service_id,
            $variation,
            $addons_str,
            $subtotal,
            $payment_id,
            $appointment_id
        );

        $stmt->execute();
        $stmt->close();

        $cust_stmt = $conn->prepare("SELECT customer_name, customer_email FROM customers WHERE customer_id = ?");
        $cust_stmt->bind_param("i", $customer_id);
        $cust_stmt->execute();
        $cust_stmt->bind_result($customer_name, $customer_email);
        $cust_stmt->fetch();
        $cust_stmt->close();

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'irsyaduddn@gmail.com';
        $mail->Password = 'mvap bryp xrxf knxk';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('irsyaduddn@gmail.com', 'GS Barbershop');
        $mail->addAddress($customer_email, $customer_name);
        $mail->isHTML(true);
        $mail->Subject = 'Your Appointment at GS Barbershop is Confirmed!';
        $mail->Body = "<h2>Thank you for your booking!</h2>
            <p>Dear <strong>$customer_name</strong>,</p>
            <p>Your appointment has been successfully confirmed.</p>
            <p><strong>Service:</strong> {$checkout_details['service_name']}<br>
            <strong>Variation:</strong> {$checkout_details['variation']}<br>
            <strong>Total Amount:</strong> RM " . number_format($checkout_details['subtotal'], 2) . "</p>
            <p>We look forward to seeing you!<br>— GS Barbershop</p>";
        $mail->send();

        unset($_SESSION['stripe_session_id'], $_SESSION['checkout_details'], $_SESSION['appointment_data']);
        $_SESSION['payment_success'] = true;

    } else {
        $_SESSION['error'] = "Payment not completed. Please try again.";
        header("Location: checkout.php");
        exit();
    }

} catch (Exception $e) {
    error_log("Stripe Error: " . $e->getMessage());
    $_SESSION['error'] = "Payment verification failed.";
    header("Location: checkout.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        :root {
            --primary-color: #b89d5a;
            --primary-dark: #9a8249;
            --dark-color: #222;
            --light-color: #f9f9f9;
            --gray-light: #e9ecef;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                color: #333;
                line-height: 1.6;
                padding-top: 160px; /* Adjusted for fixed headers */
            }
        
        /* Top Header - Modernized */
         .top-header {
                background-color: white;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                position: fixed;
                width: 100%;
                top: 0;
                z-index: 1030;
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 15px 5%;
            }
            
            .logo-container {
                display: flex;
                align-items: center;
            }
            
            .logo {
                height: 50px;
                width: auto;
                margin-right: 15px;
            }
            
            .logo-title {
                font-size: 1.5rem;
                font-weight: 700;
                color: var(--dark-color);
            }
            
            .user-profile {
                display: flex;
                align-items: center;
            }
            
            .user-img {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                object-fit: cover;
                margin-right: 10px;
                border: 2px solid var(--primary-color);
            }
        
        .dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropdown button {
            background: none;
            border: none;
            color: var(--dark-color);
            font-weight: 500;
            cursor: pointer;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 180px;
            box-shadow: var(--shadow);
            z-index: 1;
            border-radius: 8px;
            overflow: hidden;
            animation: fadeIn 0.3s;
        }
        
        .dropdown-content a {
            color: var(--dark-color);
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: var(--transition);
            font-size: 0.9rem;
        }
        
        .dropdown-content a i {
            margin-right: 8px;
            width: 20px;
            text-align: center;
        }
        
        .dropdown-content a:hover {
            background-color: var(--primary-color);
            color: white;
            padding-left: 20px;
        }
        
        .dropdown:hover .dropdown-content {
            display: block;
        }
        
        /* Navigation Bar - Modernized */
        .navbar {
            background-color: var(--dark-color) !important;
            position: fixed;
            top: 80px;
            width: 100%;
            z-index: 1020;
            padding: 0 5%;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .navbar-dark .navbar-nav .nav-link {
            color: white;
            font-weight: 500;
            padding: 15px 20px;
            transition: var(--transition);
            position: relative;
            letter-spacing: 0.5px;
        }
        
        .navbar-dark .navbar-nav .nav-link::after {
            content: '';
            position: absolute;
            bottom: 10px;
            left: 20px;
            width: 0;
            height: 2px;
            background-color: var(--primary-color);
            transition: var(--transition);
        }
        
        .navbar-dark .navbar-nav .nav-link:hover::after,
        .navbar-dark .navbar-nav .active > .nav-link::after {
            width: calc(100% - 40px);
        }
        
        .navbar-dark .navbar-nav .nav-link:hover,
        .navbar-dark .navbar-nav .active > .nav-link {
            color: var(--primary-color);
        }
        
        /* About Us Content */
        .about-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 40px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .about-title {
            font-size: 2.5rem;
            color: var(--dark-color);
            margin-bottom: 30px;
            text-align: center;
            position: relative;
        }
        
        .about-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background-color: var(--primary-color);
        }
        
        .about-description {
            font-size: 1.1rem;
            color: #555;
            line-height: 1.8;
            margin-bottom: 20px;
        }
        
        .about-highlight {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        /* Footer - Modernized */
        footer {
            background-color: var(--dark-color);
            color: white;
            padding: 80px 5% 30px;
            margin-top: 80px;
            position: relative;
        }
        
        footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 10px;
            background: linear-gradient(90deg, var(--primary-color), var(--dark-color));
        }
        
        .footer-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .footer-column h3 {
            margin-bottom: 25px;
            font-size: 1.3rem;
            position: relative;
            padding-bottom: 10px;
            font-weight: 600;
        }
        
        .footer-column h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 2px;
            background-color: var(--primary-color);
        }
        
        .footer-column p, 
        .footer-column a {
            color: #bbb;
            margin-bottom: 15px;
            display: block;
            text-decoration: none;
            transition: var(--transition);
            line-height: 1.7;
        }
        
        .footer-column a:hover {
            color: var(--primary-color);
            padding-left: 5px;
        }
        
        .footer-column i {
            margin-right: 10px;
            width: 20px;
            color: var(--primary-color);
        }
        
        .social-links {
            display: flex;
        }
        
         .social-links a {
                display: inline-block;
                margin-right: 15px;
                font-size: 1.2rem;
            }
        
        .policy-link {
            margin-top: 20px;
        }
        
        .policy-link a {
            color: var(--primary-color);
            font-weight: 500;
            display: inline-flex;
            align-items: center;
        }
        
        .policy-link a:hover {
            text-decoration: underline;
        }
        
        .copyright {
            text-align: center;
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: #777;
            font-size: 0.9rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            body {
                padding-top: 140px;
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding-top: 120px;
            }
            
            .top-header {
                flex-direction: column;
                padding: 10px 5%;
            }
            
            .logo-container {
                margin-bottom: 10px;
            }
            
            .navbar {
                top: 120px;
            }
            
            .about-container {
                padding: 30px 20px;
                margin: 30px 20px;
            }
            
            .navbar-dark .navbar-nav .active > .nav-link::after {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-light text-center">
    <div class="container py-5">
        <div class="alert alert-success">
            <h2 class="mb-3">🎉 Payment Successful!</h2>
            <p>Your appointment has been confirmed. A confirmation email has been sent to you.</p>
            <a href="view_appointments.php" class="btn btn-success mt-3">View My Appointments</a>
        </div>
    </div>
</body>
</html>
