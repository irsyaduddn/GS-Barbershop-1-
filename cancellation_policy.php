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

// Fetch user information from the database
$email = $_SESSION['user'];
$sql_user = "SELECT customer_name, customer_photo FROM customers WHERE customer_email = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("s", $email);
$stmt_user->execute();
$stmt_user->bind_result($full_name, $profile_picture);
$stmt_user->fetch();
$stmt_user->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancellation Policy - GS Barbershop</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #b89d5a;
            --primary-light: #d4c08e;
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
        
        /* Policy Container */
        .policy-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .policy-hero {
            position: relative;
            height: 250px;
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('policy-hero.jpg') center/cover no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
        }
        
        .policy-hero-content {
            max-width: 800px;
            padding: 0 20px;
            animation: fadeInUp 0.8s ease;
        }
        
        .policy-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        .policy-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .policy-content {
            padding: 50px;
        }
        
        .section-title {
            font-size: 1.8rem;
            color: var(--dark-color);
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background-color: var(--primary-color);
        }
        
        .policy-description {
            font-size: 1.1rem;
            color: #555;
            line-height: 1.8;
            margin-bottom: 25px;
        }
        
        .policy-highlight {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .policy-points {
            margin: 30px 0;
        }
        
        .policy-item {
            display: flex;
            margin-bottom: 25px;
            padding: 20px;
            border-radius: 8px;
            background-color: #f9f9f9;
            transition: var(--transition);
        }
        
        .policy-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .policy-icon {
            font-size: 1.5rem;
            margin-right: 20px;
            flex-shrink: 0;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .check-icon {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .cross-icon {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .policy-item-content {
            flex-grow: 1;
        }
        
        .policy-item-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark-color);
        }
        
        .policy-item-desc {
            color: #666;
            line-height: 1.6;
        }
        
        .contact-box {
            background-color: var(--primary-light);
            padding: 30px;
            border-radius: 8px;
            margin-top: 40px;
            color: white;
            text-align: center;
        }
        
        .contact-title {
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
        
        .contact-link {
            color: white;
            font-weight: 500;
            text-decoration: underline;
            transition: var(--transition);
        }
        
        .contact-link:hover {
            color: var(--dark-color);
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
        
        /* Animations */
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
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            body {
                padding-top: 140px;
            }
            
            .policy-content {
                padding: 40px;
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
            
            .policy-title {
                font-size: 2rem;
            }
            
            .policy-content {
                padding: 30px 20px;
            }
            
            .policy-item {
                flex-direction: column;
            }
            
            .policy-icon {
                margin-right: 0;
                margin-bottom: 15px;
            }
        }
        
        @media (max-width: 576px) {
            .policy-title {
                font-size: 1.8rem;
            }
            
            .section-title {
                font-size: 1.5rem;
            }
            
            .policy-hero {
                height: 200px;
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
                    <li class="nav-item"><a class="nav-link" href="view_appointments.php">My Appointments</a></li>
                    <li class="nav-item"><a class="nav-link" href="about_us.php">About Us</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Policy Content Section -->
    <div class="policy-container">
        <div class="policy-hero">
            <div class="policy-hero-content">
                <h1 class="policy-title">Cancellation Policy</h1>
                <p class="policy-subtitle">Understand our policies for appointment changes and refunds</p>
            </div>
        </div>
        
        <div class="policy-content">
            <h2 class="section-title">Our Cancellation Policy</h2>
            
            <p class="policy-description">
                At <span class="policy-highlight">GS Barbershop</span>, we value both your time and our barbers' time. To ensure fairness to all our customers and maintain the highest quality service, we have established the following cancellation policy:
            </p>
            
            <div class="policy-points">
                <div class="policy-item">
                    <div class="policy-icon check-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="policy-item-content">
                        <h3 class="policy-item-title">Flexible Cancellations (24+ hours notice)</h3>
                        <p class="policy-item-desc">
                            If you need to cancel or reschedule your appointment, we kindly ask for at least 24 hours notice. Cancellations made more than 24 hours before your scheduled appointment will receive a full refund of any deposit paid.
                        </p>
                    </div>
                </div>
                
                <div class="policy-item">
                    <div class="policy-icon cross-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="policy-item-content">
                        <h3 class="policy-item-title">Late Cancellations (Less than 12 hours notice)</h3>
                        <p class="policy-item-desc">
                            Cancellations made less than 12 hours before your appointment time will result in forfeiture of your deposit. This helps us compensate our barbers for the time reserved for your appointment.
                        </p>
                    </div>
                </div>
                
                <div class="policy-item">
                    <div class="policy-icon cross-icon">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <div class="policy-item-content">
                        <h3 class="policy-item-title">No-Shows</h3>
                        <p class="policy-item-desc">
                            Clients who miss their appointment without prior notice will be charged the full amount of the scheduled service and may be required to pay a deposit for future bookings.
                        </p>
                    </div>
                </div>
            </div>
            
            <p class="policy-description">
                We understand that emergencies happen. If you have a genuine emergency, please contact us as soon as possible and we'll do our best to accommodate your situation.
            </p>
            
            <div class="contact-box">
                <h3 class="contact-title">Need to cancel or reschedule?</h3>
                <p>Contact us at <a href="mailto:amilsahak8@gmail.com" class="contact-link">amilsahak8@gmail.com</a> or call <a href="tel:+60133248962" class="contact-link">(+60) 13-3248962</a></p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-column">
                <h3>FOLLOW US</h3>
                <p>Stay connected with us on social media for the latest updates and promotions.</p>
                <div class="social-links mt-4">
                    <a href="https://www.facebook.com/people/Gsbarbershop/100063705272441/"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/gengsahak_barbershop/"><i class="fab fa-instagram"></i></a>  
                </div>
                <div class="policy-link mt-3">
                    <a href="cancellation_policy.php" class="text-decoration-none">Cancellation Policy</a>
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
                <p>Public Holidays: 11am - 4pm</p>
            </div>
        </div>
        
        <div class="copyright">
            <p>&copy; <?php echo date("Y"); ?> GS Barbershop. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html>