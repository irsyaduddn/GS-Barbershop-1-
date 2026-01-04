<?php
// Start session
session_start();

// Set timezone (adjust to your location)
date_default_timezone_set('Asia/Kuala_Lumpur');

// Check if the user is logged in and is a barber
if (!isset($_SESSION['user']) || $_SESSION['usertype'] !== 'barber') {
    header("Location: login.php");
    exit();
}

// Include database connection
include("db_connect.php");

// Fetch barber information from the database
$email = $_SESSION['user'];
$sql_barber = "SELECT barber_id, barber_name FROM barbers WHERE barber_email = ?";
$stmt_barber = $conn->prepare($sql_barber);

if (!$stmt_barber) {
    die("SQL Error: " . $conn->error);
}

$stmt_barber->bind_param("s", $email);
$stmt_barber->execute();
$stmt_barber->bind_result($barber_id, $barber_name);
$stmt_barber->fetch();
$stmt_barber->close();

// Get today's date
$today = date('Y-m-d');

// Get counts for this barber
$today_appointments = $conn->query("SELECT COUNT(*) FROM appointments WHERE barber_id = $barber_id AND appointment_date = '$today' AND is_booked = 1")->fetch_row()[0];
$upcoming_appointments = $conn->query("SELECT COUNT(*) FROM appointments WHERE barber_id = $barber_id AND appointment_date > '$today' AND is_booked = 1")->fetch_row()[0];

// Get upcoming appointments for this barber (next 7 days)
$appointments_query = $conn->query("
    SELECT a.appointment_id, c.customer_name, s.service_name, a.appointment_date, a.appointment_time 
    FROM appointments a
    JOIN customers c ON a.customer_id = c.customer_id
    JOIN services s ON a.service_id = s.service_id
    WHERE a.barber_id = $barber_id
    AND a.appointment_date BETWEEN '$today' AND DATE_ADD('$today', INTERVAL 7 DAY)
    AND a.is_booked = 1
    ORDER BY a.appointment_date, a.appointment_time
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barber Dashboard - GS Barbershop</title>
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
            --secondary-color: #4e73df;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            line-height: 1.6;
            padding-top: 160px; /* Adjusted for fixed headers */
            background-color: #f8f9fc;
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
        
        .dashboard-title {
            margin-bottom: 30px;
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
        }
        
        /* Status Cards */
        .status-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .status-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 4px solid var(--secondary-color);
        }
        
        .status-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        
        .status-card.today {
            border-left-color: var(--info-color);
        }
        
        .status-card.upcoming {
            border-left-color: var(--success-color);
        }
        
        .status-card h2 {
            font-size: 16px;
            color: #5a5c69;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .status-card .count {
            font-size: 24px;
            font-weight: bold;
            color: var(--secondary-color);
        }
        
        .status-card.today .count {
            color: var(--info-color);
        }
        
        .status-card.upcoming .count {
            color: var(--success-color);
        }
        
        .status-card .icon {
            font-size: 30px;
            float: right;
            opacity: 0.3;
        }
        
        /* Date Box */
        .date-box {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border-left: 4px solid var(--primary-color);
        }
        
        .date-box h3 {
            font-size: 16px;
            color: #5a5c69;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .date-box .date {
            font-size: 20px;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .date-box .time {
            font-size: 16px;
            color: #858796;
        }
        
        /* Tables */
        .table-container {
            background-color: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .table-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 10px;
        }
        
        .table-description {
            font-size: 14px;
            color: #858796;
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e3e6f0;
        }
        
        th {
            background-color: #f8f9fc;
            font-weight: 600;
            color: #5a5c69;
        }
        
        tr:hover {
            background-color: #f8f9fc;
        }
        
        .show-all {
            display: inline-block;
            margin-top: 15px;
            color: var(--secondary-color);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .show-all:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .quick-action {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            border: 1px solid #e3e6f0;
        }
        
        .quick-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border-color: var(--primary-color);
        }
        
        .quick-action i {
            font-size: 24px;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .quick-action h3 {
            font-size: 16px;
            color: #5a5c69;
            margin-top: 10px;
            font-weight: 600;
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
        
        /* Responsive Adjustments */
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
            
            .status-cards {
                grid-template-columns: 1fr 1fr;
            }
            
            .quick-actions {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 576px) {
            .status-cards {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
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
            <img src="barberprofile.jpg" alt="Barber Profile" class="user-img">
            <div class="dropdown">
                <button>
                    <?php echo htmlspecialchars($barber_name); ?> <i class="fas fa-caret-down"></i>
                </button>
                <div class="dropdown-content">
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
                    <li class="nav-item active"><a class="nav-link" href="barber_dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="barber_appointment.php">My Appointments</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_availability.php">Manage Availability</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Dashboard Content -->
    <div class="">
       
        
        <!-- Status Cards -->
        <div class="status-cards">
            <div class="status-card today">
                <h2>Today's Appointments</h2>
                <div class="count"><?php echo $today_appointments; ?></div>
                <i class="fas fa-calendar-day icon"></i>
            </div>
            <div class="status-card upcoming">
                <h2>Upcoming Appointments</h2>
                <div class="count"><?php echo $upcoming_appointments; ?></div>
                <i class="fas fa-calendar-alt icon"></i>
            </div>
            <div class="date-box">
                <h3>Today's Date</h3>
                <div class="date"><?php echo date('F j, Y'); ?></div>
                <div class="time"><?php echo date('h:i A'); ?></div>
            </div>
        </div>
        
        <!-- Today's Appointments -->
<div class="table-container">
    <h3 class="table-title">Today's Appointments</h3>
    <p class="table-description">Here are your appointments for today, <?php echo date('F j, Y'); ?>.</p>
    
    <table>
        <thead>
            <tr>
                <th>Appointment ID</th>
                <th>Customer Name</th>
                <th>Service</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Query for today's appointments only
            $today = date('Y-m-d');
            $today_appointments_query = $conn->query("
                SELECT a.appointment_id, c.customer_name, s.service_name, a.appointment_time 
                FROM appointments a
                JOIN customers c ON a.customer_id = c.customer_id
                JOIN services s ON a.service_id = s.service_id
                WHERE a.barber_id = $barber_id
                AND a.appointment_date = '$today'
                AND a.is_booked = 1
                ORDER BY a.appointment_time
            ");
            
            while($appointment = $today_appointments_query->fetch_assoc()): ?>
            <tr>
                <td><?php echo $appointment['appointment_id']; ?></td>
                <td><?php echo $appointment['customer_name']; ?></td>
                <td><?php echo $appointment['service_name']; ?></td>
                <td><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></td>
            </tr>
            <?php endwhile; ?>
            <?php if ($today_appointments_query->num_rows == 0): ?>
            <tr>
                <td colspan="4" class="text-center">No appointments scheduled for today</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <a href="barber_appointment.php" class="show-all">Show all Appointments <i class="fas fa-arrow-right"></i></a>
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

    <script>
        // Update time every minute
        function updateTime() {
            const now = new Date();
            const timeElement = document.querySelector('.time');
            if (timeElement) {
                timeElement.textContent = now.toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        }
        
        setInterval(updateTime, 60000);
    </script>
</body>
</html>