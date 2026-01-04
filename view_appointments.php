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

// Fetch customer email
$email = $_SESSION['user'];

// Fetch customer details (ID, full name, profile picture)
$customerQuery = $conn->prepare("SELECT customer_id, customer_name, customer_photo FROM customers WHERE customer_email = ?");
$customerQuery->bind_param("s", $email);
$customerQuery->execute();
$customerQuery->bind_result($customer_id, $full_name, $profile_picture);
$customerQuery->fetch();
$customerQuery->close();

// Handle appointment cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment'])) {
    $appointment_id = $_POST['appointment_id'];
    
    // Verify the appointment belongs to the current customer and is upcoming
    $verifyStmt = $conn->prepare("SELECT appointment_id FROM appointments 
                                WHERE appointment_id = ? 
                                AND customer_id = ? 
                                AND CONCAT(appointment_date, ' ', appointment_time) >= NOW()");
    $verifyStmt->bind_param("ii", $appointment_id, $customer_id);
    $verifyStmt->execute();
    $verifyStmt->store_result();
    
    if ($verifyStmt->num_rows > 0) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update appointment status to 'cancelled'
            $updateStmt = $conn->prepare("UPDATE appointments 
                             SET status = 'cancelled'
                             WHERE appointment_id = ?");
            $updateStmt->bind_param("i", $appointment_id);
            $updateStmt->execute();
            
            // Create a new available slot
            $copyStmt = $conn->prepare("
                INSERT INTO appointments (
                    service_id, barber_id, appointment_date, appointment_time, 
                    is_booked, status, created_at, variation, selected_addons
                )
                SELECT 
                    service_id, barber_id, appointment_date, appointment_time, 
                    0, 'available', NOW(), variation, selected_addons
                FROM appointments 
                WHERE appointment_id = ?
            ");
            $copyStmt->bind_param("i", $appointment_id);
            $copyStmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            $_SESSION['success_message'] = "Appointment has been cancelled successfully. A new slot has been made available.";
        } catch (Exception $e) {
            // Roll back if any error occurs
            $conn->rollback();
            $_SESSION['error_message'] = "Failed to cancel appointment: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = "Invalid appointment or you don't have permission to cancel this appointment.";
    }
    $verifyStmt->close();
    
    // Redirect to prevent form resubmission
    header("Location: view_appointments.php");
    exit();
}

// Get current date and time for filtering appointments
$current_datetime = date('Y-m-d H:i:s');

// Check which tab is active (default to upcoming)
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'upcoming';
if (!in_array($active_tab, ['upcoming', 'past', 'cancelled'])) {
    $active_tab = 'upcoming';
}

// Fetch appointments based on active tab
if ($active_tab === 'upcoming') {
    // Fetch upcoming appointments (including today's if time hasn't passed)
    $sql = "SELECT a.appointment_id, s.service_name, a.appointment_date, a.appointment_time, b.barber_name 
            FROM appointments a
            JOIN services s ON a.service_id = s.service_id
            JOIN barbers b ON a.barber_id = b.barber_id
            WHERE a.customer_id = ? 
            AND a.status = 'confirmed'
            AND CONCAT(a.appointment_date, ' ', a.appointment_time) >= ?
            ORDER BY a.appointment_date ASC, a.appointment_time ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $customer_id, $current_datetime);
} elseif ($active_tab === 'past') {
    // Fetch past appointments
    $sql = "SELECT a.appointment_id, s.service_name, a.appointment_date, a.appointment_time, b.barber_name 
            FROM appointments a
            JOIN services s ON a.service_id = s.service_id
            JOIN barbers b ON a.barber_id = b.barber_id
            WHERE a.customer_id = ? 
            AND a.status = 'completed'
            AND CONCAT(a.appointment_date, ' ', a.appointment_time) < ?
            ORDER BY a.appointment_date DESC, a.appointment_time DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $customer_id, $current_datetime);
} else {
    // Fetch cancelled appointments
    $sql = "SELECT a.appointment_id, s.service_name, a.appointment_date, a.appointment_time, b.barber_name 
            FROM appointments a
            JOIN services s ON a.service_id = s.service_id
            JOIN barbers b ON a.barber_id = b.barber_id
            WHERE a.customer_id = ? 
            AND a.status = 'cancelled'
            ORDER BY a.appointment_date DESC, a.appointment_time DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $customer_id);
}

