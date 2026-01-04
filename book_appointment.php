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

// Fetch all services
$sql_services = "SELECT * FROM services";
$services_result = $conn->query($sql_services);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GS Barbershop - Book Appointment</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
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
        
        /* Main Content - Enhanced */
        .page-header {
            text-align: center;
            margin-bottom: 60px;
            position: relative;
        }
        
        .page-header h1 {
            font-weight: 700;
            color: var(--dark-color);
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .page-header h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: var(--primary-color);
        }
        
        .page-header p {
            color: #6c757d;
            max-width: 700px;
            margin: 0 auto;
            font-size: 1.1rem;
        }
        
        /* Service Cards - Premium Design */
        .service-card {
            border: none;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: var(--transition);
            box-shadow: var(--shadow);
            background-color: white;
            overflow: hidden;
            position: relative;
            z-index: 1;
        }
        
        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background-color: var(--primary-color);
            transition: var(--transition);
        }
        
        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.12);
        }
        
        .service-card:hover::before {
            height: 10px;
        }
        
        .service-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 15px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .service-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 2px;
            background-color: var(--primary-color);
        }
        
        .service-price {
            font-size: 1.2rem;
            color:rgb(5, 5, 5);
            font-weight: bold;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .service-price::before {
            content: 'RM';
            font-size: 0.9rem;
            margin-right: 5px;
            color: #6c757d;
        }
        
        .service-description {
            color: #6c757d;
            flex-grow: 1;
            margin-bottom: 20px;
            line-height: 1.7;
        }
        
        .btn-book {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            font-weight: 600;
            align-self: flex-start;
            padding: 10px 25px;
            border-radius: 30px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            z-index: 1;
            border: none;
            box-shadow: 0 4px 15px rgba(184, 157, 90, 0.3);
            letter-spacing: 0.5px;
        }
        
        .btn-book::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: var(--transition);
            z-index: -1;
        }
        
        .btn-book:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(184, 157, 90, 0.4);
        }
        
        .btn-book:hover::before {
            left: 100%;
        }
        
        /* Featured Service - Highlight */
        .featured-service {
            border: 2px solid var(--primary-color);
            position: relative;
        }
        
        .featured-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: var(--primary-color);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            animation: pulse 2s infinite;
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
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        /* Responsive Adjustments */
        @media (max-width: 992px) {
            body {
                padding-top: 140px;
            }
            
            .navbar {
                top: 70px;
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
            
            .page-header h1 {
                font-size: 1.8rem;
            }
        }
        
        @media (max-width: 576px) {
            .service-card {
                padding: 20px;
            }
            
            .btn-book {
                width: 100%;
                text-align: center;
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
                    <li class="nav-item active"><a class="nav-link" href="book_appointment.php">Book Appointment</a></li>
                    <li class="nav-item"><a class="nav-link" href="view_appointments.php">My Appointments</a></li>
                    <li class="nav-item"><a class="nav-link" href="about_us.php">About Us</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-5">
        <div class="page-header animate__animated animate__fadeIn">
            <h1>Book an Appointment</h1>
            <p>Choose from our premium grooming services tailored to give you the perfect look. Our barbers are experts in modern and classic styles.</p>
        </div>
        
        <div class="row">
            <?php 
            $counter = 0;
            while ($service = $services_result->fetch_assoc()): 
                $counter++;
                $is_featured = ($counter % 3 == 1); // Every 3rd service is featured
            ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card animate__animated animate__fadeInUp" style="animation-delay: <?php echo ($counter * 0.1); ?>s">
                        <?php if ($is_featured): ?>
                            <div class="featured-badge">POPULAR</div>
                        <?php endif; ?>
                        <div class="service-title"><?php echo htmlspecialchars($service['service_name']); ?></div>
                        <div class="service-price"><?php echo number_format($service['service_price'], 2); ?></div>
                        <?php if (!empty($service['description'])): ?>
                            <div class="service-description"><?php echo htmlspecialchars($service['description']); ?></div>
                        <?php endif; ?>
                        <form action="appointment_details.php" method="POST">
                            <input type="hidden" name="service_id" value="<?php echo $service['service_id']; ?>">
                            <button type="submit" class="btn btn-book">
                                <i class="far fa-calendar-alt mr-2"></i> BOOK NOW
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="animate__animated animate__fadeIn">
        <div class="footer-container">
            <div class="footer-column">
                <h3>FOLLOW US</h3>
                <p>Stay connected with us on social media for the latest updates and promotions.</p>
                <div class="social-links">
                    <a href="https://www.facebook.com/people/Gsbarbershop/100063705272441/" target="_blank"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/gengsahak_barbershop/" target="_blank"><i class="fab fa-instagram"></i></a>
                </div>
                <div class="policy-link">
                    <a href="cancellation_policy.php" class="text-decoration-none">
                        <i class="fas fa-file-alt mr-2"></i> Cancellation Policy
                    </a>
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
                <p><strong>Saturday - Thursday:</strong> 10:30am - 6:00pm</p>
                <p><strong>Friday:</strong> Closed</p>
            </div>
        </div>
        
        <div class="copyright">
            <p>&copy; <?php echo date("Y"); ?> GS Barbershop. All Rights Reserved.</p>
        </div>
    </footer>

    <script> 
        // Smooth scroll for anchor links
        $('a[href*="#"]').on('click', function(e) {
            e.preventDefault();
            $('html, body').animate(
                {
                    scrollTop: $($(this).attr('href')).offset().top - 100,
                },
                500,
                'linear'
            );
        });
    </script>
</body>
</html>