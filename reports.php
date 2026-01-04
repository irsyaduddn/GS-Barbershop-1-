<?php
session_start();

// Set timezone (adjust to your location)
date_default_timezone_set('Asia/Kuala_Lumpur');

if (!isset($_SESSION['user']) || $_SESSION['usertype'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include("db_connect.php");

// Verify connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Get date range for reports
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Function to safely prepare and execute queries
function executeQuery($conn, $sql, $params = [], $param_types = "") {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error . "<br>SQL: " . $sql);
    }
    
    if (!empty($params)) {
        if (!$stmt->bind_param($param_types, ...$params)) {
            die("Parameter binding failed: " . $stmt->error);
        }
    }
    
    if (!$stmt->execute()) {
        die("Query execution failed: " . $stmt->error);
    }
    
    return $stmt;
}

// In the statistics query:
$sql_stats = "SELECT 
                COUNT(*) as total_appointments,
                SUM(CASE WHEN status = 'Confirmed' THEN 1 ELSE 0 END) as total_booked_appointments,  -- Filter for 'Confirmed' status
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_appointments,
                SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_appointments,
                SUM(CASE WHEN status = 'Completed' THEN total_amount ELSE 0 END) as total_revenue,
                AVG(CASE WHEN status = 'Completed' THEN total_amount ELSE NULL END) as avg_revenue,
                SUM(CASE WHEN status = 'Completed' THEN total_amount ELSE 0 END) as total_sale
              FROM appointments
              WHERE appointment_date BETWEEN ? AND ? 
              AND is_booked = 1"; 

// Execute the query to fetch statistics data
$stmt_stats = executeQuery($conn, $sql_stats, [$start_date, $end_date], "ss");
$stats = $stmt_stats->get_result()->fetch_assoc();
$stmt_stats->close();

// In the service revenue query:
$sql_service_revenue = "SELECT 
                          s.service_name, 
                          COUNT(a.appointment_id) as appointment_count,
                          SUM(a.total_amount) as total_revenue
                        FROM appointments a
                        JOIN services s ON a.service_id = s.service_id
                        WHERE a.status = 'Completed' 
                        AND a.appointment_date BETWEEN ? AND ?
                        GROUP BY s.service_name
                        ORDER BY total_revenue DESC";

// Execute the query to fetch service revenue data
$stmt_service_revenue = executeQuery($conn, $sql_service_revenue, [$start_date, $end_date], "ss");
$service_revenue = $stmt_service_revenue->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_service_revenue->close();

// In the barber revenue query:
$sql_barber_revenue = "SELECT 
                         b.barber_name, 
                         COUNT(a.appointment_id) as appointment_count,
                         SUM(a.total_amount * 0.6) as barber_revenue,
                         SUM(a.total_amount) as total_service_amount
                       FROM appointments a
                       JOIN barbers b ON a.barber_id = b.barber_id
                       WHERE a.status = 'Completed' 
                       AND a.appointment_date BETWEEN ? AND ? 
                       GROUP BY b.barber_name
                       ORDER BY barber_revenue DESC";

// Execute the query to fetch barber revenue data
$stmt_barber_revenue = executeQuery($conn, $sql_barber_revenue, [$start_date, $end_date], "ss");
$barber_revenue = $stmt_barber_revenue->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_barber_revenue->close();

$sql_service_trend = "SELECT 
                        s.service_name,
                        a.variation,
                        COUNT(a.appointment_id) AS booking_count,
                        SUM(a.total_amount) AS total_revenue
                      FROM appointments a
                      JOIN services s ON a.service_id = s.service_id
                      WHERE a.status = 'Completed' 
                      AND a.appointment_date BETWEEN ? AND ?
                      GROUP BY s.service_name, a.variation
                      ORDER BY total_revenue DESC";

// Execute the query to fetch service trend data
$stmt_service_trend = executeQuery($conn, $sql_service_trend, [$start_date, $end_date], "ss");
$service_trend = $stmt_service_trend->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_service_trend->close();

// Fetch total appointments
$sql_recent = "SELECT 
                 a.appointment_id,
                 a.appointment_date,
                 a.appointment_time,
                 c.customer_name,
                 b.barber_name,
                 s.service_name,
                 a.status,
                 a.total_amount,
                 a.variation
               FROM appointments a
               JOIN customers c ON a.customer_id = c.customer_id
               JOIN barbers b ON a.barber_id = b.barber_id
               JOIN services s ON a.service_id = s.service_id
               WHERE a.appointment_date BETWEEN ? AND ?
               ORDER BY a.appointment_date DESC, a.appointment_time DESC
               LIMIT 10";

// Prepare and execute the query
$stmt_recent = executeQuery($conn, $sql_recent, [$start_date, $end_date], "ss");
$recent_appointments = $stmt_recent->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_recent->close();

