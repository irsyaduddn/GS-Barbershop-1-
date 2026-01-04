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

// Initialize variables for messages
$message = "";

// Function to generate time slots
function generateTimeSlots($start_time, $end_time, $interval) {
    $slots = array();
    
    $start = new DateTime($start_time);
    $end = new DateTime($end_time);
    $interval = new DateInterval("PT{$interval}M");
    
    $period = new DatePeriod($start, $interval, $end);
    
    foreach ($period as $time) {
        $slots[] = $time->format('H:i:s');
    }
    
    return $slots;
}

// Handle appointment addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_appointment'])) {
    $customer_id = null; // Since this is not booked yet, set to null
    $barber_id = $_POST['barber_id'];
    $service_id = $_POST['service_id'];
    $appointment_date = $_POST['appointment_date'];

    if (empty($barber_id) || empty($service_id) || empty($appointment_date)) {
        $message = "<div class='alert alert-danger'>All fields are required. Please fill out the form completely.</div>";
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Generate time slots
            $morning_slots = generateTimeSlots('10:30', '13:00', 30);
            $afternoon_slots = generateTimeSlots('14:00', '18:00', 30);
            $all_slots = array_merge($morning_slots, $afternoon_slots);
            
            $success_count = 0;
            
            foreach ($all_slots as $slot) {
                // Check if slot already exists for this barber and date
                $check_sql = "SELECT appointment_id FROM appointments 
                             WHERE barber_id = ? AND appointment_date = ? AND appointment_time = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("iss", $barber_id, $appointment_date, $slot);
                $check_stmt->execute();
                $check_stmt->store_result();
                
                if ($check_stmt->num_rows === 0) {
                    // Slot doesn't exist, add it
                    $insert_sql = "INSERT INTO appointments 
                                 (customer_id, barber_id, service_id, appointment_date, appointment_time, status, is_booked) 
                                 VALUES (?, ?, ?, ?, ?, 'Available', 0)";
                    $insert_stmt = $conn->prepare($insert_sql);
                    $insert_stmt->bind_param("iiiss", $customer_id, $barber_id, $service_id, $appointment_date, $slot);
                    
                    if ($insert_stmt->execute()) {
                        $success_count++;
                        $message = "<div class='alert alert-success'>Appointment time slot added successfully!</div>";
                    } else {
                        $message = "<div class='alert alert-danger'>Failed to add appointment time slot Please try again.</div>";
                    }
                    $insert_stmt->close();
                }
                $check_stmt->close();
            }
            
            // Commit transaction
            $conn->commit();
            
            if ($success_count > 0) {
                $message = "<div class='alert alert-success'>Successfully added $success_count appointment slots!</div>";
                // Refresh to show the new appointments
                header("Refresh:");
            } else {
                $message = "<div class='alert alert-info'>No new slots were added (all slots already exist).</div>";
            }
            
        } catch (Exception $e) {
            // Roll back on error
            $conn->rollback();
            $message = "<div class='alert alert-danger'>Failed to add appointments: " . $e->getMessage() . "</div>";
        }
    }
}

// Handle appointment deletion
if (isset($_GET['delete'])) {
    $appointment_id = $_GET['delete'];

    // First check if the appointment is booked
    $check_sql = "SELECT customer_id FROM appointments WHERE appointment_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $appointment_id);
    $check_stmt->execute();
    $check_stmt->bind_result($customer_id);
    $check_stmt->fetch();
    $check_stmt->close();

    if ($customer_id !== null) {
        $message = "<div class='alert alert-danger'>Cannot delete a booked appointment. Please cancel it first.</div>";
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // 1. First delete any payment records
            $deletePaymentStmt = $conn->prepare("DELETE FROM payments WHERE appointment_id = ?");
            $deletePaymentStmt->bind_param("i", $appointment_id);
            
            if ($deletePaymentStmt->execute()) {
                $message = "<div class='alert alert-success'>Payment record deleted successfully!</div>";
            } else {
                $message = "<div class='alert alert-danger'>Failed to delete payment record. Please try again.</div>";
            }
            
            // delete the appointment
            $deleteAppointmentStmt = $conn->prepare("DELETE FROM appointments WHERE appointment_id = ?");
            $deleteAppointmentStmt->bind_param("i", $appointment_id);
            
            if ($deleteAppointmentStmt->execute()) {
                $message = "<div class='alert alert-success'>Appointment slot deleted successfully!</div>";
                // Refresh to show updated list
                header("Refresh:");    
            } else {
                $message = "<div class='alert alert-danger'>Failed to delete appointment. Please try again.</div>";
            }
            
            // Commit transaction
            $conn->commit();
            
        } catch (Exception $e) {
            // Roll back on error
            $conn->rollback();
            $message = "<div class='alert alert-danger'>Failed to delete appointment: " . $e->getMessage() . "</div>";
        }
    }
}

