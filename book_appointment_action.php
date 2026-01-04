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

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = $_POST['service_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $barber_id = $_POST['barber_id'];
    $customer_email = $_SESSION['user'];

    // Validate inputs
    if (empty($service_id) || empty($appointment_date) || empty($appointment_time) || empty($barber_id)) {
        header("Location: book_appointment.php?status=error&message=Incomplete form data.");
        exit();
    }

    // Fetch customer ID
    $sql_customer = "SELECT customer_id FROM customers WHERE customer_email = ?";
    $stmt = $conn->prepare($sql_customer);
    $stmt->bind_param("s", $customer_email);
    $stmt->execute();
    $stmt->bind_result($customer_id);
    $stmt->fetch();
    $stmt->close();

    if (empty($customer_id)) {
        header("Location: book_appointment.php?status=error&message=Customer not found.");
        exit();
    }

    // Insert booking into appointments table
    $sql = "INSERT INTO appointments (customer_id, service_id, barber_id, appointment_date, appointment_time) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiss", $customer_id, $service_id, $barber_id, $appointment_date, $appointment_time);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: view_appointments.php?status=success");
        exit();
    } else {
        $error_message = $stmt->error; // Capture error message
        $stmt->close();
        header("Location: book_appointment.php?status=error&message=" . urlencode($error_message));
        exit();
    }
} else {
    header("Location: book_appointment.php?status=error&message=Invalid request.");
    exit();
}
?>