// Check if the Export to PDF button is clicked
if (isset($_POST['export_pdf'])) {
    require('libs/fpdf.php');
    
    // Create new PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    
    // Add logo (make sure the path is correct)
    $pdf->Image('logo.jpg', 10, 10, 30); // Adjust path and dimensions as needed
    
    // Set title
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(34, 34, 34); // Dark color
    $pdf->Cell(0, 20, '', 0, 1); // Spacer
    $pdf->Cell(0, 10, 'GS BARBERSHOP - SALES REPORT', 0, 1, 'C');
    
    // Add decorative line
    $pdf->SetDrawColor(184, 157, 90); // Primary color
    $pdf->SetLineWidth(0.5);
    $pdf->Line(20, 45, 190, 45);
    $pdf->Ln(10);  // Line break

    // Add date range with better formatting
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(90, 90, 90);
    $pdf->Cell(0, 8, 'REPORT PERIOD:', 0, 1);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, date('F j, Y', strtotime($start_date)) . ' to ' . date('F j, Y', strtotime($end_date)), 0, 1);
    $pdf->Ln(8);
    
    // Add generated date
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 6, 'Generated on: ' . date('F j, Y, g:i a'), 0, 1);
    $pdf->Ln(10);

    // Statistics Data with improved layout
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(0, 10, 'SUMMARY STATISTICS', 0, 1, 'L', true);
    $pdf->Ln(5);
    
    // Create summary table
    $pdf->SetFont('Arial', '', 11);
    
    // Row 1
    $pdf->Cell(95, 10, 'Total Appointments', 1, 0, 'L');
    $pdf->Cell(95, 10, $stats['total_appointments'], 1, 1, 'C');
    
    // Row 2
    $pdf->Cell(95, 10, 'Completed Appointments', 1, 0, 'L');
    $pdf->Cell(95, 10, $stats['completed_appointments'], 1, 1, 'C');
    
    // Row 3
    $pdf->Cell(95, 10, 'Cancelled Appointments', 1, 0, 'L');
    $pdf->Cell(95, 10, $stats['cancelled_appointments'], 1, 1, 'C');
    
    // Row 4
    $pdf->Cell(95, 10, 'Total Revenue', 1, 0, 'L');
    $pdf->Cell(95, 10, 'RM ' . number_format($stats['total_revenue'], 2), 1, 1, 'C');
    
    $pdf->Ln(15);

    // Barber Revenue with header
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(0, 10, 'BARBER PERFORMANCE', 0, 1, 'L', true);
    $pdf->Ln(5);
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(60, 10, 'Barber', 1, 0, 'C', true);
    $pdf->Cell(40, 10, 'Bookings', 1, 0, 'C', true);
    $pdf->Cell(45, 10, 'Total Revenue', 1, 0, 'C', true);
    $pdf->Cell(45, 10, 'Barber Earnings', 1, 1, 'C', true);
    
    $pdf->SetFont('Arial', '', 10);
    foreach ($barber_revenue as $barber) {
        $pdf->Cell(60, 10, $barber['barber_name'], 1);
        $pdf->Cell(40, 10, $barber['appointment_count'], 1, 0, 'C');
        $pdf->Cell(45, 10, 'RM ' . number_format($barber['total_service_amount'], 2), 1, 0, 'C');
        $pdf->Cell(45, 10, 'RM ' . number_format($barber['barber_revenue'], 2), 1, 1, 'C');
    }
    $pdf->Ln(15);

    // Service Trend with header
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(0, 10, 'SERVICE TREND', 0, 1, 'L', true);
    $pdf->Ln(5);
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(90, 10, 'Service Name', 1, 0, 'C', true);
    $pdf->Cell(50, 10, 'Style', 1, 0, 'C', true);
    $pdf->Cell(50, 10, 'Bookings', 1, 1, 'C', true);
    
    $pdf->SetFont('Arial', '', 10);
    foreach ($service_trend as $trend) {
        $pdf->Cell(90, 10, $trend['service_name'], 1);
        $pdf->Cell(50, 10, $trend['variation'], 1, 0, 'C');
        $pdf->Cell(50, 10, $trend['booking_count'], 1, 1, 'C');
    }
    $pdf->Ln(15);

    // Footer
    $pdf->SetY(-15);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 10, 'Page ' . $pdf->PageNo(), 0, 0, 'C');

    // Output PDF to the browser
    $pdf->Output('GS_Barbershop_Report_'.date('Ymd').'.pdf', 'D');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports - GS Barbershop</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            padding-top: 160px;
        }
        
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
        
        /* Report Page Specific Styles */
        .report-header {
            padding: 30px 5% 20px;
            background-color: var(--light-color);
        }
        
        .date-filter {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-top: 4px solid var(--primary-color);
        }
        
        .stat-card h3 {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 15px;
        }
        
        .stat-card .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--dark-color);
        }
        
        .stat-card .stat-label {
            font-size: 0.9rem;
            color: #777;
        }
        
        .chart-container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 40px;
        }
        
        .table-container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 40px;
        }
        
        .table th {
            background-color: var(--dark-color);
            color: white;
        }
        
        .table td, .table th {
            vertical-align: middle;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-upcoming {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .revenue-badge {
            font-size: 1rem;
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 5px 10px;
            border-radius: 5px;
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
            
            .stat-card .stat-value {
                font-size: 1.8rem;
            }
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
            
            .status-card.barbers {
                border-left-color: var(--info-color);
            }
            
            .status-card.customers {
                border-left-color: var(--success-color);
            }
            
            .status-card.services {
                border-left-color: var(--warning-color);
            }
            
            .status-card.appointments {
                border-left-color: var(--danger-color);
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
            
            .status-card.barbers .count {
                color: var(--info-color);
            }
            
            .status-card.customers .count {
                color: var(--success-color);
            }
            
            .status-card.services .count {
                color: var(--warning-color);
            }
            
            .status-card.appointments .count {
                color: var(--danger-color);
            }
            
            .status-card .icon {
                font-size: 30px;
                float: right;
                opacity: 0.3;
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
                    <li class="nav-item"><a class="nav-link" href="track_payments.php">Track Payments</a></li>
                    <li class="nav-item active"><a class="nav-link" href="reports.php">Reports</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
<div class="container">
        <!-- Report Header -->
        <div class="report-header">
            <h1><i class="fas fa-chart-line"></i> Reports</h1>
        </div>

        <!-- Date Filter -->
        <div class="date-filter">
            <form method="GET" action="reports.php" class="form-inline">
                <div class="form-group mr-3">
                    <label for="start_date" class="mr-2">From:</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="form-group mr-3">
                    <label for="end_date" class="mr-2">To:</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="reports.php" class="btn btn-outline-secondary ml-2">Reset</a>
            </form>
            <div class="mt-2">
                <small class="text-muted">Showing data from <?php echo date('M j, Y', strtotime($start_date)); ?> to <?php echo date('M j, Y', strtotime($end_date)); ?></small>
            </div>
            <form method="POST" action="reports.php">
    <button type="submit" name="export_pdf" class="btn btn-success">Export to PDF</button>
</form>
        </div>

        

<div class="stats-container">
    <div class="stat-card">
        <h3>Total Appointments</h3>
        <div class="stat-value"><?php echo $stats['total_appointments']; ?></div>
        <div class="stat-label">All booked appointments</div>
    </div>
    
    <div class="stat-card">
        <h3>Completed</h3>
        <div class="stat-value"><?php echo $stats['completed_appointments']; ?></div>
        <div class="stat-label">Successful services</div>
    </div>
    
    <div class="stat-card">
        <h3>Cancelled</h3>
        <div class="stat-value"><?php echo $stats['cancelled_appointments']; ?></div>
        <div class="stat-label">Missed opportunities</div>
    </div>
    
    <div class="stat-card">
        <h3>Total Revenue</h3>
        <div class="stat-value">RM <?php echo number_format($stats['total_revenue'], 2); ?></div>
        <div class="stat-label">From completed services</div>
    </div>
</div>



        <!-- Service Trend Table -->
<div class="table-container">
    <h3 class="mb-4"><i class="fas fa-chart-line"></i> Service Trend</h3>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Style</th>
                    <th>Bookings</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($service_trend as $trend): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($trend['service_name']); ?></td>
                        <td><?php echo htmlspecialchars($trend['variation']); ?></td>
                        <td><?php echo $trend['booking_count']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>


        <!-- Revenue by Barber -->
        <div class="table-container">
            <h3 class="mb-4"><i class="fas fa-user-tie"></i> Revenue by Barber</h3>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Barber</th>
                            <th>Appointments</th>
                            <th>Total Revenue</th>
                            <th>Barber Earnings</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($barber_revenue as $barber): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($barber['barber_name']); ?></td>
                                <td><?php echo $barber['appointment_count']; ?></td>
                                <td>RM <?php echo number_format($barber['total_service_amount'], 2); ?></td>
                                <td>RM <?php echo number_format($barber['barber_revenue'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Appointments -->
        <div class="table-container">
            <h3 class="mb-4"><i class="far fa-calendar-alt"></i> Recent Appointments</h3>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Customer</th>
                            <th>Barber</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_appointments as $appointment): ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></td>
                                <td><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></td>
                                <td><?php echo htmlspecialchars($appointment['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['barber_name']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($appointment['status']); ?>">
                                        <?php echo htmlspecialchars($appointment['status']); ?>
                                    </span>
                                </td>
                                <td>RM <?php echo number_format($appointment['total_amount'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
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
        // Initialize revenue chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            
            const labels = <?php echo json_encode(array_column($monthly_data, 'month')); ?>;
            const data = <?php echo json_encode(array_column($monthly_data, 'monthly_revenue')); ?>;
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Monthly Revenue (RM)',
                        data: data,
                        backgroundColor: 'rgba(184, 157, 90, 0.7)',
                        borderColor: 'rgba(184, 157, 90, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Amount (RM)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Month'
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>