// Get current date for filtering appointments
$current_date = date('Y-m-d');

// Check which tab is active (default to upcoming)
$active_tab = isset($_GET['tab']) && $_GET['tab'] === 'past' ? 'past' : 'upcoming';

// Get search parameters
$search_date = isset($_GET['search_date']) ? $_GET['search_date'] : null;
$search_barber = isset($_GET['search_barber']) ? (int)$_GET['search_barber'] : null;

// Fetch all barbers for dropdown
$barbers_sql = "SELECT barber_id, barber_name FROM barbers";
$barbers_result = $conn->query($barbers_sql);

// Fetch all services for dropdown
$services_sql = "SELECT service_id, service_name FROM services";
$services_result = $conn->query($services_sql);

// Pagination settings
$rows_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $rows_per_page;

// Fetch appointments based on active tab
if ($active_tab === 'upcoming') {
    $sql = "SELECT a.appointment_id, 
                   b.barber_name, 
                   a.appointment_date, 
                   a.appointment_time, 
                   s.service_name, 
                   a.status,
                   c.customer_name,
                   a.is_booked
            FROM appointments a
            JOIN barbers b ON a.barber_id = b.barber_id
            JOIN services s ON a.service_id = s.service_id
            LEFT JOIN customers c ON a.customer_id = c.customer_id
            WHERE a.appointment_date >= ?";
    
    $params = [$current_date];
    $types = "s";
} else {
    $sql = "SELECT a.appointment_id, 
                   b.barber_name, 
                   a.appointment_date, 
                   a.appointment_time, 
                   s.service_name, 
                   a.status,
                   c.customer_name,
                   a.is_booked
            FROM appointments a
            JOIN barbers b ON a.barber_id = b.barber_id
            JOIN services s ON a.service_id = s.service_id
            LEFT JOIN customers c ON a.customer_id = c.customer_id
            WHERE a.appointment_date < ?";
    
    $params = [$current_date];
    $types = "s";
}

// Add search conditions if provided
if ($search_date) {
    $sql .= " AND a.appointment_date = ?";
    $params[] = $search_date;
    $types .= "s";
}

if ($search_barber) {
    $sql .= " AND a.barber_id = ?";
    $params[] = $search_barber;
    $types .= "i";
}

// Add ordering
if ($active_tab === 'upcoming') {
    $sql .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";
} else {
    $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
}

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

if ($search_date) {
    $count_params[] = $search_date;
    $count_types .= "s";
}
if ($search_barber) {
    $count_params[] = $search_barber;
    $count_types .= "i";
}

