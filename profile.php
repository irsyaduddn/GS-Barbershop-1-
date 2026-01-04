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
$sql_user = "SELECT customer_id, customer_name, customer_email, customer_photo, customer_age, customer_phone FROM customers WHERE customer_email = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("s", $email);
$stmt_user->execute();
$stmt_user->bind_result($customer_id, $customer_name, $customer_email, $customer_photo, $customer_age, $customer_phone);
$stmt_user->fetch();
$stmt_user->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updated_name = $_POST['customer_name'];
    $updated_email = $_POST['customer_email'];
    $updated_age = $_POST['customer_age'];
    $updated_phone = $_POST['customer_phone'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if passwords match
    if (!empty($new_password) && $new_password !== $confirm_password) {
        echo "<script>alert('Passwords do not match. Please try again.');</script>";
    } else {
        // Handle profile picture upload
        $profile_picture_path = $customer_photo; // Keep the current profile picture if not updated
        if (isset($_FILES['customer_photo']) && $_FILES['customer_photo']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "uploads/";
            $target_file = $target_dir . basename($_FILES["customer_photo"]["name"]);
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $valid_extensions = ["jpg", "jpeg", "png", "gif"];

            if (in_array($imageFileType, $valid_extensions)) {
                if (move_uploaded_file($_FILES["customer_photo"]["tmp_name"], $target_file)) {
                    $profile_picture_path = $target_file;
                } else {
                    echo "<script>alert('Failed to upload the profile picture.');</script>";
                }
            } else {
                echo "<script>alert('Invalid file format. Only JPG, JPEG, PNG, and GIF files are allowed.');</script>";
            }
        }

        // Update the database
        $sql_update = "UPDATE customers SET customer_name = ?, customer_email = ?, customer_photo = ?, customer_age = ?, customer_phone = ?" . 
                      (!empty($new_password) ? ", customer_password = ?" : "") . 
                      " WHERE customer_id = ?";
        $stmt_update = $conn->prepare($sql_update);

        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT); // Hash the new password
            $stmt_update->bind_param("ssssssi", $updated_name, $updated_email, $profile_picture_path, $updated_age, $updated_phone, $hashed_password, $customer_id);
        } else {
            $stmt_update->bind_param("sssssi", $updated_name, $updated_email, $profile_picture_path, $updated_age, $updated_phone, $customer_id);
        }

        if ($stmt_update->execute()) {
            $_SESSION['user'] = $updated_email; // Update session email if changed
            echo "<script>alert('Profile updated successfully!'); window.location.href='profile.php';</script>";
            exit();
        } else {
            echo "<script>alert('Failed to update profile. Please try again.');</script>";
        }
        $stmt_update->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GS Barbershop - My Profile</title>
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
        
        /* Profile Content */
        .profile-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background-color: var(--primary-color);
        }
        
        .profile-picture-container {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .profile-header img {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: var(--transition);
        }
        
        .profile-picture-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background-color: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: var(--transition);
            cursor: pointer;
        }
        
        .profile-picture-overlay i {
            color: white;
            font-size: 1.5rem;
        }
        
        .profile-picture-container:hover .profile-picture-overlay {
            opacity: 1;
        }
        
        .profile-picture-container:hover img {
            transform: scale(1.03);
        }
        
        .profile-header h2 {
            color: var(--dark-color);
            font-size: 2.2rem;
            margin-bottom: 10px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .profile-header p {
            color: #666;
            font-size: 1rem;
        }
        
        .profile-form {
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: var(--transition);
        }
        
        .profile-form:hover {
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
            display: block;
            font-size: 0.95rem;
        }
        
        .form-control {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 12px 15px;
            transition: var(--transition);
            height: auto;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(184, 157, 90, 0.25);
        }
        
        .form-control-file {
            padding: 10px 0;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 6px;
            transition: var(--transition);
            font-size: 0.9rem;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        /* Password Toggle */
        .password-toggle {
            position: relative;
        }
        
        .password-toggle-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #777;
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
        
        /* Responsive adjustments */
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
            
            .profile-form {
                padding: 30px 20px;
            }
            
            .navbar-dark .navbar-nav .active > .nav-link::after {
                display: none;
            }
            
            .profile-header h2 {
                font-size: 1.8rem;
            }
        }
        
        /* Animation for form elements */
        .form-group {
            animation: fadeInUp 0.5s ease forwards;
            opacity: 0;
            transform: translateY(20px);
        }
        
        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Add delay to each form group */
        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }
        .form-group:nth-child(4) { animation-delay: 0.4s; }
        .form-group:nth-child(5) { animation-delay: 0.5s; }
        .form-group:nth-child(6) { animation-delay: 0.6s; }
        .form-group:nth-child(7) { animation-delay: 0.7s; }
        /* Profile Content Styles Only */
        .profile-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 40px;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .profile-avatar {
            position: relative;
            margin-right: 30px;
        }
        
        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #f5f5f5;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .avatar-edit {
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 36px;
            height: 36px;
            background: #b89d5a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }
        
        .profile-info h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: #333;
        }
        
        .profile-info p {
            color: #777;
            margin-bottom: 15px;
        }
        
        .profile-stats {
            display: flex;
        }
        
        .stat-item {
            margin-right: 30px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: #b89d5a;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .profile-tabs {
            display: flex;
            border-bottom: 1px solid #eee;
            margin-bottom: 30px;
        }
        
        .tab-item {
            padding: 12px 25px;
            cursor: pointer;
            font-weight: 600;
            color: #777;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .tab-item.active {
            color: #b89d5a;
        }
        
        .tab-item.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background: #b89d5a;
        }
        
        .tab-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .form-card {
            margin-bottom: 30px;
        }
        
        .form-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            color: #444;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
            display: block;
            font-size: 0.95rem;
        }
        
        .form-control {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px 15px;
            transition: all 0.3s ease;
            height: auto;
        }
        
        .form-control:focus {
            border-color: #b89d5a;
            box-shadow: 0 0 0 0.2rem rgba(184, 157, 90, 0.25);
        }
        
        .input-group-password {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
            cursor: pointer;
        }
        
        .btn-save {
            background: #b89d5a;
            color: white;
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-save:hover {
            background: #9a834b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-avatar {
                margin-right: 0;
                margin-bottom: 20px;
            }
            
            .profile-stats {
                justify-content: center;
            }
            
            .profile-tabs {
                overflow-x: auto;
                white-space: nowrap;
                padding-bottom: 5px;
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
            <img src="<?php echo htmlspecialchars($customer_photo ?: 'default_profile.png'); ?>" alt="User" class="user-img">
            <div class="dropdown">
                <button>
                    <?php echo htmlspecialchars($customer_name); ?> <i class="fas fa-caret-down"></i>
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

    <!-- New Profile Content -->
    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <img src="<?php echo htmlspecialchars($customer_photo ?: 'default_profile.png'); ?>" alt="Profile Picture" class="profile-img" id="profile-img-preview">
                <div class="avatar-edit" onclick="document.getElementById('profile-photo-input').click()">
                    <i class="fas fa-pencil-alt"></i>
                </div>
                <input type="file" id="profile-photo-input" name="customer_photo" accept="image/*" style="display: none;">
            </div>
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($customer_name); ?></h2>
                <p><?php echo htmlspecialchars($customer_email); ?></p>
                <div class="profile-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo htmlspecialchars($customer_age); ?></div>
                        <div class="stat-label">Age</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><i class="fas fa-phone"></i></div>
                        <div class="stat-label"><?php echo htmlspecialchars($customer_phone); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><i class="fas fa-calendar-alt"></i></div>
                        <div class="stat-label">Member since <?php echo date("Y"); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="profile-tabs">
            <div class="tab-item active" onclick="switchTab('personal-info')">Personal Info</div>
            <div class="tab-item" onclick="switchTab('security')">Security</div>
        </div>
        
        <div class="tab-content">
            <form method="POST" enctype="multipart/form-data">
                <div id="personal-info" class="tab-pane active">
                    <div class="form-card">
                        <h3 class="form-title">Basic Information</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="customer_name">Full Name</label>
                                    <input type="text" class="form-control" id="customer_name" name="customer_name" value="<?php echo htmlspecialchars($customer_name); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="customer_email">Email Address</label>
                                    <input type="email" class="form-control" id="customer_email" name="customer_email" value="<?php echo htmlspecialchars($customer_email); ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="customer_age">Age</label>
                                    <input type="number" class="form-control" id="customer_age" name="customer_age" value="<?php echo htmlspecialchars($customer_age); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="customer_phone">Phone Number</label>
                                    <input type="text" class="form-control" id="customer_phone" name="customer_phone" value="<?php echo htmlspecialchars($customer_phone); ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="security" class="tab-pane" style="display: none;">
                    <div class="form-card">
                        <h3 class="form-title">Change Password</h3>
                        <div class="form-group input-group-password">
                            <label class="form-label" for="new_password">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                            <i class="" onclick="togglePassword('new_password', this)"></i>
                        </div>
                        <div class="form-group input-group-password">
                            <label class="form-label" for="confirm_password">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            <i class="" onclick="togglePassword('confirm_password', this)"></i>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-save">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-column">
                <h3>FOLLOW US</h3>
                <p>Stay connected with us on social media for the latest updates and promotions.</p>
                <div class="social-links">
                    <a href="https://www.facebook.com/people/Gsbarbershop/100063705272441/"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/gengsahak_barbershop/"><i class="fab fa-instagram"></i></a>
                </div>
                <div class="policy-link">
                    <a href="cancellation_policy.php"><i class="fas fa-file-alt"></i> Cancellation Policy</a>
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
                <p><i class="far fa-clock"></i> Saturday - Thursday: 10.30am - 6pm</p>
                <p><i class="far fa-clock"></i> Friday: Closed</p>
            </div>
        </div>
        
        <div class="copyright">
            <p>&copy; <?php echo date("Y"); ?> GS Barbershop. All Rights Reserved.</p>
        </div>
    </footer>

    <script>
        // Password toggle functionality
        function togglePassword(id, icon) {
            const input = document.getElementById(id);
            
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
        
        // Profile picture preview
        document.getElementById('profile-photo-input').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('profile-img-preview').src = event.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
        
        // Tab switching
        function switchTab(tabId) {
            // Hide all tab panes
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.style.display = 'none';
            });
            
            // Show selected tab pane
            document.getElementById(tabId).style.display = 'block';
            
            // Update active tab
            document.querySelectorAll('.tab-item').forEach(tab => {
                tab.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
        }
    </script>
</body>
</html>