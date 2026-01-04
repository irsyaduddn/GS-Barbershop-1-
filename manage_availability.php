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

// Include database connection and PHPMailer
include("db_connect.php");

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

// Fetch barber information
$email = $_SESSION['user'];
$sql_barber = "SELECT barber_id, barber_name FROM barbers WHERE barber_email = ?";
$stmt_barber = $conn->prepare($sql_barber);
if ($stmt_barber === false) {
    die("Error preparing barber query: " . $conn->error);
}
$stmt_barber->bind_param("s", $email);
$stmt_barber->execute();
$stmt_barber->bind_result($barber_id, $barber_name);
$stmt_barber->fetch();
$stmt_barber->close();

// Handle day deletion
if (isset($_GET['delete_day'])) {
    $delete_date = $_GET['delete_day'];
    
    // First, get all booked appointments for this day to notify customers
    $sql_booked = "SELECT a.appointment_id, c.customer_email, c.customer_name, a.appointment_time, s.service_name 
                   FROM appointments a
                   JOIN customers c ON a.customer_id = c.customer_id
                   JOIN services s ON a.service_id = s.service_id
                   WHERE a.barber_id = ? AND a.appointment_date = ? AND a.is_booked = 1";
    $stmt_booked = $conn->prepare($sql_booked);
    $stmt_booked->bind_param("is", $barber_id, $delete_date);
    $stmt_booked->execute();
    $result_booked = $stmt_booked->get_result();
    
    $booked_appointments = [];
    while ($row = $result_booked->fetch_assoc()) {
        $booked_appointments[] = $row;
    }
    $stmt_booked->close();
    
    // Delete all appointments for this day (both booked and available)
    $sql_delete = "DELETE FROM appointments WHERE barber_id = ? AND appointment_date = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("is", $barber_id, $delete_date);
    
    if ($stmt_delete->execute()) {
        $message = "<div class='alert alert-success'>All time slots for $delete_date have been deleted successfully!</div>";
        
        // Send cancellation emails to customers with booked appointments
        if (!empty($booked_appointments)) {
            $mail = new PHPMailer(true);
            
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com'; // Your SMTP server
                $mail->SMTPAuth   = true;
                $mail->Username   = 'irsyaduddn@gmail.com'; // SMTP username
                $mail->Password   = 'mvap bryp xrxf knxk'; // SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                
                // Sender info
                $mail->setFrom('irsyaduddn@gmail.com', 'GS Barbershop');
                $mail->addReplyTo('irsyaduddn@gmail.com', 'GS Barbershop Info');
                
                foreach ($booked_appointments as $appointment) {
                    $customer_email = $appointment['customer_email'];
                    $customer_name = $appointment['customer_name'];
                    $appointment_time = date('h:i A', strtotime($appointment['appointment_time']));
                    $service_name = $appointment['service_name'];
                    
                    // Recipient
                    $mail->addAddress($customer_email, $customer_name);
                    
                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Appointment Cancellation Notification';
                    $mail->Body    = "
                        <p>Dear $customer_name,</p>
                        <p>We regret to inform you that your appointment for <strong>$service_name</strong> on <strong>$delete_date at $appointment_time</strong> has been canceled by the barber.</p>
                        <p>We apologize for any inconvenience this may cause. Please feel free to book a new appointment at your convenience.</p>
                        <p>Thank you for your understanding.</p>
                        <p>Best regards,<br>GS Barbershop Team</p>
                    ";
                    $mail->AltBody = "Dear $customer_name,\n\nYour appointment for $service_name on $delete_date at $appointment_time has been canceled by the barber.\n\nWe apologize for any inconvenience. Please book a new appointment.\n\nBest regards,\nGS Barbershop Team";
                    
                    $mail->send();
                    $mail->clearAddresses();
                }
                
            } catch (Exception $e) {
                // Log error but don't show to user
                error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            }
        }
        
        // Refresh to show updated list
        header("Refresh:0");
    } else {
        $message = "<div class='alert alert-danger'>Failed to delete time slots. Please try again.</div>";
    }
    $stmt_delete->close();
}