if (!empty($count_types)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$total_result = $count_stmt->get_result();
$total_rows = $total_result->num_rows;
$total_pages = ceil($total_rows / $rows_per_page);

// Add pagination to main query
$sql .= " LIMIT ? OFFSET ?";
$params[] = $rows_per_page;
$params[] = $offset;
$types .= "ii";

// Prepare and execute the statement
$stmt = $conn->prepare($sql);
if ($params && !empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedule - GS Barbershop</title>
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
        .badge-available {
            background-color: #28a745;
            color: white;
        }
        
        .badge-booked {
            background-color: #007bff;
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
        
        /* Search Form Styles */
        .search-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .search-card .card-header {
            background-color: var(--light-color);
            border-bottom: 1px solid #e0e0e0;
            padding: 15px 20px;
        }
        
        .search-card .card-body {
            padding: 20px;
        }
        
        .search-card .form-control {
            border-radius: 4px;
        }
        
        .search-card .btn {
            border-radius: 4px;
            padding: 8px 15px;
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
        
        /* Additional styles for action buttons */
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .confirmation-modal .modal-content {
        border-radius: 10px;
        border: none;
    }
    
    .confirmation-modal .modal-header {
        border-bottom: none;
        padding-bottom: 0;
    }
    
    .confirmation-modal .modal-body {
        text-align: center;
        padding: 20px 30px;
    }
    
    .confirmation-icon {
        font-size: 60px;
        color: #f8bb86;
        margin-bottom: 20px;
    }
    
    .confirmation-modal .modal-footer {
        border-top: none;
        justify-content: center;
        padding-top: 0;
        padding-bottom: 30px;
    }
    
    .btn-confirm {
        min-width: 100px;
    }
    
    .btn-cancel {
        min-width: 100px;
        margin-right: 15px;
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
                    <li class="nav-item active"><a class="nav-link" href="manage_schedule.php">Manage Schedule</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_bookedappointments.php">Manage Appointments</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_barbers.php">Manage Barbers</a></li>
                    <li class="nav-item"><a class="nav-link" href="track_payments.php">Track Payments</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <h1 class="mb-4">Manage Schedule</h1>

        <?php echo $message; ?>

        <!-- Add Appointment Button -->
        <button class="btn btn-primary mb-4" data-toggle="modal" data-target="#addAppointmentModal">
            <i class="fas fa-plus"></i> Add Appointment Slots
        </button>

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
                                   value="<?php echo isset($_GET['search_date']) ? htmlspecialchars($_GET['search_date']) : ''; ?>">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="search_barber">Barber</label>
                            <select name="search_barber" id="search_barber" class="form-control">
                                <option value="">All Barbers</option>
                                <?php 
                                $barbers_result->data_seek(0); // Reset barbers result pointer
                                while ($barber = $barbers_result->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $barber['barber_id']; ?>"
                                        <?php if(isset($_GET['search_barber']) && $_GET['search_barber'] == $barber['barber_id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($barber['barber_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
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
        
        <!-- Add Appointment Modal -->
        <div class="modal fade" id="addAppointmentModal" tabindex="-1" role="dialog" aria-labelledby="addAppointmentModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addAppointmentModalLabel">Add New Appointment Slots</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="" id="addAppointmentForm">
                            <div class="form-group">
                                <label for="barber_id">Barber</label>
                                <select name="barber_id" id="barber_id" class="form-control" required>
                                    <?php 
                                    $barbers_result->data_seek(0); // Reset barbers result pointer
                                    while ($barber = $barbers_result->fetch_assoc()): ?>
                                        <option value="<?php echo $barber['barber_id']; ?>">
                                            <?php echo htmlspecialchars($barber['barber_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="service_id">Service</label>
                                <select name="service_id" id="service_id" class="form-control" required>
                                    <?php 
                                    $services_result->data_seek(0); // Reset services result pointer
                                    while ($service = $services_result->fetch_assoc()): ?>
                                        <option value="<?php echo $service['service_id']; ?>">
                                            <?php echo htmlspecialchars($service['service_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="appointment_date">Date</label>
                                <input type="date" name="appointment_date" id="appointment_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> This will automatically create time slots from 10:30 AM to 1:00 PM and 2:00 PM to 6:00 PM with 30-minute intervals.
                            </div>
                            <button type="submit" name="add_appointment" class="btn btn-primary">Generate Slots</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

<div class="modal fade confirmation-modal" id="confirmationModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Action</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="confirmation-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <p id="confirmationMessage">Are you sure you want to delete this appointment slot?</p>
                <p class="text-danger">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-cancel" data-dismiss="modal">Cancel</button>
                <a id="confirmActionBtn" href="#" class="btn btn-danger btn-confirm">Delete</a>
            </div>
        </div>
    </div>
</div>

        <!-- Appointment List Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>No.</th>
                        <th>Barber</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <?php if ($active_tab === 'upcoming'): ?>
                            <th>Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php $count = 1 + $offset; while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td><?php echo htmlspecialchars($row['barber_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                                <td><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></td>
                                <td><?php echo $row['customer_name'] ? htmlspecialchars($row['customer_name']) : 'Available'; ?></td>
                                <td>
                                    <?php 
                                    $status_class = '';
                                    switch($row['status']) {
                                        case 'Available': $status_class = 'badge-available'; break;
                                        case 'Booked': $status_class = 'badge-booked'; break;
                                        case 'Completed': $status_class = 'badge-completed'; break;
                                        case 'Cancelled': $status_class = 'badge-cancelled'; break;
                                        default: $status_class = 'badge-secondary';
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <?php if ($active_tab === 'upcoming'): ?>
                                    <td>
                                        <?php if ($row['is_booked'] == 0 && $row['appointment_date'] >= $current_date): ?>
    <button class="btn btn-danger btn-sm delete-btn" 
            data-id="<?php echo $row['appointment_id']; ?>"
            data-tab="<?php echo $active_tab; ?>"
            data-date="<?php echo $search_date ? $search_date : ''; ?>"
            data-barber="<?php echo $search_barber ? $search_barber : ''; ?>">
        <i class="fas fa-trash-alt"></i> Delete
    </button>
<?php else: ?>
    <span class="text-muted">No action</span>
<?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">
                                <?php 
                                if ($search_date || $search_barber) {
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
                            <a class="page-link" href="?tab=<?php echo $active_tab; ?>&page=1<?php echo $search_date ? '&search_date='.$search_date : ''; ?><?php echo $search_barber ? '&search_barber='.$search_barber : ''; ?>" aria-label="First">
                                <span aria-hidden="true">&laquo;&laquo;</span>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?tab=<?php echo $active_tab; ?>&page=<?php echo $current_page - 1; ?><?php echo $search_date ? '&search_date='.$search_date : ''; ?><?php echo $search_barber ? '&search_barber='.$search_barber : ''; ?>" aria-label="Previous">
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
                            <a class="page-link" href="?tab=<?php echo $active_tab; ?>&page=<?php echo $i; ?><?php echo $search_date ? '&search_date='.$search_date : ''; ?><?php echo $search_barber ? '&search_barber='.$search_barber : ''; ?>">
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
                            <a class="page-link" href="?tab=<?php echo $active_tab; ?>&page=<?php echo $current_page + 1; ?><?php echo $search_date ? '&search_date='.$search_date : ''; ?><?php echo $search_barber ? '&search_barber='.$search_barber : ''; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?tab=<?php echo $active_tab; ?>&page=<?php echo $total_pages; ?><?php echo $search_date ? '&search_date='.$search_date : ''; ?><?php echo $search_barber ? '&search_barber='.$search_barber : ''; ?>" aria-label="Last">
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
        // Set minimum date for the date picker to today
        document.getElementById('appointment_date').min = new Date().toISOString().split('T')[0];
        
        // Highlight today's date in the table
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.querySelectorAll('td:nth-child(4)').forEach(cell => {
                if (cell.textContent === today) {
                    cell.closest('tr').style.backgroundColor = '#fff9e6';
                }
            });
        });

// Delete confirmation handling
    $(document).on('click', '.delete-btn', function() {
        const appointmentId = $(this).data('id');
        const tab = $(this).data('tab');
        const date = $(this).data('date');
        const barber = $(this).data('barber');
        
        // Set the delete URL in the modal
        let deleteUrl = `?delete=${appointmentId}&tab=${tab}`;
        if (date) deleteUrl += `&search_date=${date}`;
        if (barber) deleteUrl += `&search_barber=${barber}`;
        
        $('#confirmActionBtn').attr('href', deleteUrl);
        $('#confirmationModal').modal('show');
    });
    </script>
</body>
</html>