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
    $sql_user = "SELECT customer_id, customer_name, customer_photo FROM customers WHERE customer_email = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("s", $email);
    $stmt_user->execute();
    $stmt_user->bind_result($customer_id, $full_name, $profile_picture);
    $stmt_user->fetch();
    $stmt_user->close();

    // Check if service_id is passed
    if (!isset($_POST['service_id'])) {
        echo "<script>alert('Invalid service selected. Please go back and select a valid service.'); window.location.href='book_appointments.php';</script>";
        exit();
    }

    // Fetch service details based on service_id
    $service_id = $_POST['service_id'];
    $sql_service = "SELECT service_name, service_price FROM services WHERE service_id = ?";
    $stmt_service = $conn->prepare($sql_service);
    $stmt_service->bind_param("i", $service_id);
    $stmt_service->execute();
    $stmt_service->bind_result($service_name, $service_price);
    if (!$stmt_service->fetch()) {
        echo "<script>alert('Invalid service selected. Please go back and select a valid service.'); window.location.href='book_appointments.php';</script>";
        exit();
    }
    $stmt_service->close();

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['barber_id'], $_POST['appointment_date'], $_POST['appointment_time'], $_POST['variation'])) {
        $barber_id = $_POST['barber_id'];
        $appointment_date = $_POST['appointment_date'];
        $appointment_time = $_POST['appointment_time'];
        $variation = $_POST['variation'];
        $addons = isset($_POST['addons']) ? $_POST['addons'] : [];
        
        // First, find the existing unbooked appointment
        $sql_find = "SELECT appointment_id FROM appointments 
                    WHERE barber_id = ? 
                    AND appointment_date = ? 
                    AND appointment_time = ? 
                    AND is_booked = 0
                    LIMIT 1";
        $stmt_find = $conn->prepare($sql_find);
        $stmt_find->bind_param("iss", $barber_id, $appointment_date, $appointment_time);
        $stmt_find->execute();
        $stmt_find->bind_result($appointment_id);
        $stmt_find->fetch();
        $stmt_find->close();
        
        if (!$appointment_id) {
            die("Error: Appointment slot not found or already booked");
        }
        
        // Store all data in session for checkout
        $_SESSION['appointment_data'] = [
            'appointment_id' => $appointment_id,
            'service_id' => $service_id,
            'barber_id' => $barber_id,
            'appointment_date' => $appointment_date,
            'appointment_time' => $appointment_time,
            'variation' => $variation,
            'addons' => $addons,
            'customer_id' => $customer_id
        ];
        
        header("Location: checkout.php");
        exit();
    }

    // Fetch service details and variations
    $service_id = $_POST['service_id'];
    $sql_service = "SELECT s.service_name, s.service_price, s.duration,
                    GROUP_CONCAT(sv.variation_name SEPARATOR '|') as variations
                    FROM services s
                    LEFT JOIN service_variations sv ON s.service_id = sv.service_id
                    WHERE s.service_id = ?
                    GROUP BY s.service_id";
    $stmt_service = $conn->prepare($sql_service);
    $stmt_service->bind_param("i", $service_id);
    $stmt_service->execute();
    $stmt_service->bind_result($service_name, $service_price, $service_duration, $variations);
    if (!$stmt_service->fetch()) {
        echo "<script>alert('Invalid service selected. Please go back and select a valid service.'); window.location.href='book_appointments.php';</script>";
        exit();
    }
    $stmt_service->close();

    // Convert variations string to array
    $variation_options = $variations ? explode('|', $variations) : [];