// Handle single appointment deletion
if (isset($_GET['delete'])) {
    $appointment_id = $_GET['delete'];
    
    // Check if this is a booked appointment
    $sql_check = "SELECT a.is_booked, c.customer_email, c.customer_name, a.appointment_date, a.appointment_time, s.service_name 
                  FROM appointments a
                  LEFT JOIN customers c ON a.customer_id = c.customer_id
                  JOIN services s ON a.service_id = s.service_id
                  WHERE a.appointment_id = ? AND a.barber_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $appointment_id, $barber_id);
    $stmt_check->execute();
    $stmt_check->bind_result($is_booked, $customer_email, $customer_name, $appointment_date, $appointment_time, $service_name);
    $stmt_check->fetch();
    $stmt_check->close();
    
    // Delete the appointment
    $sql_delete = "DELETE FROM appointments WHERE appointment_id = ? AND barber_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("ii", $appointment_id, $barber_id);
    
    if ($stmt_delete->execute()) {
        $message = "<div class='alert alert-success'>Appointment slot deleted successfully!</div>";
        
        // If it was a booked appointment, send cancellation email
        if ($is_booked && !empty($customer_email)) {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp@gmail.com'; // Your SMTP server
                $mail->SMTPAuth   = true;
                $mail->Username   = 'irsyaduddn@gmail.com'; // SMTP username
                $mail->Password   = 'mvap bryp xrxf knxk'; // SMTP password
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                
                // Sender info
                $mail->setFrom('irsyaduddn@gmail.com', 'GS Barbershop');
                $mail->addReplyTo('irsyaduddn@gmail.com', 'GS Barbershop');
                
                // Recipient
                $mail->addAddress($customer_email, $customer_name);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Appointment Cancellation Notification';
                $mail->Body    = "
                    <p>Dear $customer_name,</p>
                    <p>We regret to inform you that your appointment for <strong>$service_name</strong> on <strong>$appointment_date at " . date('h:i A', strtotime($appointment_time)) . "</strong> has been canceled by the barber.</p>
                    <p>We apologize for any inconvenience this may cause. Please feel free to book a new appointment at your convenience.</p>
                    <p>Thank you for your understanding.</p>
                    <p>Best regards,<br>GS Barbershop Team</p>
                ";
                $mail->AltBody = "Dear $customer_name,\n\nYour appointment for $service_name on $appointment_date at " . date('h:i A', strtotime($appointment_time)) . " has been canceled by the barber.\n\nWe apologize for any inconvenience. Please book a new appointment.\n\nBest regards,\nGS Barbershop Team";
                
                $mail->send();
                
            } catch (Exception $e) {
                // Log error but don't show to user
                error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            }
        }
        
        // Refresh to show updated list
        header("Refresh:0");
    } else {
        $message = "<div class='alert alert-danger'>Failed to delete appointment slot. Please try again.</div>";
    }
    $stmt_delete->close();
}

// Get current date for filtering
$current_date = date('Y-m-d');
$active_tab = isset($_GET['tab']) && $_GET['tab'] === 'past' ? 'past' : 'upcoming';

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Fetch total number of records for pagination
if ($active_tab === 'upcoming') {
    $sql_count = "SELECT COUNT(*) FROM appointments WHERE barber_id = ? AND appointment_date >= ?";
} else {
    $sql_count = "SELECT COUNT(*) FROM appointments WHERE barber_id = ? AND appointment_date < ?";
}

$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param("is", $barber_id, $current_date);
$stmt_count->execute();
$stmt_count->bind_result($total_records);
$stmt_count->fetch();
$stmt_count->close();

$total_pages = ceil($total_records / $records_per_page);

// Fetch appointments based on active tab with pagination
if ($active_tab === 'upcoming') {
    // Fetch upcoming availability slots (including today's)
    $sql_appointments = "
        SELECT a.appointment_id, a.appointment_date, a.appointment_time, s.service_name, a.is_booked
        FROM appointments a
        JOIN services s ON a.service_id = s.service_id
        WHERE a.barber_id = ? AND a.appointment_date >= ?
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
        LIMIT ? OFFSET ?
    ";
} else {
    // Fetch past availability slots
    $sql_appointments = "
        SELECT a.appointment_id, a.appointment_date, a.appointment_time, s.service_name, a.is_booked
        FROM appointments a
        JOIN services s ON a.service_id = s.service_id
        WHERE a.barber_id = ? AND a.appointment_date < ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
        LIMIT ? OFFSET ?
    ";
}

