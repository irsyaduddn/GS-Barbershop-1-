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

// Fetch barber information
$email = $_SESSION['user'];
$sql_barber = "SELECT barber_id, barber_name FROM barbers WHERE barber_email = ?";
$stmt_barber = $conn->prepare($sql_barber);
if ($stmt_barber === false) {
    die("Error preparing statement: " . $conn->error);
}
$stmt_barber->bind_param("s", $email);
$stmt_barber->execute();
$stmt_barber->bind_result($barber_id, $barber_name);
$stmt_barber->fetch();
$stmt_barber->close();

// Update appointment status
$message = "";
if (isset($_POST['update_status'])) {
    $appointment_id = $_POST['appointment_id'];
    $new_status = $_POST['status'];

    // First check current status to prevent updating cancelled appointments
    $sql_check = "SELECT status FROM appointments WHERE appointment_id = ? AND barber_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $appointment_id, $barber_id);
    $stmt_check->execute();
    $stmt_check->bind_result($current_status);
    $stmt_check->fetch();
    $stmt_check->close();

    if ($current_status === 'Cancelled') {
        $message = "<div class='alert alert-warning'>Cannot update a cancelled appointment.</div>";
    } else {
        $sql_update = "UPDATE appointments SET status = ? WHERE appointment_id = ? AND barber_id = ?";
        $stmt_update = $conn->prepare($sql_update);
        if ($stmt_update === false) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmt_update->bind_param("sii", $new_status, $appointment_id, $barber_id);

        if ($stmt_update->execute()) {
            $message = "<div class='alert alert-success'>Appointment status updated successfully!</div>";
            // Refresh to show updated status
            header("Refresh:0");
        } else {
            $message = "<div class='alert alert-danger'>Failed to update appointment status. Please try again.</div>";
        }

        $stmt_update->close();
    }
}

// Get current date for filtering appointments
$current_date = date('Y-m-d');

// Check which tab is active (default to upcoming)
$active_tab = isset($_GET['tab']) && $_GET['tab'] === 'past' ? 'past' : 'upcoming';

// Fetch appointments based on active tab
if ($active_tab === 'upcoming') {
    // Fetch upcoming appointments (including today's)
    $sql_appointments = "SELECT a.appointment_id, c.customer_name, s.service_name, 
                                a.appointment_date, a.appointment_time, a.status 
                         FROM appointments a 
                         JOIN customers c ON a.customer_id = c.customer_id 
                         JOIN services s ON a.service_id = s.service_id 
                         WHERE a.barber_id = ? AND (a.appointment_date >= ? AND a.status != 'Completed')
                         ORDER BY a.appointment_date ASC, a.appointment_time ASC";
} else {
    // Fetch past appointments
    $sql_appointments = "SELECT a.appointment_id, c.customer_name, s.service_name, 
                                a.appointment_date, a.appointment_time, a.status 
                         FROM appointments a 
                         JOIN customers c ON a.customer_id = c.customer_id 
                         JOIN services s ON a.service_id = s.service_id 
                         WHERE a.barber_id = ? AND (a.appointment_date < ? OR a.status = 'Completed' OR a.status = 'Cancelled')
                         ORDER BY a.appointment_date DESC, a.appointment_time DESC";
}

$stmt_appointments = $conn->prepare($sql_appointments);
if ($stmt_appointments === false) {
    die("Error preparing statement: " . $conn->error);
}
$stmt_appointments->bind_param("is", $barber_id, $current_date);
$stmt_appointments->execute();
$result_appointments = $stmt_appointments->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barber Appointments - GS Barbershop</title>
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
                    <li class="nav-item active"><a class="nav-link" href="barber_appointment.php">My Appointments</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_availability.php">Manage Availability</a></li>
                </ul>
            </div>
        </div>
    </nav>

   <!-- Main Content -->
    <div class="container">
        <h1 class="mb-4">My Appointments</h1>

        <?php echo $message; ?>

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

        <!-- Appointment List Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Customer Name</th>
                        <th>Service Name</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_appointments->num_rows > 0): ?>
                        <?php 
                        $count = 1; 
                        $today = date('Y-m-d');
                        while ($row = $result_appointments->fetch_assoc()): 
                            // Check if appointment is today for highlighting
                            $isToday = ($row['appointment_date'] == $today);
                        ?>
                            <tr class="<?php echo $isToday ? 'today-appointment' : ''; ?>">
                                <td><?php echo $count++; ?></td>
                                <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['service_name']); ?></td>
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
                                <td>
                                    <?php if ($row['status'] === 'Cancelled'): ?>
                                        <span class="text-muted">Cancelled</span>
                                    <?php elseif ($row['status'] === 'Completed'): ?>
                                        <span class="text-muted">Completed</span>
                                    <?php else: ?>
                                        <form method="POST" action="" class="form-inline">
                                            <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
                                            <select name="status" class="form-control form-control-sm mr-1" required>
                                                <option value="Confirmed" <?php echo $row['status'] === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                <option value="Completed" <?php echo $row['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-primary btn-sm">Update</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">
                                <?php echo $active_tab === 'upcoming' 
                                    ? 'No upcoming appointments found.' 
                                    : 'No past appointments found.'; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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
            document.querySelectorAll('td:nth-child(4)').forEach(cell => {
                if (cell.textContent === today) {
                    cell.closest('tr').classList.add('today-appointment');
                }
            });
        });
    </script>
</body>
</html>