?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Appointment Details - GS Barbershop</title>
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
            
            /* Main Content */
            .main-container {
                padding: 40px 5%;
                max-width: 1200px;
                margin: 0 auto;
            }
            
            .appointment-form {
                background-color: white;
                border-radius: 8px;
                padding: 30px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.05);
                margin-bottom: 30px;
            }
            
            .service-details {
                background-color: white;
                border-radius: 8px;
                padding: 20px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.05);
                margin-bottom: 20px;
            }
            
            .btn-primary {
                background-color: var(--primary-color);
                border-color: var(--primary-color);
                padding: 12px 30px;
                font-weight: 600;
            }
            
            .btn-primary:hover {
                background-color: #a58a4a;
                border-color: #a58a4a;
            }
            
            .time-slot-btn {
                margin: 5px;
            }
            
            .time-slot-btn.selected {
                background-color: var(--primary-color);
                border-color: var(--primary-color);
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
            .variation-options {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-top: 10px;
    }

    .variation-options .form-check {
        margin-bottom: 10px;
    }

    .variation-options .form-check-input {
        margin-top: 0.3rem;
    }
    .haircut-options {
        display: flex;
        flex-wrap: wrap;
        margin-top: 10px;
    }

    .haircut-option-btn {
        margin: 5px;
        padding: 8px 15px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        background-color: white;
        cursor: pointer;
        transition: all 0.3s;
    }

    .haircut-option-btn:hover {
        background-color: #f8f9fa;
    }

    .haircut-option-btn.selected {
        background-color: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }

    .haircut-option-btn input[type="radio"] {
        display: none;
    }
    .addon-options {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-top: 10px;
    }

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
    .service-duration {
                display: flex;
                align-items: center;
                margin-top: 5px;
                color: #6c757d;
            }
            
            .service-duration i {
                margin-right: 5px;
                color: var(--primary-color);
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
                <img src="<?php echo htmlspecialchars($profile_picture ?: 'default_profile.png'); ?>" alt="User" class="user-img">
                <div class="dropdown">
                    <button>
                        <?php echo htmlspecialchars($full_name); ?> <i class="fas fa-caret-down"></i>
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
                        <li class="nav-item active"><a class="nav-link" href="book_appointment.php">Book Appointment</a></li>
                        <li class="nav-item"><a class="nav-link" href="view_appointments.php">My Appointments</a></li>
                        <li class="nav-item"><a class="nav-link" href="about_us.php">About Us</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="main-container">
            <h1 class="mb-4">Appointment Details</h1>
            <form id="appointmentForm" method="POST" action="">
                <input type="hidden" name="service_id" value="<?php echo $service_id; ?>">
                <div class="row">
                    <!-- Left Column - Form Controls -->
                    <div class="col-md-8">
                        <div class="appointment-form">
                            <h4>Select Barber:</h4>
                            <div class="form-group">
                                <select name="barber_id" id="barber_id" class="form-control" required>
                                    <option value="">Select Barber</option>
                                    <?php
                                    $sql_barbers = "SELECT barber_id, barber_name FROM barbers";
                                    $barbers_result = $conn->query($sql_barbers);
                                    while ($barber = $barbers_result->fetch_assoc()) {
                                        echo "<option value='" . $barber['barber_id'] . "'>" . htmlspecialchars($barber['barber_name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <h4>Select Date:</h4>
                            <div class="form-group">
                                <input type="date" name="appointment_date" id="appointment_date" class="form-control" required>
                            </div>
                            
                            <h4>Available Times:</h4>
                            <div class="form-group">
                                <div id="timeSlots">
                                    <p>Please select a barber and date first.</p>
                                </div>
                            </div>
                            
                            <!-- Haircut Type Selection -->
                            <?php if (!empty($variation_options)): ?>
                            <h4>Select Haircut Style:</h4>
                            <div class="form-group">
                                <div class="haircut-options">
                                    <?php foreach ($variation_options as $variation): ?>
                                    <label class="haircut-option-btn">
                                        <input type="radio" name="variation" value="<?php echo htmlspecialchars($variation); ?>" required>
                                        <?php echo htmlspecialchars($variation); ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Add-On Services -->
                            <h4>Add-On Services:</h4>
                            <div class="form-group">
                                <div class="addon-options">
                                    <?php
                                    $sql_addons = "SELECT a.addon_id, a.service_name, a.service_price 
                                                FROM addon_services a
                                                JOIN service_addons sa ON a.addon_id = sa.addon_id
                                                WHERE sa.service_id = ?";
                                    $stmt_addons = $conn->prepare($sql_addons);
                                    $stmt_addons->bind_param("i", $service_id);
                                    $stmt_addons->execute();
                                    $addons_result = $stmt_addons->get_result();
                                    
                                    while ($addon = $addons_result->fetch_assoc()):
                                    ?>
                                        <div class="form-check addon-option">
                                            <input class="form-check-input" type="checkbox" 
                                                name="addons[]" id="addon_<?php echo $addon['addon_id']; ?>"
                                                value="<?php echo $addon['addon_id']; ?>">
                                            <label class="form-check-label" for="addon_<?php echo $addon['addon_id']; ?>">
                                                <?php echo htmlspecialchars($addon['service_name']); ?> 
                                                (+RM<?php echo number_format($addon['service_price'], 2); ?>)
                                            </label>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Column - Service Details -->
                    <div class="col-md-4">
                        <div class="service-details">
                            <h4>Service Details</h4>
                            <p><strong>Service Name:</strong> <?php echo htmlspecialchars($service_name); ?></p>
                            <p><strong>Base Price:</strong> RM <?php echo number_format($service_price, 2); ?></p>
                            <p class="total-price"><strong>Total Price:</strong> <span id="total-price"><?php echo number_format($service_price, 2); ?></span></p>
                            <div class="service-duration">
                                <i class="far fa-clock"></i>
                                <span>Duration: <?php echo $service_duration; ?> minutes</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Book Appointment</button>
            </form>
        </div>

        <!-- Footer -->
        <footer>
            <div class="footer-container">
                <div class="footer-column">
                    <h3>FOLLOW US</h3>
                    <p>Stay connected with us on social media for the latest updates and promotions.</p>
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
        $(document).ready(function () {
            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            $('#appointment_date').attr('min', today);
            
            $('#barber_id, #appointment_date').on('change', function () {
                const barberId = $('#barber_id').val();
                const appointmentDate = $('#appointment_date').val();
                const serviceId = <?php echo json_encode($service_id); ?>;
                const serviceDuration = <?php echo json_encode($service_duration); ?>;
                
                // Clear any previous time selection
                $('#appointment_time').remove();
                
                // Validate date selection
                if (new Date(appointmentDate) < new Date(today)) {
                    $('#timeSlots').html('<div class="alert alert-danger">Cannot select a past date. Please choose today or a future date.</div>');
                    return;
                }

                if (barberId && appointmentDate && serviceId) {
                    // Show loading indicator
                    $('#timeSlots').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Loading available times...</p></div>');
                    
                    $.ajax({
                        url: 'fetch_times.php',
                        type: 'POST',
                        data: { 
                            barber_id: barberId, 
                            appointment_date: appointmentDate, 
                            service_id: serviceId,
                            duration: serviceDuration, 
                        },
                        success: function (response) {
                            const result = JSON.parse(response);
                            const timeSlotsDiv = $('#timeSlots');
                            
                            if (result.status === 'success') {
                                let buttons = '';
                                result.available_times.forEach(function (time) {
                                    // Format time for display (HH:MM)
                                    const timeParts = time.split(':');
                                    const displayTime = `${timeParts[0]}:${timeParts[1]}`;
                                    
                                    buttons += `
                                        <button type="button" 
                                                class="btn btn-outline-primary time-slot-btn mb-2" 
                                                data-time="${time}">
                                            ${displayTime}
                                        </button>`;
                                });
                                
                                if (buttons === '') {
                                    const message = result.is_today 
                                        ? "No available slots left for today. Please choose another date."
                                        : "No available times for the selected date.";
                                    timeSlotsDiv.html(`<div class="alert alert-info">${message}</div>`);
                                } else {
                                    timeSlotsDiv.html(`
                                        <div class="d-flex flex-wrap">
                                            ${buttons}
                                        </div>
                                        <p class="text-muted mt-2">Please select a time slot</p>
                                    `);
                                }

                                // Handle time slot selection
                                $('.time-slot-btn').on('click', function () {
                                    $('.time-slot-btn').removeClass('btn-primary selected').addClass('btn-outline-primary');
                                    $(this).removeClass('btn-outline-primary').addClass('btn-primary selected');
                                    $('#appointment_time').remove();
                                    timeSlotsDiv.after(`<input type="hidden" id="appointment_time" name="appointment_time" value="${$(this).data('time')}">`);
                                });
                            } else {
                                timeSlotsDiv.html(`<div class="alert alert-info">${result.message}</div>`);
                            }
                        },
                        error: function () {
                            $('#timeSlots').html('<div class="alert alert-danger">Failed to load available times. Please try again later.</div>');
                        }
                    });
                } else {
                    $('#timeSlots').html('<div class="alert alert-info">Please select a barber and date first.</div>');
                }
            });
            
            // Initialize with today's date
            $('#appointment_date').val(today).trigger('change');
        });
        // Add this to your existing JavaScript
    $(document).on('click', '.haircut-option-btn', function() {
        $('.haircut-option-btn').removeClass('selected');
        $(this).addClass('selected');
        $(this).find('input[type="radio"]').prop('checked', true);
    });
    $(document).ready(function() {
        // Base price
        const basePrice = <?php echo $service_price; ?>;
        let totalPrice = basePrice;
        
        // Update price display
        function updatePrice() {
            $('#total-price').text('RM' + totalPrice.toFixed(2));
        }
        
        // Initialize price display
        updatePrice();
        
        // Handle add-on selection changes  
        $('input[name="addons[]"]').change(function() {
            // Reset total to base price
            totalPrice = basePrice;
            
            // Add selected add-on prices
            $('input[name="addons[]"]:checked').each(function() {
                const priceText = $(this).next('label').text().match(/\+RM([\d.]+)/);
                if (priceText) {
                    totalPrice += parseFloat(priceText[1]);
                }
            });
            
            updatePrice();
        });
    });
    </script>
    </body>
    </html>