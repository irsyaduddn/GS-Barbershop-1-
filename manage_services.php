
<?php
session_start();

// Set timezone (adjust to your location)
date_default_timezone_set('Asia/Kuala_Lumpur');

if (!isset($_SESSION['user']) || $_SESSION['usertype'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include("db_connect.php");
$message = "";

// Handle main service addition
if ($_POST && isset($_POST['add_service'])) {
    $service_name = $_POST['service_name'];
    $service_price = $_POST['service_price'];
    $description = $_POST['description'];
    $duration = $_POST['duration']; // Add this line

    // Update the SQL query
    $sql = "INSERT INTO services (service_name, service_price, description, duration) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdsi", $service_name, $service_price, $description, $duration); // Note the "i" for integer

    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>Service added successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Failed to add service. Please try again.</div>";
    }
    $stmt->close();
}

// Handle service variation addition
if ($_POST && isset($_POST['add_variation'])) {
    $service_id = $_POST['service_id'];
    $variation_name = $_POST['variation_name'];

    $sql = "INSERT INTO service_variations (service_id, variation_name) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $service_id, $variation_name);

    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>Service variation added successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Failed to add service variation. Please try again.</div>";
    }
    $stmt->close();
}

// Handle service update
if ($_POST && isset($_POST['update_service'])) {
    $service_id = $_POST['service_id'];
    $service_name = $_POST['service_name'];
    $service_price = $_POST['service_price'];
    $description = $_POST['description'];
    $duration = $_POST['duration'];

    $sql = "UPDATE services SET service_name = ?, service_price = ?, description = ?, duration = ? WHERE service_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdsii", $service_name, $service_price, $description, $duration, $service_id);

    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>Service updated successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Failed to update service. Please try again.</div>";
    }
    $stmt->close();
    
    // Refresh the page to show changes
    header("Location: manage_services.php");
    exit();
}

// Handle service deletion
if (isset($_GET['delete_service'])) {
    $service_id = $_GET['delete_service'];
    $sql = "DELETE FROM services WHERE service_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $stmt->close();
    $message = "<div class='alert alert-success'>Service deleted successfully!</div>";
}

// Handle variation deletion
if (isset($_GET['delete_variation'])) {
    $variation_id = $_GET['delete_variation'];
    $sql = "DELETE FROM service_variations WHERE variation_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $variation_id);
    $stmt->execute();
    $stmt->close();
    $message = "<div class='alert alert-success'>Service variation deleted successfully!</div>";
}

// Handle add-on creation
if ($_POST && isset($_POST['add_addon'])) {
    $addon_name = $_POST['addon_name'];
    $addon_price = $_POST['addon_price'];
    $description = $_POST['description'];
    
    $sql = "INSERT INTO addon_services (service_name, service_price, description) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sds", $addon_name, $addon_price, $description);

    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>Add-on service added successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Failed to add add-on service. Please try again.</div>";
    }
    $stmt->close();
}

// Handle addon-service relationship
if ($_POST && isset($_POST['link_addon'])) {
    $service_id = $_POST['service_id'];
    $addon_id = $_POST['addon_id'];
    
    // First check if the link already exists
    $check_sql = "SELECT * FROM service_addons WHERE service_id = ? AND addon_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $service_id, $addon_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $message = "<div class='alert alert-warning'>This add-on is already linked to the service.</div>";
    } else {
        // Verify both IDs exist
        $valid = true;
        
        // Check service exists
        $service_check = $conn->prepare("SELECT service_id FROM services WHERE service_id = ?");
        $service_check->bind_param("i", $service_id);
        $service_check->execute();
        if ($service_check->get_result()->num_rows === 0) {
            $valid = false;
            $message = "<div class='alert alert-danger'>Invalid service selected.</div>";
        }
        
        // Check addon exists
        $addon_check = $conn->prepare("SELECT addon_id FROM addon_services WHERE addon_id = ?");
        $addon_check->bind_param("i", $addon_id);
        $addon_check->execute();
        if ($addon_check->get_result()->num_rows === 0) {
            $valid = false;
            $message = "<div class='alert alert-danger'>Invalid add-on selected.</div>";
        }
        
        if ($valid) {
            $sql = "INSERT INTO service_addons (service_id, addon_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("ii", $service_id, $addon_id);
                if ($stmt->execute()) {
                    $message = "<div class='alert alert-success'>Add-on linked to service successfully!</div>";
                } else {
                    $message = "<div class='alert alert-danger'>Database error: " . $stmt->error . "</div>";
                }
                $stmt->close();
            } else {
                $message = "<div class='alert alert-danger'>Database preparation error: " . $conn->error . "</div>";
            }
        }
    }
    $check_stmt->close();
}

