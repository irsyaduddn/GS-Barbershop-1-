<?php 
session_start();

// Set timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

if (!isset($_SESSION['user']) || $_SESSION['usertype'] !== 'customer') {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['appointment_data'])) {
    header("Location: book_appointments.php");
    exit();
}

include("db_connect.php");

$appointment_data = $_SESSION['appointment_data'];
$service_id = $appointment_data['service_id'];
$barber_id = $appointment_data['barber_id'];
$appointment_date = $appointment_data['appointment_date'];
$appointment_time = $appointment_data['appointment_time'];
$variation = $appointment_data['variation'];
$addons = $appointment_data['addons'] ?? [];
$customer_id = $appointment_data['customer_id'];

$email = $_SESSION['user'];
$sql_user = "SELECT customer_id, customer_name, customer_photo, customer_email FROM customers WHERE customer_email = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("s", $email);
$stmt_user->execute();
$stmt_user->bind_result($customer_id, $full_name, $profile_picture, $customer_email);
$stmt_user->fetch();
$stmt_user->close();

$sql_service = "SELECT service_name, service_price, duration FROM services WHERE service_id = ?";
$stmt_service = $conn->prepare($sql_service);
$stmt_service->bind_param("i", $service_id);
$stmt_service->execute();
$stmt_service->bind_result($service_name, $service_price, $duration);
$stmt_service->fetch();
$stmt_service->close();

$sql_barber = "SELECT barber_name FROM barbers WHERE barber_id = ?";
$stmt_barber = $conn->prepare($sql_barber);
$stmt_barber->bind_param("i", $barber_id);
$stmt_barber->execute();
$stmt_barber->bind_result($barber_name);
$stmt_barber->fetch();
$stmt_barber->close();

$addons_details = [];
$addons_total = 0;
$selected_addons = [];

if (!empty($addons)) {
    $placeholders = implode(',', array_fill(0, count($addons), '?'));
    $sql_addons = "SELECT addon_id, service_name, service_price FROM addon_services WHERE addon_id IN ($placeholders)";
    $stmt_addons = $conn->prepare($sql_addons);

    if ($stmt_addons) {
        $types = str_repeat('i', count($addons));
        $stmt_addons->bind_param($types, ...$addons);
        $stmt_addons->execute();
        $result = $stmt_addons->get_result();

        while ($row = $result->fetch_assoc()) {
            $addons_details[] = $row;
            $addons_total += $row['service_price'];
            $selected_addons[] = $row['addon_id'];
        }

        $stmt_addons->close();
    }
}

$subtotal = $service_price + $addons_total;

