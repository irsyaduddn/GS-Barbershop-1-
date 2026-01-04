<?php
// Start session
session_start();

// Set timezone (adjust to your location)
date_default_timezone_set('Asia/Kuala_Lumpur');

// Check if the user is logged in and is a customer
if (!isset($_SESSION['user']) || $_SESSION['usertype'] !== 'customer') {
    header("Location: login.php");
    exit();
}

// Include database connection
include("db_connect.php");

// Check if appointment ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: view_appointments.php");
    exit();
}

$appointment_id = $_GET['id'];
$customer_email = $_SESSION['user'];

// Fetch customer details (ID, full name, profile picture)
$customerQuery = $conn->prepare("SELECT customer_id, customer_name, customer_photo FROM customers WHERE customer_email = ?");
if (!$customerQuery) {
    die("Error preparing customer query: " . $conn->error);
}

if (!$customerQuery->bind_param("s", $customer_email)) {
    die("Error binding customer parameters: " . $customerQuery->error);
}

if (!$customerQuery->execute()) {
    die("Error executing customer query: " . $customerQuery->error);
}

$customerQuery->bind_result($customer_id, $full_name, $profile_picture);
$customerQuery->fetch();
$customerQuery->close();

// Fetch appointment details
$sql = "
    SELECT 
        a.appointment_id,
        a.appointment_date,
        a.appointment_time,
        a.variation,
        a.selected_addons,
        a.status,
        s.service_name,
        s.service_price,
        b.barber_name
    FROM 
        appointments a
    JOIN 
        services s ON a.service_id = s.service_id
    JOIN 
        barbers b ON a.barber_id = b.barber_id
    LEFT JOIN 
        payments p ON a.appointment_id = p.appointment_id
    WHERE 
        a.appointment_id = ? 
        AND a.customer_id = ?
";

$appointmentQuery = $conn->prepare($sql);

if ($appointmentQuery === false) {
    die("Error preparing query: " . $conn->error);
}

if (!$appointmentQuery->bind_param("ii", $appointment_id, $customer_id)) {
    die("Error binding parameters: " . $appointmentQuery->error);
}

if (!$appointmentQuery->execute()) {
    die("Error executing query: " . $appointmentQuery->error);
}

$result = $appointmentQuery->get_result();

if ($result->num_rows === 0) {
    header("Location: view_appointments.php");
    exit();
}

$appointment = $result->fetch_assoc();
$appointmentQuery->close();