// Handle addon deletion
if (isset($_GET['delete_addon'])) {
    $addon_id = $_GET['delete_addon'];
    $sql = "DELETE FROM addon_services WHERE addon_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $addon_id);
    $stmt->execute();
    $stmt->close();
    $message = "<div class='alert alert-success'>Add-on service deleted successfully!</div>";
}

// Handle addon unlinking
if (isset($_GET['unlink_addon'])) {
    $service_id = $_GET['service_id'];
    $addon_id = $_GET['unlink_addon'];
    
    $sql = "DELETE FROM service_addons WHERE service_id = ? AND addon_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $service_id, $addon_id);
    
    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>Add-on unlinked successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Failed to unlink add-on. Please try again.</div>";
    }
    $stmt->close();
    
    // Refresh the page to show changes
    header("Location: manage_services.php");
    exit();
}

// Fetch all services first
$services = [];
$sql_services = "SELECT * FROM services ORDER BY service_name";
$result_services = $conn->query($sql_services);
while ($service = $result_services->fetch_assoc()) {
    $services[$service['service_id']] = [
        'service_name' => $service['service_name'],
        'service_price' => $service['service_price'],
        'description' => $service['description'],
        'duration' => $service['duration'],
        'variations' => [],
        'addons' => []
    ];
}

// Fetch variations for each service
$sql_variations = "SELECT * FROM service_variations ORDER BY variation_name";
$result_variations = $conn->query($sql_variations);
while ($variation = $result_variations->fetch_assoc()) {
    if (isset($services[$variation['service_id']])) {
        $services[$variation['service_id']]['variations'][] = [
            'variation_id' => $variation['variation_id'],
            'variation_name' => $variation['variation_name']
        ];
    }
}

// Fetch linked addons for each service
$sql_addons = "SELECT sa.service_id, a.addon_id, a.service_name as addon_name, a.service_price as addon_price 
               FROM service_addons sa
               JOIN addon_services a ON sa.addon_id = a.addon_id
               ORDER BY a.service_name";
$result_addons = $conn->query($sql_addons);
while ($addon = $result_addons->fetch_assoc()) {
    if (isset($services[$addon['service_id']])) {
        $services[$addon['service_id']]['addons'][] = [
            'addon_id' => $addon['addon_id'],
            'addon_name' => $addon['addon_name'],
            'addon_price' => $addon['addon_price']
        ];
    }
}

