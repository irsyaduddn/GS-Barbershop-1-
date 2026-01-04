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

// Handle barber addition
if ($_POST && isset($_POST['add_barber'])) {
    $barber_name = $_POST['barber_name'];
    $barber_email = $_POST['barber_email'];
    $barber_password = $_POST['barber_password'];
    $barber_phone = $_POST['barber_phone'];

    $sql = "INSERT INTO barbers (barber_name, barber_email, barber_password, barber_phone) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $barber_name, $barber_email, $barber_password, $barber_phone);

    if ($stmt->execute()) {
        // Insert into web_users table
        $webuser_sql = "INSERT INTO web_users (email, user_type) VALUES (?, ?)";
        $webuser_stmt = $conn->prepare($webuser_sql);
        $user_type = 'barber';
        $webuser_stmt->bind_param("ss", $barber_email, $user_type);

        if ($webuser_stmt->execute()) {
            $message = "<div class='alert alert-success'>Barber added successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Failed to add barber to web_users table. Please try again.</div>";
        }

        $webuser_stmt->close();
    } else {
        $message = "<div class='alert alert-danger'>Failed to add barber. Please try again.</div>";
    }

    $stmt->close();
}

// Handle barber deletion
if (isset($_GET['delete'])) {
    $barber_id = $_GET['delete'];

    // Fetch barber email before deletion
    $email_query = "SELECT barber_email FROM barbers WHERE barber_id = ?";
    $email_stmt = $conn->prepare($email_query);
    $email_stmt->bind_param("i", $barber_id);
    $email_stmt->execute();
    $email_stmt->bind_result($barber_email);
    $email_stmt->fetch();
    $email_stmt->close();

    $sql = "DELETE FROM barbers WHERE barber_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $barber_id);

    if ($stmt->execute()) {
        // Delete from web_users table
        $webuser_delete_sql = "DELETE FROM web_users WHERE email = ?";
        $webuser_delete_stmt = $conn->prepare($webuser_delete_sql);
        $webuser_delete_stmt->bind_param("s", $barber_email);
        $webuser_delete_stmt->execute();
        $webuser_delete_stmt->close();

        $message = "<div class='alert alert-success'>Barber deleted successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Failed to delete barber. Please try again.</div>";
    }

    $stmt->close();
}

// Handle barber editing
if ($_POST && isset($_POST['edit_barber'])) {
    $barber_id = $_POST['barber_id'];
    $barber_name = $_POST['barber_name'];
    $barber_email = $_POST['barber_email'];
    $barber_phone = $_POST['barber_phone'];

    $sql = "UPDATE barbers SET barber_name = ?, barber_email = ?, barber_phone = ? WHERE barber_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $barber_name, $barber_email, $barber_phone, $barber_id);

    if ($stmt->execute()) {
        // Update web_users table
        $webuser_update_sql = "UPDATE web_users SET email = ? WHERE email = ?";
        $webuser_update_stmt = $conn->prepare($webuser_update_sql);
        $webuser_update_stmt->bind_param("ss", $barber_email, $_POST['original_email']);
        $webuser_update_stmt->execute();
        $webuser_update_stmt->close();

        $message = "<div class='alert alert-success'>Barber updated successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Failed to update barber. Please try again.</div>";
    }

    $stmt->close();
}

// Fetch all barbers
$sql = "SELECT * FROM barbers";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Barbers - GS Barbershop</title>
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
        
        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #212529;
        }
        
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
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
                    <li class="nav-item"><a class="nav-link" href="manage_schedule.php">Manage Schedule</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_bookedappointments.php">Manage Appointments</a></li>
                    <li class="nav-item active"><a class="nav-link" href="manage_barbers.php">Manage Barbers</a></li>
                    <li class="nav-item"><a class="nav-link" href="track_payments.php">Track Payments</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <h1 class="mb-4">Manage Barbers</h1>

        <?php echo $message; ?>

        <!-- Add Barber Button -->
        <button class="btn btn-primary mb-4" data-toggle="modal" data-target="#addBarberModal">
            <i class="fas fa-plus"></i> Add Barber
        </button>

        <!-- Add Barber Modal -->
        <div class="modal fade" id="addBarberModal" tabindex="-1" role="dialog" aria-labelledby="addBarberModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addBarberModalLabel">Add New Barber</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="barber_name">Full Name</label>
                                <input type="text" name="barber_name" id="barber_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="barber_email">Email</label>
                                <input type="email" name="barber_email" id="barber_email" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="barber_password">Password</label>
                                <input type="password" name="barber_password" id="barber_password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="barber_phone">Phone</label>
                                <input type="text" name="barber_phone" id="barber_phone" class="form-control" required>
                            </div>
                            <button type="submit" name="add_barber" class="btn btn-primary">Add Barber</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Barber Modal -->
        <div class="modal fade" id="editBarberModal" tabindex="-1" role="dialog" aria-labelledby="editBarberModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editBarberModalLabel">Edit Barber</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="">
                            <input type="hidden" name="barber_id" id="edit_barber_id">
                            <input type="hidden" name="original_email" id="edit_original_email">
                            <div class="form-group">
                                <label for="edit_barber_name">Full Name</label>
                                <input type="text" name="barber_name" id="edit_barber_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="edit_barber_email">Email</label>
                                <input type="email" name="barber_email" id="edit_barber_email" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="edit_barber_phone">Phone</label>
                                <input type="text" name="barber_phone" id="edit_barber_phone" class="form-control" required>
                            </div>
                            <button type="submit" name="edit_barber" class="btn btn-primary">Update Barber</button>
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
                <p id="confirmationMessage">Are you sure you want to delete this barber?</p>
                <p class="text-danger">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-cancel" data-dismiss="modal">Cancel</button>
                <a id="confirmDeleteBtn" href="#" class="btn btn-danger btn-confirm">Delete</a>
            </div>
        </div>
    </div>
</div>

        <!-- Barber List Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>No.</th>
                        <th>Barber Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php $count = 1; while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td><?php echo htmlspecialchars($row['barber_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['barber_email']); ?></td>
                                <td><?php echo htmlspecialchars($row['barber_phone']); ?></td>
                                <td>
                                     <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editBarberModal" onclick="editBarber(<?php echo htmlspecialchars(json_encode($row)); ?>)">
        <i class="fas fa-edit"></i> Edit
    </button>
    <button class="btn btn-danger btn-sm delete-btn" data-id="<?php echo $row['barber_id']; ?>">
        <i class="fas fa-trash-alt"></i> Delete
    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No barbers found.</td>
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
        function editBarber(barber) {
            document.getElementById('edit_barber_id').value = barber.barber_id;
            document.getElementById('edit_original_email').value = barber.barber_email;
            document.getElementById('edit_barber_name').value = barber.barber_name;
            document.getElementById('edit_barber_email').value = barber.barber_email;
            document.getElementById('edit_barber_phone').value = barber.barber_phone;
        }

        // Delete confirmation handling
    $(document).on('click', '.delete-btn', function() {
        const barberId = $(this).data('id');
        $('#confirmDeleteBtn').attr('href', '?delete=' + barberId);
        $('#confirmationModal').modal('show');
    });
    </script>
</body>
</html>