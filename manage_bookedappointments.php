<?php
// Start session
session_start();

// Set timezone (adjust to your location)
date_default_timezone_set('Asia/Kuala_Lumpur');

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['usertype'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Include database connection
include("db_connect.php");

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

// Initialize variables for messages
$message = "";

// Debugging: Output received GET parameters
error_log("GET Parameters: " . print_r($_GET, true));

// Get current date for filtering appointments
$current_date = date('Y-m-d');

// Check which tab is active (default to upcoming)
$active_tab = isset($_GET['tab']) && $_GET['tab'] === 'past' ? 'past' : 'upcoming';

// Get search parameters
$search_date = isset($_GET['search_date']) ? $_GET['search_date'] : null;
$search_customer = isset($_GET['search_customer']) ? trim($_GET['search_customer']) : null;

// Pagination settings
$rows_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $rows_per_page;

// Debugging: Output search parameters
error_log("Search Date: $search_date");
error_log("Search Customer: $search_customer");

// Base SQL query
$sql = "SELECT a.appointment_id, c.customer_name, b.barber_name, s.service_name, 
               a.appointment_date, a.appointment_time, a.status, a.variation,
               GROUP_CONCAT(asv.service_name SEPARATOR ', ') as addon_names
        FROM appointments a
        INNER JOIN customers c ON a.customer_id = c.customer_id
        INNER JOIN barbers b ON a.barber_id = b.barber_id
        INNER JOIN services s ON a.service_id = s.service_id
        LEFT JOIN addon_services asv ON FIND_IN_SET(asv.addon_id, a.selected_addons)";

// Initialize conditions and parameters
$conditions = [];
$params = [];
$types = "";

// Add base condition based on active tab
if ($active_tab === 'upcoming') {
    $conditions[] = "a.appointment_date >= ? AND a.status != 'Completed'";
    $params[] = $current_date;
    $types .= "s";
} else {
    $conditions[] = "(a.appointment_date < ? OR a.status = 'Completed')";
    $params[] = $current_date;
    $types .= "s";
}

// Add search conditions if provided
if (!empty($search_date)) {
    $conditions[] = "a.appointment_date = ?";
    $params[] = $search_date;
    $types .= "s";
}

if (!empty($search_customer)) {
    $conditions[] = "c.customer_name LIKE ?";
    $params[] = "%$search_customer%";
    $types .= "s";
}

// Combine all conditions
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

// Add grouping
$sql .= " GROUP BY a.appointment_id";

// Get total count for pagination (remove LIMIT and OFFSET)
$count_sql = str_replace("LIMIT ? OFFSET ?", "", $sql);
$count_stmt = $conn->prepare($count_sql);

// Only bind parameters if we have them
$count_params = [];
$count_types = "";
if ($active_tab === 'upcoming') {
    $count_params[] = $current_date;
    $count_types .= "s";
} else {
    $count_params[] = $current_date;
    $count_types .= "s";
}

if (!empty($search_date)) {
    $count_params[] = $search_date;
    $count_types .= "s";
}
if (!empty($search_customer)) {
    $count_params[] = "%$search_customer%";
    $count_types .= "s";
}

if (!empty($count_types)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$total_result = $count_stmt->get_result();
$total_rows = $total_result->num_rows;
$total_pages = ceil($total_rows / $rows_per_page);

// Add ordering and pagination
if ($active_tab === 'upcoming') {
    $sql .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";
} else {
    $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
}

// Add pagination
$sql .= " LIMIT ? OFFSET ?";
$params[] = $rows_per_page;
$params[] = $offset;
$types .= "ii";

// Debugging: Output final SQL query
error_log("Final SQL Query: $sql");
error_log("Parameters: " . print_r($params, true));
error_log("Types: $types");

// Prepare and execute the statement
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    error_log("Prepare failed: " . $conn->error);
    die("Database error. Please try again later.");
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

if (!$stmt->execute()) {
    error_log("Execute failed: " . $stmt->error);
    die("Database error. Please try again later.");
}

$result = $stmt->get_result();

// Handle appointment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $appointment_id = $_POST['appointment_id'];
    $status = $_POST['status'];

    // First check if the appointment is already cancelled
    $check_sql = "SELECT status FROM appointments WHERE appointment_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $appointment_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $appointment = $check_result->fetch_assoc();
        if ($appointment['status'] === 'Cancelled') {
            $message = "<div class='alert alert-danger'>Cannot update a cancelled appointment.</div>";
        } else {
            // Proceed with update
            $update_sql = "UPDATE appointments SET status = ? WHERE appointment_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $status, $appointment_id);

            if ($update_stmt->execute()) {
                $message = "<div class='alert alert-success'>Appointment status updated successfully!</div>";
                // Redirect with current search parameters
                $redirect_url = "manage_bookedappointments.php?tab=$active_tab";
                if (!empty($search_date)) $redirect_url .= "&search_date=$search_date";
                if (!empty($search_customer)) $redirect_url .= "&search_customer=" . urlencode($search_customer);
                if (isset($_GET['page'])) $redirect_url .= "&page=" . $_GET['page'];
                header("Location: $redirect_url");
                exit();
            } else {
                $message = "<div class='alert alert-danger'>Failed to update appointment status. Please try again.</div>";
            }
            $update_stmt->close();
        }
    } else {
        $message = "<div class='alert alert-danger'>Appointment not found.</div>";
    }
    $check_stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reminder'])) {

    // Get all appointments for today
    $today = date('Y-m-d');
    $reminder_sql = "SELECT a.appointment_id, a.appointment_time, c.customer_name, c.customer_email, s.service_name
                 FROM appointments a
                 JOIN customers c ON a.customer_id = c.customer_id
                 JOIN services s ON a.service_id = s.service_id
                 WHERE a.appointment_date = ? AND a.status != 'Cancelled'";
    $reminder_stmt = $conn->prepare($reminder_sql);
if (!$reminder_stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}
    $reminder_stmt->bind_param("s", $today);
    $reminder_stmt->execute();
    $reminder_result = $reminder_stmt->get_result();

    $emails_sent = 0;

    while ($row = $reminder_result->fetch_assoc()) {
        $mail = new PHPMailer(true);

        try {
            // SMTP Settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';       // Replace with your SMTP server
            $mail->SMTPAuth = true;
            $mail->Username = 'irsyaduddn@gmail.com';     // Your SMTP username
            $mail->Password = 'mvap bryp xrxf knxk';        // Your SMTP password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            // Email Content
            $mail->setFrom('irsyaduddn@gmail.com', 'GS Barbershop');
            $mail->addAddress($row['customer_email'], $row['customer_name']);

            $mail->Subject = 'Appointment Reminder - GS Barbershop';
            $mail->Body    = "Hi {$row['customer_name']},\n\nThis is a friendly reminder that you have an appointment for {$row['service_name']} at {$row['appointment_time']} today at GS Barbershop.\n\nSee you soon!\n\nRegards,\nGS Barbershop";

            $mail->send();
            $emails_sent++;

        } catch (Exception $e) {
            error_log("Mailer Error ({$row['customer_email']}): {$mail->ErrorInfo}");
        }
    }

    $message = "<div class='alert alert-success'>{$emails_sent} reminder email(s) sent successfully!</div>";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Booked Appointments - GS Barbershop</title>
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
        
        /* Status Badges */
        .badge-pending {
            background-color: #ffc107;
            color: #212529;
        }
        
        .badge-confirmed {
            background-color: #28a745;
            color: white;
        }
        
        .badge-completed {
            background-color: #6c757d;
            color: white;
        }
        
        .badge-cancelled {
            background-color: #dc3545;
            color: white;
        }
        
        /* Pagination Styles */
        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .pagination .page-link {
            color: var(--dark-color);
            margin: 0 5px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        .pagination .page-link:hover {
            background-color: #f0f0f0;
            color: var(--primary-color);
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
        
        /* Highlight today's appointments */
        .today-appointment {
            background-color: #fff9e6;
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
            <img src="admin.jpeg" alt="Admin" class="user-img">
            <div class="dropdown">
                <button>
                    Admin <i class="fas fa-caret-down"></i>
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
                    <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_services.php">Manage Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_schedule.php">Manage Schedule</a></li>
                    <li class="nav-item active"><a class="nav-link" href="manage_bookedappointments.php">Manage Appointments</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_barbers.php">Manage Barbers</a></li>
                    <li class="nav-item"><a class="nav-link" href="track_payments.php">Track Payments</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <h1 class="mb-4">Manage Booked Appointments</h1>

        <?php echo $message; ?>

        <!-- Debugging Output (remove in production) -->
        <div class="alert alert-info d-none">
            <strong>Debug Info:</strong><br>
            Active Tab: <?php echo $active_tab; ?><br>
            Search Date: <?php echo $search_date ?: 'Not set'; ?><br>
            Search Customer: <?php echo $search_customer ?: 'Not set'; ?><br>
            SQL Query: <?php echo htmlspecialchars($sql); ?><br>
            Total Rows: <?php echo $total_rows; ?><br>
            Current Page: <?php echo $current_page; ?>
        </div>

         <form method="post" action="manage_bookedappointments.php">
    <button type="submit" name="send_reminder" class="btn btn-primary mb-3">
        <i class="fas fa-bell"></i> Send Today's Reminder
    </button>
</form>

        <!-- Appointment Tabs -->
        <ul class="nav nav-tabs appointment-tabs" id="appointmentTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab === 'upcoming' ? 'active' : ''; ?>" 
                   href="?tab=upcoming<?php echo $search_date ? '&search_date='.htmlspecialchars($search_date) : ''; ?><?php echo $search_customer ? '&search_customer='.htmlspecialchars(urlencode($search_customer)) : ''; ?>">
                   Upcoming Appointments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab === 'past' ? 'active' : ''; ?>" 
                   href="?tab=past<?php echo $search_date ? '&search_date='.htmlspecialchars($search_date) : ''; ?><?php echo $search_customer ? '&search_customer='.htmlspecialchars(urlencode($search_customer)) : ''; ?>">
                   Past Appointments
                </a>
            </li>
        </ul>

        <!-- Search Form -->
        <div class="table-responsive">
            <div class="card-body">
                <form method="GET" action="">
                    <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="search_date">Date</label>
                            <input type="date" name="search_date" id="search_date" class="form-control" 
                                   value="<?php echo $search_date ? htmlspecialchars($search_date) : ''; ?>">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="search_customer">Customer Name</label>
                            <input type="text" name="search_customer" id="search_customer" class="form-control" 
                                   value="<?php echo $search_customer ? htmlspecialchars($search_customer) : ''; ?>"
                                   placeholder="Enter customer name">
                        </div>
                        <div class="form-group col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary mr-2">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <a href="?tab=<?php echo $active_tab; ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Appointment List Table -->
        <div class="table-responsive mt-3">
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>No.</th>
                        <th>Customer Name</th>
                        <th>Barber Name</th>
                        <th>Service Name</th>
                        <th>Style</th>
                        <th>Add-Ons</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php 
                        $count = 1 + $offset; 
                        $today = date('Y-m-d');
                        while ($row = $result->fetch_assoc()): 
                            $isToday = ($row['appointment_date'] == $today);
                        ?>
                            <tr class="<?php echo $isToday ? 'today-appointment' : ''; ?>">
                                <td><?php echo $count++; ?></td>
                                <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['barber_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['variation'] ?? 'Standard'); ?></td>
                                <td><?php echo htmlspecialchars($row['addon_names'] ?? 'None'); ?></td>
                                <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                                <td><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></td>
                                <td>
                                    <?php 
                                    $status_class = '';
                                    switch($row['status']) {
                                        case 'Pending': $status_class = 'badge-pending'; break;
                                        case 'Confirmed': $status_class = 'badge-confirmed'; break;
                                        case 'Completed': $status_class = 'badge-completed'; break;
                                        case 'Cancelled': $status_class = 'badge-cancelled'; break;
                                        default: $status_class = 'badge-secondary';
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <?php if ($row['status'] !== 'Completed' && $row['status'] !== 'Cancelled'): ?>
                                        <form method="POST" action="" class="form-inline">
                                            <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
                                            <select name="status" class="form-control form-control-sm mr-1" required>
                                                <option value="Confirmed" <?php echo $row['status'] === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                <option value="Completed">Completed</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-primary btn-sm">Update</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted"><?php echo htmlspecialchars($row['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center">
                                <?php 
                                if (!empty($search_date) || !empty($search_customer)) {
                                    echo 'No appointments found matching your search criteria.';
                                } else {
                                    echo $active_tab === 'upcoming' 
                                        ? 'No upcoming appointments found.' 
                                        : 'No past appointments found.';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination Controls -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($current_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?tab=<?php echo $active_tab; ?>&page=1<?php echo $search_date ? '&search_date='.$search_date : ''; ?><?php echo $search_customer ? '&search_customer='.urlencode($search_customer) : ''; ?>" aria-label="First">
                                <span aria-hidden="true">&laquo;&laquo;</span>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?tab=<?php echo $active_tab; ?>&page=<?php echo $current_page - 1; ?><?php echo $search_date ? '&search_date='.$search_date : ''; ?><?php echo $search_customer ? '&search_customer='.urlencode($search_customer) : ''; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php 
                    // Show page numbers
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    if ($start_page > 1) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?tab=<?php echo $active_tab; ?>&page=<?php echo $i; ?><?php echo $search_date ? '&search_date='.$search_date : ''; ?><?php echo $search_customer ? '&search_customer='.urlencode($search_customer) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; 
                    
                    if ($end_page < $total_pages) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    ?>

                    <?php if ($current_page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?tab=<?php echo $active_tab; ?>&page=<?php echo $current_page + 1; ?><?php echo $search_date ? '&search_date='.$search_date : ''; ?><?php echo $search_customer ? '&search_customer='.urlencode($search_customer) : ''; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?tab=<?php echo $active_tab; ?>&page=<?php echo $total_pages; ?><?php echo $search_date ? '&search_date='.$search_date : ''; ?><?php echo $search_customer ? '&search_customer='.urlencode($search_customer) : ''; ?>" aria-label="Last">
                                <span aria-hidden="true">&raquo;&raquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
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

    <script>
        // Highlight today's date in the table
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.querySelectorAll('td:nth-child(7)').forEach(cell => {
                if (cell.textContent === today) {
                    cell.closest('tr').classList.add('today-appointment');
                }
            });
            
            // For debugging - show debug info when holding Ctrl+Shift+D
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.shiftKey && e.key === 'D') {
                    document.querySelector('.alert.alert-info').classList.toggle('d-none');
                }
            });
        });
    </script>
</body>
</html>