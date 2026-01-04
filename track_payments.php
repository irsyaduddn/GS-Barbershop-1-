<?php
// Start session
session_start();

// Set timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['usertype'] !== 'admin') {
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

// Initialize variables for messages
$message = "";

// Get current date for filtering
$current_date = date('Y-m-d');

// Check which tab is active (default to confirmed)
$active_tab = isset($_GET['tab']) && $_GET['tab'] === 'cancelled' ? 'cancelled' : 'confirmed';

// Get search parameters
$search_date = isset($_GET['search_date']) ? $_GET['search_date'] : null;
$search_customer = isset($_GET['search_customer']) ? trim($_GET['search_customer']) : null;
$search_status = isset($_GET['search_status']) ? $_GET['search_status'] : null;

// Pagination settings
$rows_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $rows_per_page;

// Base SQL query
$sql = "SELECT 
            a.appointment_id, 
            c.customer_name, 
            c.customer_email,
            b.barber_name, 
            s.service_name, 
            a.appointment_date, 
            a.appointment_time, 
            a.status,
            a.total_amount,
            a.payment_id,
            a.payment_date,
            a.payment_status,
            a.variation,
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
if ($active_tab === 'confirmed') {
    $conditions[] = "a.status = 'Confirmed'";
} else {
    $conditions[] = "a.status = 'Cancelled'";
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

if (!empty($search_status) && $search_status !== 'all') {
    $conditions[] = "a.payment_status = ?";
    $params[] = $search_status;
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

if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_result = $count_stmt->get_result();
$total_rows = $total_result->num_rows;
$total_pages = ceil($total_rows / $rows_per_page);

// Add ordering and pagination
$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

// Add pagination
$sql .= " LIMIT ? OFFSET ?";
$params[] = $rows_per_page;
$params[] = $offset;
$types .= "ii";

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

// Handle payment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment_status'])) {
    $appointment_id = $_POST['appointment_id'];
    $payment_id = $_POST['payment_id'];
    $payment_status = $_POST['payment_status'];
    $refund_amount = $_POST['refund_amount'];
    
    // First verify the payment exists and is refundable
    $check_sql = "SELECT a.*, c.customer_name, c.customer_email 
                  FROM appointments a
                  JOIN customers c ON a.customer_id = c.customer_id
                  WHERE a.appointment_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $appointment_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $appointment = $check_result->fetch_assoc();
        
        if ($payment_status === 'Refunded') {
            if ($appointment['status'] !== 'Cancelled') {
                $message = "<div class='alert alert-danger'>Only cancelled appointments can be refunded.</div>";
            } elseif ($appointment['payment_status'] === 'Refunded') {
                $message = "<div class='alert alert-danger'>This payment has already been refunded.</div>";
            } elseif (empty($payment_id)) {
                $message = "<div class='alert alert-danger'>No payment record found for this appointment.</div>";
            } else {
                // Update the database to mark as refunded
                $update_sql = "UPDATE appointments SET payment_status = 'Refunded' WHERE appointment_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $appointment_id);
                
                if ($update_stmt->execute()) {
                    // Send email notification to customer
                    $mail = new PHPMailer(true);
                    
                    try {
                        //Server settings
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com'; // Your SMTP server
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'irsyaduddn@gmail.com'; // SMTP username
                        $mail->Password   = 'mvap bryp xrxf knxk'; // SMTP password
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;
                        
                        //Recipients
                        $mail->setFrom('irsyaduddn@gmail.com', 'GS Barbershop');
                        $mail->addAddress($appointment['customer_email'], $appointment['customer_name']);
                        
                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = 'Your Refund Has Been Processed';
                        
                        $email_body = "
                        <html>
                        <head>
                            <style>
                                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                                .header { background-color: #b89d5a; color: white; padding: 10px; text-align: center; }
                                .content { padding: 20px; background-color: #f9f9f9; }
                                .footer { margin-top: 20px; padding: 10px; text-align: center; font-size: 12px; color: #777; }
                                .amount { font-size: 18px; font-weight: bold; color: #b89d5a; }
                            </style>
                        </head>
                        <body>
                            <div class='container'>
                                <div class='header'>
                                    <h2>GS Barbershop</h2>
                                </div>
                                <div class='content'>
                                    <p>Dear " . htmlspecialchars($appointment['customer_name']) . ",</p>
                                    <p>We have processed your refund for the cancelled appointment.</p>
                                    
                                    <p><strong>Appointment Details:</strong></p>
                                    <ul>
                                        <li>Appointment ID: #" . htmlspecialchars($appointment['appointment_id']) . "</li>
                                        <li>Date: " . htmlspecialchars($appointment['appointment_date']) . "</li>
                                        <li>Time: " . htmlspecialchars($appointment['appointment_time']) . "</li>
                                        <li>Service: " . htmlspecialchars($appointment['service_name']) . "</li>
                                    </ul>
                                    
                                    <p><strong>Refund Details:</strong></p>
                                    <ul>
                                        <li>Refund Amount: RM <span class='amount'>" . number_format($refund_amount, 2) . "</span></li>
                                        <li>Payment Method: Credit Card</li>
                                        <li>Refund Date: " . date('Y-m-d H:i:s') . "</li>
                                    </ul>
                                    
                                    <p>Please allow 5-7 business days for the refund to appear in your account.</p>
                                    <p>If you have any questions, please contact us at amilsahak8@gmail.com or call us at (+60) 13-3248962.</p>
                                </div>
                                <div class='footer'>
                                    <p>Thank you for choosing GS Barbershop!</p>
                                    <p>No 24, Jalan Bunga Cempaka 1, Serom 6, Sungai Mati, Tangkak, Johor</p>
                                </div>
                            </div>
                        </body>
                        </html>
                        ";
                        
                        $mail->Body = $email_body;
                        
                        $mail->send();
                        $message = "<div class='alert alert-success'>Payment status updated to Refunded and notification email sent to customer!</div>";
                    } catch (Exception $e) {
                        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
                        $message = "<div class='alert alert-success'>Payment status updated but the notification email could not be sent. Error: " . $e->getMessage() . "</div>";
                    }
                    
                    // Redirect to avoid form resubmission
                    $redirect_url = "track_payments.php?tab=$active_tab";
                    if (!empty($search_date)) $redirect_url .= "&search_date=$search_date";
                    if (!empty($search_customer)) $redirect_url .= "&search_customer=" . urlencode($search_customer);
                    if (!empty($search_status)) $redirect_url .= "&search_status=$search_status";
                    header("Location: $redirect_url");
                    exit();
                } else {
                    $message = "<div class='alert alert-danger'>Failed to update payment status. Please try again.</div>";
                }
                $update_stmt->close();
            }
        } else {
            // Handle other payment status updates if needed
            $update_sql = "UPDATE appointments SET payment_status = ? WHERE appointment_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $payment_status, $appointment_id);
            
            if ($update_stmt->execute()) {
                $message = "<div class='alert alert-success'>Payment status updated successfully!</div>";
                
                // Redirect to avoid form resubmission
                $redirect_url = "track_payments.php?tab=$active_tab";
                if (!empty($search_date)) $redirect_url .= "&search_date=$search_date";
                if (!empty($search_customer)) $redirect_url .= "&search_customer=" . urlencode($search_customer);
                if (!empty($search_status)) $redirect_url .= "&search_status=$search_status";
                header("Location: $redirect_url");
                exit();
            } else {
                $message = "<div class='alert alert-danger'>Failed to update payment status. Please try again.</div>";
            }
            $update_stmt->close();
        }
    } else {
        $message = "<div class='alert alert-danger'>Appointment not found.</div>";
    }
    $check_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Payments - GS Barbershop</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #b89d5a;
            --dark-color: #222;
            --light-color: #f9f9f9;
            --success-color: #28a745;
            --info-color: #17a2b8;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            line-height: 1.6;
            padding-top: 160px;
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
        
        /* Payment Status Badges */
        .badge-paid {
            background-color:  #28a745;
            color: white;
        }
        
        .badge-refunded {
            background-color: var(--info-color);
            color: white;
        }
        
        .badge-pending {
            background-color: var(--warning-color);
            color: #212529;
        }
        
        .badge-failed {
            background-color: var(--danger-color);
            color: white;
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
        
        /* Highlight today's payments */
        .today-payment {
            background-color: #fff9e6;
        }
        
        /* Refund form styling */
        .refund-form {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
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
            border-left: 4px solid var(--primary-color);
        }
        
        .status-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        
        .status-card.paid {
            border-left-color: var(--success-color);
        }
        
        .status-card.refunded {
            border-left-color: var(--info-color);
        }
        
        .status-card.pending {
            border-left-color: var(--warning-color);
        }
        
        .status-card.revenue {
            border-left-color: var(--primary-color);
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
            color: var(--primary-color);
        }
        
        .status-card.paid .count {
            color: var(--success-color);
        }
        
        .status-card.refunded .count {
            color: var(--info-color);
        }
        
        .status-card.pending .count {
            color: var(--warning-color);
        }
        
        .status-card.revenue .count {
            color: var(--primary-color);
        }
        
        .status-card .icon {
            font-size: 30px;
            float: right;
            opacity: 0.3;
        }
        
        /* Appointment Tabs */
        .payment-tabs {
            margin-bottom: 30px;
            border-bottom: 1px solid #ddd;
        }
        
        .payment-tabs .nav-link {
            color: #555;
            font-weight: 500;
            padding: 10px 20px;
            border: none;
            border-bottom: 3px solid transparent;
        }
        
        .payment-tabs .nav-link.active {
            color: var(--primary-color);
            border-bottom: 3px solid var(--primary-color);
            background-color: transparent;
        }
        
        .payment-tabs .nav-link:hover:not(.active) {
            color: var(--dark-color);
            border-bottom: 3px solid #ddd;
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
                    <li class="nav-item"><a class="nav-link" href="manage_bookedappointments.php">Manage Appointments</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_barbers.php">Manage Barbers</a></li>
                    <li class="nav-item active"><a class="nav-link" href="track_payments.php">Track Payments</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <h1 class="mb-4">Track Payments</h1>

        <?php echo $message; ?>

        <!-- Payment Tabs -->
        <ul class="nav nav-tabs payment-tabs" id="paymentTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab === 'confirmed' ? 'active' : ''; ?>" 
                   href="?tab=confirmed<?php echo $search_date ? '&search_date='.htmlspecialchars($search_date) : ''; ?><?php echo $search_customer ? '&search_customer='.htmlspecialchars(urlencode($search_customer)) : ''; ?><?php echo $search_status ? '&search_status='.htmlspecialchars($search_status) : ''; ?>">
                   Confirmed Appointments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab === 'cancelled' ? 'active' : ''; ?>" 
                   href="?tab=cancelled<?php echo $search_date ? '&search_date='.htmlspecialchars($search_date) : ''; ?><?php echo $search_customer ? '&search_customer='.htmlspecialchars(urlencode($search_customer)) : ''; ?><?php echo $search_status ? '&search_status='.htmlspecialchars($search_status) : ''; ?>">
                   Cancelled Appointments
                </a>
            </li>
        </ul>

        <!-- Status Cards -->
        <div class="status-cards">
            <div class="status-card paid">
                <h2>Paid Payments</h2>
                <div class="count">
                    <?php
                    $paid_sql = "SELECT COUNT(*) as count FROM appointments WHERE payment_status = 'Paid'";
                    $paid_result = $conn->query($paid_sql);
                    echo $paid_result->fetch_assoc()['count'];
                    ?>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            
            <div class="status-card refunded">
                <h2>Refunded</h2>
                <div class="count">
                    <?php
                    $refunded_sql = "SELECT COUNT(*) as count FROM appointments WHERE payment_status = 'Refunded'";
                    $refunded_result = $conn->query($refunded_sql);
                    echo $refunded_result->fetch_assoc()['count'];
                    ?>
                </div>
                <div class="icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
            </div> 
        </div>

        <!-- Search Form -->
            <div class="card-body">
                <form method="GET" action="">
                    <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="search_date">Date</label>
                            <input type="date" name="search_date" id="search_date" class="form-control" 
                                   value="<?php echo $search_date ? htmlspecialchars($search_date) : ''; ?>">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="search_customer">Customer Name</label>
                            <input type="text" name="search_customer" id="search_customer" class="form-control" 
                                   value="<?php echo $search_customer ? htmlspecialchars($search_customer) : ''; ?>"
                                   placeholder="Enter customer name">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="search_status">Payment Status</label>
                            <select name="search_status" id="search_status" class="form-control">
                                <option value="all" <?php echo empty($search_status) || $search_status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                <option value="Paid" <?php echo $search_status === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                                <option value="Refunded" <?php echo $search_status === 'Refunded' ? 'selected' : ''; ?>>Refunded</option>
                                <option value="Pending" <?php echo $search_status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Failed" <?php echo $search_status === 'Failed' ? 'selected' : ''; ?>>Failed</option>
                            </select>
                        </div>
                        <div class="form-group col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary mr-2">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <a href="track_payments.php?tab=<?php echo $active_tab; ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>

<!-- Payment List Table -->
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>No.</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Amount (RM)</th>
                            <th>Payment Date</th>
                            <th>Payment Status</th>
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
                                <tr class="<?php echo $isToday ? 'today-payment' : ''; ?>">
                                    <td><?php echo $count++; ?></td>
                                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                                    <td class="text-right"><?php echo number_format($row['total_amount'], 2); ?></td>
                                    <td><?php echo $row['payment_date'] ? htmlspecialchars($row['payment_date']) : 'N/A'; ?></td>
                                    <td>
                                        <?php 
                                        $status_class = '';
                                        $payment_status = $row['payment_status'] ?? 'Pending';
                                        switch($payment_status) {
                                            case 'Paid':
                                                $status_class = 'badge-paid';
                                                break;
                                            case 'Refunded':
                                                $status_class = 'badge-refunded';
                                                break;
                                            case 'Pending':
                                                $status_class = 'badge-pending';
                                                break;
                                            case 'Failed':
                                                $status_class = 'badge-failed';
                                                break;
                                            default:
                                                $status_class = 'badge-secondary';
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <?php echo htmlspecialchars($payment_status); ?>
                                        </span>
                                    </td>
                                    <td class="action-buttons">
    <?php if ($row['status'] === 'Cancelled' && $row['payment_status'] !== 'Refunded'): ?>
        <form method="POST" action="" class="form-inline">
            <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
            <input type="hidden" name="payment_id" value="<?php echo $row['payment_id']; ?>">
            <input type="hidden" name="refund_amount" value="<?php echo $row['total_amount']; ?>">
            <select name="payment_status" class="form-control form-control-sm mr-1" required>
                <option value="Paid" <?php echo $row['payment_status'] === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                <option value="Refunded">Refunded</option>
            </select>
            <button type="submit" name="update_payment_status" class="btn btn-primary btn-sm">Update</button>
        </form>
    <?php elseif ($row['payment_status'] === 'Refunded'): ?>
        <span class="text-muted">Refunded</span>
    <?php else: ?>
        <span class="text-muted"><?php echo htmlspecialchars($row['status']); ?></span>
    <?php endif; ?>
</td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">
                                    <?php 
                                    if (!empty($search_date) || !empty($search_customer) || !empty($search_status)) {
                                        echo 'No appointments found matching your search criteria.';
                                    } else {
                                        echo $active_tab === 'confirmed' 
                                            ? 'No confirmed appointments found.' 
                                            : 'No cancelled appointments found.';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

                <!-- Pagination Controls -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($current_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?tab=<?php echo $active_tab; ?>&page=1<?php echo $search_date ? '&search_date='.$search_date : ''; ?><?php echo $search_customer ? '&search_customer='.urlencode($search_customer) : ''; ?><?php echo $search_status ? '&search_status='.$search_status : ''; ?>" aria-label="First">
                                    <span aria-hidden="true">&laquo;&laquo;</span>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?tab=<?php echo $active_tab; ?>&page=<?php echo $current_page - 1; ?><?php echo $search_date ? '&search_date='.$search_date : ''; ?><?php echo $search_customer ? '&search_customer='.urlencode($search_customer) : ''; ?><?php echo $search_status ? '&search_status='.$search_status : ''; ?>" aria-label="Previous">
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
                                <a class="page-link" href="?tab=<?php echo $active_tab; ?>&page=<?php echo $i; ?><?php echo $search_date ? '&search_date='.$search_date : ''; ?><?php echo $search_customer ? '&search_customer='.urlencode($search_customer) : ''; ?><?php echo $search_status ? '&search_status='.$search_status : ''; ?>">
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
                                <a class="page-link" href="?tab=<?php echo $active_tab; ?>&page=<?php echo $current_page + 1; ?><?php echo $search_date ? '&search_date='.$search_date : ''; ?><?php echo $search_customer ? '&search_customer='.urlencode($search_customer) : ''; ?><?php echo $search_status ? '&search_status='.$search_status : ''; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?tab=<?php echo $active_tab; ?>&page=<?php echo $total_pages; ?><?php echo $search_date ? '&search_date='.$search_date : ''; ?><?php echo $search_customer ? '&search_customer='.urlencode($search_customer) : ''; ?><?php echo $search_status ? '&search_status='.$search_status : ''; ?>" aria-label="Last">
                                    <span aria-hidden="true">&raquo;&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
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
        // Handle refund modal data
        $('#refundModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var appointmentId = button.data('appointment');
            var amount = button.data('amount');
            var paymentId = button.data('payment');
            
            var modal = $(this);
            modal.find('#appointmentId').val(appointmentId);
            modal.find('#refundAmount').val(amount);
            modal.find('#paymentId').val(paymentId);
        });
        
        // Highlight today's payments
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.querySelectorAll('td:nth-child(6)').forEach(cell => {
                if (cell.textContent === today) {
                    cell.closest('tr').classList.add('today-payment');
                }
            });
        });
    </script>
</body>
</html>