$_SESSION['checkout_details'] = [
    'service_id' => $service_id,
    'service_name' => $service_name,
    'service_price' => $service_price,
    'duration' => $duration,
    'barber_id' => $barber_id,
    'barber_name' => $barber_name,
    'appointment_date' => $appointment_date,
    'appointment_time' => $appointment_time,
    'variation' => $variation,
    'addons' => $addons_details,
    'subtotal' => $subtotal,
    'customer_id' => $customer_id,
    'selected_addons' => $selected_addons
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . "/vendor/autoload.php";

    $stripe_secret_key = "sk_test_51RMP4MQvexRVoRKEdUyX3nwhN2QxUZb8YWdq2Cq9GDm4rr2B4xb1rZkdFOdLWCQ04tJ7xpVnpaebNJldmGbN5ixm00vLfAlDN5";
    \Stripe\Stripe::setApiKey($stripe_secret_key);

    $line_items = [[
        "quantity" => 1,
        "price_data" => [
            "currency" => "myr",
            "unit_amount" => $service_price * 100,
            "product_data" => ["name" => "$service_name Appointment"]
        ]
    ]];

    foreach ($addons_details as $addon) {
        $line_items[] = [
            "quantity" => 1,
            "price_data" => [
                "currency" => "myr",
                "unit_amount" => $addon['service_price'] * 100,
                "product_data" => ["name" => "Addon: {$addon['service_name']}"]
            ]
        ];
    }

    $metadata = [
        'customer_id' => $customer_id,
        'service_id' => $service_id,
        'barber_id' => $barber_id,
        'appointment_date' => $appointment_date,
        'appointment_time' => $appointment_time,
        'variation' => $variation,
        'addons' => implode(',', $selected_addons)
    ];

    try {
        $checkout_session = \Stripe\Checkout\Session::create([
            "mode" => "payment",
            "success_url" => "http://localhost/PSM/payment_success.php?session_id={CHECKOUT_SESSION_ID}",
            "cancel_url" => "http://localhost/PSM/payment_cancel.php",
            "locale" => "auto",
            "customer_email" => $email,
            "line_items" => $line_items,
            "metadata" => $metadata,
            "payment_intent_data" => ["metadata" => $metadata]
        ]);

        $_SESSION['stripe_session_id'] = $checkout_session->id;
        $_SESSION['payment_amount'] = $subtotal;

        header("Location: " . $checkout_session->url);
        exit();
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log("Stripe error: " . $e->getMessage());
        $_SESSION['error'] = "Payment processing failed. Please try again.";
        header("Location: checkout.php");
        exit();
    }
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - GS Barbershop</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #b89d5a;
            --dark-color: #222;
            --light-color: #f9f9f9;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            line-height: 1.6;
            padding-top: 160px;
            background-color: #f8f9fa;
        }
        
        /* Top Header - Enhanced */
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
            min-width: 160px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            z-index: 1;
            border-radius: 4px;
        }
        
        .dropdown-content a {
            color: var(--dark-color);
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: background-color 0.3s;
        }
        
        .dropdown-content a:hover {
            background-color: var(--light-color);
            color: var(--primary-color);
        }
        
        .dropdown:hover .dropdown-content {
            display: block;
        }
        
        /* Navigation Bar - Enhanced */
        .navbar {
            background-color: var(--dark-color) !important;
            position: fixed;
            top: 80px;
            width: 100%;
            z-index: 1020;
            padding: 0 5%;
        }
        .navbar-dark .navbar-nav .nav-link {
                color: white;
                font-weight: 500;
                padding: 15px 20px;
                transition: color 0.3s;
            }
            
            .navbar-dark .navbar-nav .nav-link:hover,
            .navbar-dark .navbar-nav .active > .nav-link {
                color: var(--primary-color);
            }
        
        /* Main Content */
        .main-container {
            padding: 40px 5%;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .checkout-container {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .service-item, .addon-item {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .price-summary {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .total-row {
            font-weight: bold;
            font-size: 1.1rem;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 12px 30px;
            font-weight: 600;
            margin-top: 20px;
        }
        
        .btn-primary:hover {
            background-color: #a58a4a;
            border-color: #a58a4a;
        }
        
        /* Loading modal */
        .loading-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        
        .loading-content {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            max-width: 400px;
        }
        
        .loading-spinner {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        /* Footer - Enhanced */
        footer {
            background-color: var(--dark-color);
            color: white;
            padding: 60px 5% 30px;
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
            margin-bottom: 12px;
            display: block;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-column a:hover {
            color: var(--primary-color);
        }
        
        .social-links a {
            display: inline-block;
            margin-right: 15px;
            font-size: 1.2rem;
        }
        
        .copyright {
            text-align: center;
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid #444;
            color: #777;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <!-- Top Header -->
    <header class="top-header">
        <div class="logo-container">
            <img src="logo.jpg" alt="GS Barbershop Logo" class="logo">
            <div class="logo-title">GS Barbershop</div>
        </div>
        <div class="user-profile">
            <img src="<?php echo htmlspecialchars($profile_picture ?: 'default_profile.png'); ?>" alt="User" class="user-img">
            <div class="dropdown">
                <button>
                    <?php echo htmlspecialchars($full_name); ?> <i class="fas fa-caret-down"></i>
                </button>
                <div class="dropdown-content">
                    <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="home.php">Home</a></li>  
                    <li class="nav-item"><a class="nav-link" href="book_appointment.php">Book Appointment</a></li>
                    <li class="nav-item"><a class="nav-link" href="view_appointments.php">My Appointments</a></li>
                    <li class="nav-item"><a class="nav-link" href="about_us.php">About Us</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container">
        <h1 class="mb-4">Checkout</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <div class="checkout-container">
            <h4>Appointment Details</h4>
            <div class="service-item">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5><?php echo htmlspecialchars($service_name); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars($variation); ?></p>
                        <p class="text-muted">Barber: <?php echo htmlspecialchars($barber_name); ?></p>
                        <p class="text-muted">Date: <?php echo date('F j, Y', strtotime($appointment_date)); ?></p>
                        <p class="text-muted">Time: <?php echo date('g:i A', strtotime($appointment_time)); ?></p>
                    </div>
                    <div class="text-right">
                        <p>RM <?php echo number_format($service_price, 2); ?></p>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($addons_details)): ?>
            <h5 class="mt-4">Add-On Services</h5>
            <?php foreach ($addons_details as $addon): ?>
                <div class="addon-item d-flex justify-content-between">
                    <div>
                        <p class="mb-0"><?php echo htmlspecialchars($addon['service_name']); ?></p>
                    </div>
                    <div class="text-right">
                        <p class="mb-0">+ RM <?php echo number_format($addon['service_price'], 2); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php endif; ?>
            
            <div class="price-summary">
                <div class="price-row total-row">
                    <span>Total:</span>
                    <span>RM <?php echo number_format($subtotal, 2); ?></span>
                </div>
            </div>
            
            <form id="paymentForm" method="POST">
                <button type="submit" class="btn btn-primary btn-block">
                    Pay Now (RM <?php echo number_format($subtotal, 2); ?>)
                </button>
            </form>
        </div>
    </div>



    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-column">
                <h3>FOLLOW US</h3>
                <div class="social-links mt-4">
                    <a href="https://www.facebook.com/people/Gsbarbershop/100063705272441/"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/gengsahak_barbershop/"><i class="fab fa-instagram"></i></a>
                </div>
                <div class="policy-link mt-3">
                    <a href="cancellation_policy.php" class="text-decoration-none"> Cancellation Policy</a>
                </div>
            </div>
            
            <div class="footer-column">
                <h3>CONTACT US</h3>
                <p><i class="fas fa-map-marker-alt"></i> No 24, Jalan Bunga Cempaka 1, Serom 6, Sungai Mati, Tangkak, Johor</p>
                <p><i class="fas fa-phone"></i> (+60) 13-3248962</p>
                <p><i class="fas fa-envelope"></i> amilsahak8@gmail.com</p>
            </div>
            
            <div class="footer-column">
                <h3>OPENING HOURS</h3>
                <p>Saturday - Thursday: 10.30am - 6pm</p>
                <p>Friday: Closed</p>
            </div>
        </div>
        
        <div class="copyright">
            <p>&copy; <?php echo date("Y"); ?> GS Barbershop. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
    $(document).ready(function() {
        $('#paymentForm').on('submit', function(e) {
            e.preventDefault();
            
            // Show loading modal
            $('#loadingModal').css('display', 'flex');
            
            // Submit the form
            this.submit();
        });
    });
    </script>
</body>
</html>