$stmt_appointments = $conn->prepare($sql_appointments);
if ($stmt_appointments === false) {
    die("Error preparing appointment query: " . $conn->error);
}
$stmt_appointments->bind_param("isii", $barber_id, $current_date, $records_per_page, $offset);
if (!$stmt_appointments->execute()) {
    die("Error executing appointment query: " . $stmt_appointments->error);
}
$result_appointments = $stmt_appointments->get_result();

// Get unique dates for the date dropdown (only for upcoming tab)
if ($active_tab === 'upcoming') {
    $sql_dates = "
        SELECT DISTINCT appointment_date 
        FROM appointments 
        WHERE barber_id = ? AND appointment_date >= ? 
        ORDER BY appointment_date ASC
    ";
    $stmt_dates = $conn->prepare($sql_dates);
    $stmt_dates->bind_param("is", $barber_id, $current_date);
    $stmt_dates->execute();
    $result_dates = $stmt_dates->get_result();
    $available_dates = [];
    while ($row = $result_dates->fetch_assoc()) {
        $available_dates[] = $row['appointment_date'];
    }
    $stmt_dates->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Availability - GS Barbershop</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
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
        
        /* Button Styles */
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 8px 20px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background-color: #a58a4a;
            border-color: #a58a4a;
        }
        
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        
        /* Table Styles */
        .table th {
            background-color: var(--dark-color);
            color: white;
        }
        
        /* Highlight today's appointments */
        .today-appointment {
            background-color: #fff9e6;
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
        
        /* Add this new style for the delete day section */
        .delete-day-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
        }
        
        .booked-slot {
            background-color: #ffecec;
        }
        
        /* Pagination styles */
        .pagination {
            justify-content: center;
            margin-top: 20px;
        }
        
        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .page-link {
            color: var(--primary-color);
        }
        
        .page-link:hover {
            color: var(--dark-color);
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
            <img src="barberprofile.jpg" alt="Barber" class="user-img">
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
                    <li class="nav-item"><a class="nav-link" href="barber_dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="barber_appointment.php">My Appointments</a></li>
                    <li class="nav-item active"><a class="nav-link" href="manage_availability.php">Manage Availability</a></li>
                </ul>
            </div>
        </div>
    </nav>


    <!-- Delete Slot Confirmation Modal -->
<div class="modal fade" id="deleteSlotModal" tabindex="-1" role="dialog" aria-labelledby="deleteSlotModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteSlotModalLabel">Confirm Deletion</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this time slot? <span id="bookedWarning" class="text-danger font-weight-bold"></span>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <a id="confirmDeleteSlot" href="#" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<!-- Delete Day Confirmation Modal -->
<div class="modal fade" id="deleteDayModal" tabindex="-1" role="dialog" aria-labelledby="deleteDayModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteDayModalLabel">Confirm Day Off</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete ALL time slots for <span id="dayToDelete" class="font-weight-bold"></span>? 
                <p class="text-danger mt-2">This cannot be undone and any booked appointments will be canceled.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form id="deleteDayForm" method="get" action="manage_availability.php" style="display: inline;">
                    <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
                    <input type="hidden" id="deleteDayInput" name="delete_day" value="">
                    <button type="submit" class="btn btn-danger">Take Day Off</button>
                </form>
            </div>
        </div>
    </div>
</div>
    <!-- Main Content -->
    <div class="container">
        <h1 class="mb-4">Manage Availability</h1>

        <?php if (isset($message)) echo $message; ?>

        <!-- Delete Day Section (only for upcoming tab) -->
        <?php if ($active_tab === 'upcoming' && !empty($available_dates)): ?>
        <div class="delete-day-section mb-4">
            <h4>Day Off</h4>
            <form method="get" action="manage_availability.php" class="form-inline">
                <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
                <div class="form-group mr-2">
                    <select name="delete_day" class="form-control" required>
                        <option value="">Select a date</option>
                        <?php foreach ($available_dates as $date): ?>
                            <option value="<?php echo $date; ?>"><?php echo $date; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
<button type="button" class="btn btn-danger" id="takeDayOffBtn" data-toggle="modal" data-target="#deleteDayModal">
    <i class="fas fa-calendar-times"></i> Take Day Off
</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Availability Tabs -->
        <ul class="nav nav-tabs appointment-tabs" id="availabilityTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab === 'upcoming' ? 'active' : ''; ?>" 
                   href="?tab=upcoming">Upcoming Availability</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab === 'past' ? 'active' : ''; ?>" 
                   href="?tab=past">Past Availability</a>
            </li>
        </ul>

        <!-- Availability List Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Service Name</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <?php if ($active_tab === 'upcoming'): ?>
                            <th>Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_appointments->num_rows > 0): ?>
                        <?php 
                        $count = ($page - 1) * $records_per_page + 1;
                        $today = date('Y-m-d');
                        while ($row = $result_appointments->fetch_assoc()): 
                            $isToday = ($row['appointment_date'] == $today);
                            $isBooked = $row['is_booked'];
                        ?>
                            <tr class="<?php echo $isToday ? 'today-appointment' : ''; ?> <?php echo $isBooked ? 'booked-slot' : ''; ?>">
                                <td><?php echo $count++; ?></td>
                                <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                                <td><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></td>
                                <td><?php echo $isBooked ? 'Booked' : 'Available'; ?></td>
                                <?php if ($active_tab === 'upcoming'): ?>
                                    <td>
                                        <a href="#" class="btn btn-danger btn-sm delete-slot-btn" 
   data-appointment-id="<?php echo $row['appointment_id']; ?>"
   data-is-booked="<?php echo $isBooked ? '1' : '0'; ?>">
    <i class="fas fa-trash-alt"></i> Delete