// Process selected addons if they exist
$addons_details = [];
if (!empty($appointment['selected_addons'])) {
    $addon_ids = explode(',', $appointment['selected_addons']);
    
    if (!empty($addon_ids) && is_array($addon_ids)) {
        $placeholders = implode(',', array_fill(0, count($addon_ids), '?'));
        
        // Changed to query from addon_services table like in the checkout page
        $addonQuery = $conn->prepare("
            SELECT addon_id, service_name, service_price 
            FROM addon_services 
            WHERE addon_id IN ($placeholders)
        ");
        
        if ($addonQuery) {
            $types = str_repeat('i', count($addon_ids));
            $bindParams = array_merge([$types], $addon_ids);
            $bindParamsReferences = [];
            
            foreach ($bindParams as $key => $value) {
                $bindParamsReferences[$key] = &$bindParams[$key];
            }
            
            call_user_func_array(array($addonQuery, 'bind_param'), $bindParamsReferences);
            
            $addonQuery->execute();
            $addonResult = $addonQuery->get_result();
            
            while ($row = $addonResult->fetch_assoc()) {
                $addons_details[] = [
                    'service_name' => $row['service_name'],
                    'service_price' => $row['service_price']
                ];
            }
            $addonQuery->close();
        }
    }
}

// Calculate prices
$service_price = $appointment['service_price'];
$subtotal = $service_price;
foreach ($addons_details as $addon) {
    $subtotal += $addon['service_price'];
}
$deposit = 1.00; // Fixed RM1 deposit
$balance = $subtotal - $deposit;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GS Barbershop - Appointment Details</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        
        /* Main Content */
        .main-container {
            padding: 40px 5%;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-header {
            margin-bottom: 40px;
            position: relative;
            padding-bottom: 15px;
        }
        
        .page-header::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 80px;
            height: 3px;
            background: var(--primary-color);
        }
        
        .appointment-card {
            background-color: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            transition: var(--transition);
        }
        
        .appointment-card:hover {
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-confirmed {
            background-color: #e1f5fe;
            color: #0288d1;
        }
        
        .status-completed {
            background-color: #e8f5e9;
            color: #388e3c;
        }
        
        .status-cancelled {
            background-color: #ffebee;
            color: #d32f2f;
        }
        
        .service-item {
            display: flex;
            justify-content: space-between;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
        }
        
        .service-details {
            flex: 1;
        }
        
        .service-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .service-meta {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .service-price {
            font-weight: 600;
            color: var(--dark-color);
            min-width: 100px;
            text-align: right;
        }
        
        .addon-item {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .addon-name {
            color: #666;
            font-size: 0.95rem;
        }
        
        .addon-price {
            color: #666;
            font-size: 0.95rem;
            min-width: 100px;
            text-align: right;
        }
        
        .price-summary {
            background-color: #f9f9f9;
            padding: 25px;
            border-radius: 8px;
            margin-top: 30px;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .total-row {
            font-weight: 700;
            font-size: 1.1rem;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .btn-back {
            background-color: var(--dark-color);
            color: white;
            border: none;
            padding: 12px 25px;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: var(--transition);
            margin-top: 30px;
        }
        
        .btn-back:hover {
            background-color: #333;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .btn-back i {
            margin-right: 8px;
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
            
            .appointment-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .status-badge {
                margin-top: 10px;
            }
            
            .service-item, .addon-item {
                flex-direction: column;
            }
            
            .service-price, .addon-price {
                text-align: left;
                margin-top: 5px;
            }
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
                    <li class="nav-item active"><a class="nav-link" href="view_appointments.php">My Appointments</a></li>
                    <li class="nav-item"><a class="nav-link" href="about_us.php">About Us</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container">
        <div class="page-header">
            <h1>Appointment Details</h1>
            <p class="text-muted">Review your appointment information</p>
        </div>
        
        <div class="appointment-card">
            <div class="appointment-header">
                <div>
                    <h3>Appointment #<?php echo htmlspecialchars($appointment['appointment_id']); ?></h3>
                    <p class="text-muted mb-0">Booked on <?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?></p>
                </div>
                <span class="status-badge status-<?php echo strtolower($appointment['status']); ?>">
                    <?php echo htmlspecialchars($appointment['status']); ?>
                </span>
            </div>
            
            <div class="service-item">
                <div class="service-details">
                    <h4 class="service-name"><?php echo htmlspecialchars($appointment['service_name']); ?></h4>
                    <?php if (!empty($appointment['variation'])): ?>
                        <p class="service-meta">Style: <?php echo htmlspecialchars($appointment['variation']); ?></p>
                    <?php endif; ?>
                    <p class="service-meta">Barber: <?php echo htmlspecialchars($appointment['barber_name']); ?></p>
                    <p class="service-meta">
                        <i class="far fa-calendar-alt"></i> <?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?>
                        <span class="mx-2">•</span>
                        <i class="far fa-clock"></i> <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?>
                    </p>
                </div>
                <div class="service-price">
                    RM <?php echo number_format($service_price, 2); ?>
                </div>
            </div>
            
            <?php if (!empty($addons_details)): ?>
                <h5 class="mt-4 mb-3">Add-On Services</h5>
                <?php foreach ($addons_details as $addon): ?>
                    <div class="addon-item">
                        <span class="addon-name">
                            <i class="fas fa-plus-circle text-success"></i> <?php echo htmlspecialchars($addon['service_name']); ?>
                        </span>
                        <span class="addon-price">
                            + RM <?php echo number_format($addon['service_price'], 2); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <div class="price-summary">
                <div class="price-row total-row">
                    <span>Subtotal:</span>
                    <span>RM <?php echo number_format($subtotal, 2); ?></span>
                </div>
            </div>
        </div>
        
        <div class="text-center">
            <a href="view_appointments.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Appointments
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-column">
                <h3>FOLLOW US</h3>
                <p>Stay connected with us on social media for the latest updates and promotions.</p>
                <div class="social-links">
                    <a href="https://www.facebook.com/people/Gsbarbershop/100063705272441/"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/gengsahak_barbershop/"><i class="fab fa-instagram"></i></a>
                </div>
                <div class="policy-link">
                    <a href="cancellation_policy.php"><i class="fas fa-file-alt"></i> Cancellation Policy</a>
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
                <p><i class="far fa-clock"></i> Saturday - Thursday: 10.30am - 6pm</p>
                <p><i class="far fa-clock"></i> Friday: Closed</p>
            </div>
        </div>
        
        <div class="copyright">
            <p>&copy; <?php echo date("Y"); ?> GS Barbershop. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>