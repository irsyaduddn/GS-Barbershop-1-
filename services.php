<?php
// Start session
session_start();

// Check if the user is logged in and is a customer
if (!isset($_SESSION['user']) || $_SESSION['usertype'] !== 'customer') {
    header("Location: login.php");
    exit();
}

// Include database connection
include("db_connect.php");

// Fetch user information from the database
$email = $_SESSION['user'];
$sql_user = "SELECT customer_name, customer_photo FROM customers WHERE customer_email = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("s", $email);
$stmt_user->execute();
$stmt_user->bind_result($full_name, $profile_picture);
$stmt_user->fetch();
$stmt_user->close();

// Fetch all services
$sql = "SELECT * FROM services";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="styles.css">
    <style>
        .service-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            padding: 20px;
            background-color: white;
        }

        .service-title {
            font-size: 20px;
            font-weight: bold;
            color: #007bff;
        }

        .service-price {
            font-size: 18px;
            color: #28a745;
            font-weight: bold;
        }

        .service-description {
            margin-top: 10px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <header class="top-header">
        <div class="logo-container">
            <img src="logo.jpg" alt="GS Barbershop Logo" class="logo">
            <div class="logo-title">GS Barbershop</div>
        </div>
        <div class="user-profile">
            <img src="<?php echo htmlspecialchars($profile_picture ?: 'default_profile.png'); ?>" alt="User" class="user-img">
            <div class="dropdown">
                <button>
                    <?php echo htmlspecialchars($full_name); ?> &#9662;
                </button>
                <div class="dropdown-content">
                    <a href="profile.php">Profile</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation Links -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="home.php">Home</a></li>
                    <li class="nav-item active"><a class="nav-link" href="services.php">Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="book_appointment.php">Book Appointment</a></li>
                    <li class="nav-item"><a class="nav-link" href="view_appointments.php">My Appointments</a></li>
                    <li class="nav-item"><a class="nav-link" href="about_us.php">About Us</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1 class="text-center mb-4">Our Services</h1>

        <?php if ($result->num_rows > 0): ?>
            <div class="row">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="col-md-4">
                        <div class="service-card">
                            <div class="service-title"> <?php echo htmlspecialchars($row['service_name']); ?> </div>
                            <div class="service-price">RM <?php echo number_format($row['service_price'], 2); ?> </div>
                            <div class="service-description"> <?php echo htmlspecialchars($row['description']); ?> </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-center">No services available at the moment. Please check back later.</p>
        <?php endif; ?>
    </div>
</body>
</html>