</a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo $active_tab === 'upcoming' ? '6' : '5'; ?>" class="text-center">
                                <?php echo $active_tab === 'upcoming' 
                                    ? 'No upcoming availability slots found.' 
                                    : 'No past availability slots found.'; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?tab=<?php echo $active_tab; ?>&page=<?php echo $page - 1; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?tab=<?php echo $active_tab; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?tab=<?php echo $active_tab; ?>&page=<?php echo $page + 1; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
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
        // Highlight today's date in the table
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.querySelectorAll('td:nth-child(3)').forEach(cell => {
                if (cell.textContent === today) {
                    cell.closest('tr').classList.add('today-appointment');
                }
            });
        });

        // Delete Slot Modal Handling
    $(document).ready(function() {
        $('.delete-slot-btn').click(function(e) {
            e.preventDefault();
            var appointmentId = $(this).data('appointment-id');
            var isBooked = $(this).data('is-booked');
            
            // Set the warning message if it's a booked slot
            if (isBooked == '1') {
                $('#bookedWarning').text('The customer will be notified of cancellation.');
            } else {
                $('#bookedWarning').text('');
            }
            
            // Set the delete link
            $('#confirmDeleteSlot').attr('href', '?delete=' + appointmentId + '&tab=<?php echo $active_tab; ?>');
            
            // Show the modal
            $('#deleteSlotModal').modal('show');
        });
        
        // Delete Day Modal Handling
        $('#takeDayOffBtn').click(function() {
            var selectedDate = $('select[name="delete_day"]').val();
            if (!selectedDate) {
                alert('Please select a date first.');
                return false;
            }
            
            $('#dayToDelete').text(selectedDate);
            $('#deleteDayInput').val(selectedDate);
            $('#deleteDayModal').modal('show');
        });
        
        // Highlight today's date in the table
        const today = new Date().toISOString().split('T')[0];
        document.querySelectorAll('td:nth-child(3)').forEach(cell => {
            if (cell.textContent === today) {
                cell.closest('tr').classList.add('today-appointment');
            }
        });
    });
    </script>
</body>
</html>