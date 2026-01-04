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
    <title>GS Barbershop - Premium Barber Experience</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
            /* Base Styles */
            :root {
                --primary-color: #b89d5a;
                --dark-color: #222;
                --light-color: #f9f9f9;
            }
            
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                color: #333;
                line-height: 1.6;
                padding-top: 160px; /* Adjusted for fixed headers */
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
            
            /* Updated Hero Section with Sliding Background */
            .hero {
                height: 100vh;
                margin-top: -80px;
                position: relative;
                overflow: hidden;
            }

            .hero-content {
                position: relative;
                z-index: 2;
                padding: 0 10%;
                max-width: 1200px;
                margin: 0 auto;
                height: 100%;
                display: flex;
                flex-direction: column;
                justify-content: center;
                color: white;
            }

            .hero-bg {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-size: cover;
                background-position: center;
                opacity: 0;
                transition: opacity 1s ease-in-out;
                animation: slideShow 16s infinite;
            }

            /* Keyframes for the slideshow */
            @keyframes slideShow {
                0%, 100% {
                    opacity: 0;
                }
                20% {
                    opacity: 1;
                }
                80% {
                    opacity: 1;
                }
            }

            /* Individual background images with different delays */
            .hero-bg:nth-child(1) {
                background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), 
                                url('images/barbershop-hero1.jpg');
                animation-delay: 0s;
            }

            .hero-bg:nth-child(2) {
                background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), 
                                url('images/barbershop-hero2.jpg');
                animation-delay: 4s;
            }

            .hero-bg:nth-child(3) {
                background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), 
                                url('images/barbershop-hero3.jpg');
                animation-delay: 8s;
            }

            .hero-bg:nth-child(4) {
                background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), 
                                url('images/barbershop-hero4.jpg');
                animation-delay: 12s;
            }
            
            .hero h1 {
                font-size: 3.5rem;
                font-weight: 700;
                margin-bottom: 20px;
            }
            
            .hero p {
                font-size: 1.2rem;
                max-width: 600px;
                margin-bottom: 30px;
            }
            
            .btn-primary {
                background-color: var(--primary-color);
                border-color: var(--primary-color);
                padding: 12px 30px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            
            .btn-primary:hover {
                background-color: #a58a4a;
                border-color: #a58a4a;
            }
            
            /* Welcome Section - Adjusted */
            .welcome-section {
                padding: 80px 5%;
                max-width: 1400px;
                margin: 0 auto;
                text-align: center;
            }
            
            .welcome-section h2 {
                font-size: 2.5rem;
                margin-bottom: 20px;
                color: var(--dark-color);
            }
            
            .welcome-section p {
                font-size: 1.1rem;
                color: #666;
                max-width: 700px;
                margin: 0 auto 40px;
            }
            
            /* Services Preview - New */
            .services-preview {
                background-color: var(--light-color);
                padding: 80px 5%;
            }
            
            .section-title {
                text-align: center;
                margin-bottom: 50px;
            }
            
            .section-title h2 {
                font-size: 2.5rem;
                margin-bottom: 15px;
                color: var(--dark-color);
            }
            
            .section-title p {
                color: #777;
                max-width: 700px;
                margin: 0 auto;
            }
            
            .services-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 30px;
                max-width: 1200px;
                margin: 0 auto;
            }
            
            .service-card {
                background-color: white;
                padding: 30px;
                border-radius: 8px;
                text-align: center;
                transition: transform 0.3s, box-shadow 0.3s;
                box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            }
            
            .service-card:hover {
                transform: translateY(-10px);
                box-shadow: 0 15px 30px rgba(0,0,0,0.1);
            }
            
            .service-card i {
                font-size: 3rem;
                color: var(--primary-color);
                margin-bottom: 20px;
            }
            
            .service-card h3 {
                margin-bottom: 15px;
                font-size: 1.5rem;
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
            
            /* Responsive Adjustments */
            @media (max-width: 992px) {
                body {
                    padding-top: 140px;
                }
                
                .hero h1 {
                    font-size: 2.8rem;
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
                
                .hero {
                    height: 50vh;
                    margin-top: -60px;
                }
                
                .hero h1 {
                    font-size: 2.2rem;
                }
                
                .hero p {
                    font-size: 1rem;
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
                    <li class="nav-item active"><a class="nav-link" href="home.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="book_appointment.php">Book Appointment</a></li>
                    <li class="nav-item"><a class="nav-link" href="view_appointments.php">My Appointments</a></li>
                    <li class="nav-item"><a class="nav-link" href="about_us.php">About Us</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section with Sliding Background -->
    <section class="hero">
        <!-- Background images -->
        <div class="hero-bg"></div>
        <div class="hero-bg"></div>
        <div class="hero-bg"></div>
        <div class="hero-bg"></div>
        
        <!-- Content -->
        <div class="hero-content">
            <h1>PREMIUM BARBER EXPERIENCE</h1>
            <p>Traditional barbering techniques meet modern style at GS Barbershop. Book your appointment today and experience the difference.</p>
            <a href="book_appointment.php" class="btn btn-primary">BOOK NOW</a>
        </div>
    </section>

    <!-- Services Preview -->
    <section class="services-preview">
        <div class="section-title">
            <h2>OUR SERVICES</h2>
            <p>We offer a range of premium grooming services tailored to your individual style and needs.</p>
        </div>
        
        <div class="services-grid">
            <div class="service-card">
                <i class="fas fa-cut"></i>
                <h3>Classic Haircut</h3>
                <p>Precision haircut with clippers and scissors, finished with a straight razor neck shave.</p>
                <a href="book_appointment.php" class="btn btn-outline-primary mt-3">Learn More</a>
            </div>
            
            <div class="service-card">
                <i class="fas fa-spa"></i>
                <h3>Hair Treatment</h3>
                <p>Revitalizing treatments to repair damage, add shine, and restore your hair's natural health.</p>
                <a href="book_appointment.php" class="btn btn-outline-primary mt-3">Learn More</a>
            </div>
            
            <div class="service-card">
                <i class="fas fa-air-freshener"></i>
                <h3>Beard Trim</h3>
                <p>Expert beard shaping and trimming to maintain your desired look.</p>
                <a href="book_appointment.php" class="btn btn-outline-primary mt-3">Learn More</a>
            </div>
        </div>
    </section>

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
</body>
</html>