// Fetch all addon services for the link addon modal
$sql_all_addons = "SELECT * FROM addon_services ORDER BY service_name";
$addons_result = $conn->query($sql_all_addons);
$all_addons = [];
while ($addon = $addons_result->fetch_assoc()) {
    $all_addons[$addon['addon_id']] = $addon;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services - GS Barbershop</title>
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
        .variation-table {
            margin-top: 15px;
            margin-bottom: 30px;
        }
        .variation-table th {
            background-color: #f8f9fa;
        }
        .service-card {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .service-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .price-badge {
            font-size: 1.1rem;
            background-color: #f8f9fa;
            padding: 5px 10px;
            border-radius: 5px;
        }
        /* Add these to your existing CSS */
.addon-option {
    padding: 8px;
    border-bottom: 1px solid #eee;
}

.addon-option:last-child {
    border-bottom: none;
}

.addon-option .form-check-input {
    margin-right: 10px;
}

.addon-option .form-check-label {
    cursor: pointer;
}
.duration-badge {
    font-size: 0.9rem;
    background-color: #e9f7fe;
    color: #31708f;
    padding: 4px 8px;
    height: 35px;
    border-radius: 5px;
}
.duration-badge i {
font-size: 1.5rem;
}    
/* Smaller Button Styles */
.smaller-btn {
    padding: 2.5px 10px;  /* Reduce padding */
    font-size: 1.0  rem;  /* Smaller font size */
    height: 30px;  /* Adjust button height */
    border-radius: 20px;  /* Rounded corners */
}

.smaller-btn i {
    font-size: 1.0rem;  /* Adjust icon size */
}

/* Button Color Customization (Optional) */
.smaller-btn-green {
    background-color: #28a745; /* Green for Add Variation */
    border-color: #28a745;
}

.smaller-btn-green i {
     font-size: 1.0rem;
     color:rgb(254, 255, 254);
}

.smaller-btn-var {
    background-color: #28a745; /* Green for Add Variation */
    border-color: #28a745;
    color: white;
}

.smaller-btn-var i {
     font-size: 1.0rem;
     color:rgb(254, 255, 254);
}

.smaller-btn-red {
    background-color: #dc3545; /* Red for Delete Service */
    border-color: #dc3545;
}

.smaller-btn-green:hover {
    background-color: #218838; /* Darker green on hover */
}

.smaller-btn-red:hover {
    background-color: #c82333; /* Darker red on hover */
}
.smaller-btn-yellow {
    background-color:rgb(255, 242, 0);
    border-color:rgb(255, 242, 0);
}
.smaller-btn-yellow:hover {
    background-color:rgb(210, 200, 4); 
}
 .service-card {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 0;
            margin-bottom: 15px;
            overflow: hidden;
        }
        
        .service-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .service-header:hover {
            background-color: #e9ecef;
        }
        
        .service-header h5 {
            margin: 0;
            font-size: 1.1rem;
        }
        
        .service-header .badge {
            font-size: 0.9rem;
            margin-left: 10px;
        }
        
        .service-header .fa-chevron-down {
            transition: transform 0.3s;
        }
        
        .service-header.collapsed .fa-chevron-down {
            transform: rotate(-90deg);
        }
        
        .service-content {
            padding: 15px;
            border-top: 1px solid #dee2e6;
        }
        
        .variation-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .variation-item:last-child {
            border-bottom: none;
        }
        
        .action-buttons .btn {
            margin-left: 5px;
            padding: 3px 8px;
            font-size: 0.8rem;
        }
        
        .no-variations {
            color: #6c757d;
            font-style: italic;
            padding: 10px 0;
        }
        
        .add-variation-btn {
            margin-top: 10px;
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
                    <li class="nav-item active"><a class="nav-link" href="manage_services.php">Manage Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_schedule.php">Manage Schedule</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_bookedappointments.php">Manage Appointments</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_barbers.php">Manage Barbers</a></li>
                    <li class="nav-item"><a class="nav-link" href="track_payments.php">Track Payments</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1 class="mb-4">Manage Services</h1>
        <?php echo $message; ?>

        <!-- Add Service Button -->
        <button class="btn btn-primary mb-4" data-toggle="modal" data-target="#addServiceModal">
            <i class="fas fa-plus"></i> Add Service
        </button>
        
        <button class="btn btn-info mb-4 ml-2" data-toggle="modal" data-target="#addAddonModal">
            <i class="fas fa-plus"></i> Add Add-On Service
        </button>

        <button class="btn btn-secondary mb-4 ml-2" data-toggle="modal" data-target="#linkAddonModal">
            <i class="fas fa-link"></i> Link Add-On to Service
        </button>

        <!-- Add Service Modal -->
        <div class="modal fade" id="addServiceModal" tabindex="-1" role="dialog" aria-labelledby="addServiceModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addServiceModalLabel">Add New Service</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="service_name">Service Name</label>
                                <input type="text" name="service_name" id="service_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="service_price">Service Price</label>
                                <input type="number" step="0.01" name="service_price" id="service_price" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="duration">Duration (minutes)</label>
                                <input type="number" name="duration" id="duration" class="form-control" min="5" max="240" value="30" required>
                            </div>
                            <button type="submit" name="add_service" class="btn btn-primary">Add Service</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Service Modal -->
<div class="modal fade" id="editServiceModal" tabindex="-1" role="dialog" aria-labelledby="editServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editServiceModalLabel">Edit Service</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="service_id" id="edit_service_id">
                    <div class="form-group">
                        <label for="edit_service_name">Service Name</label>
                        <input type="text" name="service_name" id="edit_service_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_service_price">Service Price</label>
                        <input type="number" step="0.01" name="service_price" id="edit_service_price" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_duration">Duration (minutes)</label>
                        <input type="number" name="duration" id="edit_duration" class="form-control" min="5" max="240" required>
                    </div>
                    <button type="submit" name="update_service" class="btn btn-primary">Update Service</button>
                </form>
            </div>
        </div>
    </div>
</div>

        <!-- Add Variation Modal (Simplified) -->
        <div class="modal fade" id="addVariationModal" tabindex="-1" role="dialog" aria-labelledby="addVariationModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addVariationModalLabel">Add Service Variation</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="service_id">Service</label>
                                <select name="service_id" id="service_id" class="form-control" required>
                                    <?php foreach ($services as $id => $service): ?>
                                        <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($service['service_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="variation_name">Variation Name</label>
                                <input type="text" name="variation_name" id="variation_name" class="form-control" required>
                            </div>
                            <button type="submit" name="add_variation" class="btn btn-primary">Add Variation</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

         <!-- Add Add-On Modal -->
<div class="modal fade" id="addAddonModal" tabindex="-1" role="dialog" aria-labelledby="addAddonModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAddonModalLabel">Add New Add-On Service</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="addon_name">Add-On Name</label>
                        <input type="text" name="addon_name" id="addon_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="addon_price">Add-On Price</label>
                        <input type="number" step="0.01" name="addon_price" id="addon_price" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" name="add_addon" class="btn btn-primary">Add Add-On</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Link Add-On Modal -->
<div class="modal fade" id="linkAddonModal" tabindex="-1" role="dialog" aria-labelledby="linkAddonModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="linkAddonModalLabel">Link Add-On to Service</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="link_service_id">Service</label>
                        <select name="service_id" id="link_service_id" class="form-control" required>
                            <?php foreach ($services as $id => $service): ?>
                                <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($service['service_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="addon_id">Add-On Service</label>
                        <select name="addon_id" id="addon_id" class="form-control" required>
                            <?php foreach ($all_addons as $id => $addon): ?>
                                <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($addon['service_name']); ?> (RM<?php echo number_format($addon['service_price'], 2); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="link_addon" class="btn btn-primary">Link Add-On</button>
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
                    <p id="confirmationMessage">Are you sure you want to perform this action?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-cancel" data-dismiss="modal">Cancel</button>
                    <a id="confirmActionBtn" href="#" class="btn btn-danger btn-confirm">Confirm</a>
                </div>
            </div>
        </div>
    </div>

        <!-- Services List with Collapsible Cards -->
        <div class="services-list">
        <?php if (empty($services)): ?>
            <div class="alert alert-info">No services found. Add your first service.</div>
        <?php else: ?>
            <?php foreach ($services as $id => $service): ?>
                <div class="service-card">
                    <!-- Service Header (Always Visible) -->
                    <div class="service-header collapsed" data-toggle="collapse" data-target="#service-<?php echo $id; ?>">
    <div class="d-flex align-items-center">
        <h5 class="mb-0"><?php echo htmlspecialchars($service['service_name']); ?></h5>
        <span class="price-badge ml-2">RM<?php echo number_format($service['service_price'], 2); ?></span>
        <span class="duration-badge ml-2">
            <i class="far fa-clock"></i> <?php echo $service['duration']; ?> mins
        </span>
    </div>
    <div class="d-flex align-items-center">
        <button class="btn smaller-btn smaller-btn-green" 
                data-toggle="modal" 
                data-target="#editServiceModal"
                onclick="editService({
                    service_id: '<?php echo $id; ?>',
                    service_name: '<?php echo addslashes($service['service_name']); ?>',
                    service_price: '<?php echo $service['service_price']; ?>',
                    description: '<?php echo addslashes($service['description']); ?>',
                    duration: '<?php echo $service['duration']; ?>'
                })">
            <i class="fas fa-edit"></i> Edit
        </button>
        <a href="#" 
           class="btn smaller-btn smaller-btn-red" 
           data-toggle="modal" 
           data-target="#confirmationModal"
           data-message="Are you sure you want to delete this service and all its variations?"
           data-url="?delete_service=<?php echo $id; ?>">
            <i class="fas fa-trash-alt"></i> Delete
        </a>
        <i class="fas fa-chevron-down"></i>
    </div>
</div>
                    
                    <!-- Collapsible Content -->
                    <div id="service-<?php echo $id; ?>" class="collapse">
                        <div class="service-content p-3">
                            <?php if ($service['description']): ?>
                                <p class="mb-3">Description: <?php echo htmlspecialchars($service['description']); ?></p>
                            <?php endif; ?>
                            
                            <!-- Variations Section -->
                            <div class="variation-section mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="mb-0">Style</h5>
                                    <button class="btn smaller-btn smaller-btn-var" 
                                            data-toggle="modal" 
                                            data-target="#addVariationModal" 
                                            onclick="document.getElementById('service_id').value = '<?php echo $id; ?>';">
                                        <i class="fas fa-plus"></i> Add Style
                                    </button>
                                </div>
                                
                                <?php if (empty($service['variations'])): ?>
                                    <div class="alert alert-warning mb-0">No Style added yet.</div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Style Name</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($service['variations'] as $variation): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($variation['variation_name']); ?></td>
                                                        <td>
                                                             <a href="#" 
           class="btn smaller-btn smaller-btn-red" 
           data-toggle="modal" 
           data-target="#confirmationModal"
           data-message="Are you sure you want to delete this variation?"
           data-url="?delete_variation=<?php echo $variation['variation_id']; ?>">
            <i class="fas fa-trash-alt"></i> Delete
        </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Add-Ons Section -->
                            <?php if (!empty($service['addons'])): ?>
                                <div class="addons-section">
                                    <h5 class="mb-3 text-left">Linked Add-Ons</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Add-On Name</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($service['addons'] as $addon): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($addon['addon_name']); ?></td>
                                                        <td>
                                                            <a href="#" 
           class="btn smaller-btn smaller-btn-yellow" 
           data-toggle="modal" 
           data-target="#confirmationModal"
           data-message="Are you sure you want to unlink this add-on?"
           data-url="?service_id=<?php echo $id; ?>&unlink_addon=<?php echo $addon['addon_id']; ?>">
            <i class="fas fa-unlink"></i> Unlink
        </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
        
        <!-- Add-On Services List -->
        <div class="mt-5">
            <h3>Add-On Services</h3>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>No.</th>
                            <th>Add-On Name</th>
                            <th>Price</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_addons)): ?>
                            <tr>
                                <td colspan="4" class="text-center">No add-on services found.</td>
                            </tr>
                        <?php else: ?>
                            <?php $count = 1; foreach ($all_addons as $id => $addon): ?>
                                <tr>
                                    <td><?php echo $count++; ?></td>
                                    <td><?php echo htmlspecialchars($addon['service_name']); ?></td>
                                    <td>RM<?php echo number_format($addon['service_price'], 2); ?></td>
                                    <td>
                                         <a href="#" 
           class="btn btn-sm btn-outline-danger"
           data-toggle="modal" 
           data-target="#confirmationModal"
           data-message="Are you sure you want to delete this add-on service?"
           data-url="?delete_addon=<?php echo $id; ?>">
            <i class="fas fa-trash-alt"></i> Delete
        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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
        function editService(service) {
    document.getElementById('edit_service_id').value = service.service_id;
    document.getElementById('edit_service_name').value = service.service_name;
    document.getElementById('edit_service_price').value = service.service_price;
    document.getElementById('edit_description').value = service.description;
    document.getElementById('edit_duration').value = service.duration;
}
        // Add animation to chevron icon
        $(document).ready(function() {
            $('.service-header').click(function() {
                $(this).toggleClass('collapsed');
            });
        });

        // Confirmation modal handler
        $(document).ready(function() {
            $('#confirmationModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget); // Button that triggered the modal
                var message = button.data('message'); // Extract message from data-* attributes
                var url = button.data('url'); // Extract URL from data-* attributes
                
                var modal = $(this);
                modal.find('#confirmationMessage').text(message);
                modal.find('#confirmActionBtn').attr('href', url);
            });
            
            // Service header toggle remains the same
            $('.service-header').click(function() {
                $(this).toggleClass('collapsed');
            });
        });
    </script>
</body>
</html>