$stmt->execute();
$result = $stmt->get_result();

// Display success/error messages from session
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GS Barbershop - My Appointments</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
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
        
        /* Hero Section - New Ojisan Style */
        .hero {
            height: 60vh;
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), 
                        url('images/barbershop-hero.jpg') no-repeat center center;
            background-size: cover;
            display: flex;
            align-items: center;
            color: white;
            margin-top: -80px; /* Compensate for fixed nav */
        }
        
        .hero-content {
            padding: 0 10%;
            max-width: 1200px;
            margin: 0 auto;
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
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
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
        
        /* Appointment Tabs */
        .appointment-tabs {
            margin-bottom: 30px;
            border-bottom: 1px solid #ddd;
        }
        
        .appointment-tabs .nav-link {
            color: #555;
            font-weight: 500;
            padding: 10px 20px;
            border: none;
            border-bottom: 3px solid transparent;
        }
        
        .appointment-tabs .nav-link.active {
            color: var(--primary-color);
            border-bottom: 3px solid var(--primary-color);
            background-color: transparent;
        }
        
        .appointment-tabs .nav-link:hover:not(.active) {
            color: var(--dark-color);
            border-bottom: 3px solid #ddd;
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
        
        /* Additional styles for cancel button and modal */
        .btn-cancel {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .btn-cancel:hover {
            background-color: #c82333;
            color: white;
        }
        
        .confirmation-modal .modal-header {
            background-color: var(--primary-color);
            color: white;
        }
        
        .confirmation-modal .modal-footer .btn-confirm {
            background-color: #dc3545;
            color: white;
        }
        
        .confirmation-modal .modal-footer .btn-confirm:hover {
            background-color: #c82333;
        }
        .appointment-tabs .nav-link.active {
            color: #b89d5a;
            border-bottom: 3px solid #b89d5a;
        }
        .btn-cancel {
            background-color: #dc3545;
            color: white;
        }
        .btn-cancel:hover {
            background-color: #c82333;
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
    <div class="container mt-4">
        <h1>My Appointments</h1>
        
        <!-- Display success/error messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php elseif (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <!-- Appointment Tabs -->
        <ul class="nav nav-tabs appointment-tabs" id="appointmentTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab === 'upcoming' ? 'active' : ''; ?>" 
                   href="?tab=upcoming">Upcoming Appointments</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab === 'past' ? 'active' : ''; ?>" 
                   href="?tab=past">Past Appointments</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab === 'cancelled' ? 'active' : ''; ?>" 
                   href="?tab=cancelled">Cancelled Appointments</a>
            </li>
        </ul>
        
        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive mt-3">
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>No.</th>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Barber</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $count = 1; while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['appointment_time']); ?></td>
                                <td><?php echo htmlspecialchars($row['barber_name']); ?></td>
                                <td>
                                    <a href="bookedappointment_details.php?id=<?php echo $row['appointment_id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                
                                <?php if ($active_tab === 'upcoming'): ?>
                                    
                                        <button class="btn btn-cancel btn-sm" 
                                                data-toggle="modal" 
                                                data-target="#cancelModal" 
                                                data-appointment-id="<?php echo $row['appointment_id']; ?>">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info mt-3">
                <?php 
                switch ($active_tab) {
                    case 'upcoming':
                        echo 'You have no upcoming appointments.';
                        break;
                    case 'past':
                        echo 'You have no past appointments.';
                        break;
                    case 'cancelled':
                        echo 'You have no cancelled appointments.';
                        break;
                }
                ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Cancel Appointment Confirmation Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1" role="dialog" aria-labelledby="cancelModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="cancelModalLabel">Confirm Cancellation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel this appointment?</p>
                    <p><strong>This action cannot be undone.</strong></p>
                </div>
                <div class="modal-footer">
                    <form id="cancelForm" method="POST" action="">
                        <input type="hidden" name="appointment_id" id="appointmentIdInput">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="cancel_appointment" class="btn btn-danger">Confirm Cancellation</button>
                    </form>
                </div>
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
        // Set the appointment ID in the modal form when cancel button is clicked
        $('#cancelModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var appointmentId = button.data('appointment-id');
            var modal = $(this);
            modal.find('#appointmentIdInput').val(appointmentId);
        });
    </script>
</body>
</html>