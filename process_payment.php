<?php
session_start();

// Set timezone (adjust to your location)
date_default_timezone_set('Asia/Kuala_Lumpur');

// Check authentication
if (!isset($_SESSION['user']) || $_SESSION['usertype'] !== 'customer') {
    header("Location: login.php");
    exit();
}

// Check if required data exists
if (!isset($_SESSION['checkout_details'], $_SESSION['appointment_data']['appointment_id'])) {
    header("Location: book_appointments.php");
    exit();
}

include("db_connect.php");
    
// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

$checkout_details = $_SESSION['checkout_details'];
$appointment_data = $_SESSION['appointment_data'];

// Prepare addons
$selected_addons = !empty($checkout_details['addons']) ? 
    implode(',', array_column($checkout_details['addons'], 'addon_id')) : '';

// Update the appointment
$sql = "UPDATE appointments SET
        customer_id = ?,
        service_id = ?,
        is_booked = 1,
        status = 'confirmed',
        variation = ?,
        selected_addons = ?,
        total_amount = ?
        WHERE appointment_id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing appointment update: " . $conn->error);
}

$success = $stmt->bind_param(
    "iissdi",
    $appointment_data['customer_id'],
    $checkout_details['service_id'],
    $checkout_details['variation'],
    $selected_addons,
    $checkout_details['subtotal'],
    $appointment_data['appointment_id']
);

if (!$success) {
    die("Error binding appointment parameters: " . $stmt->error);
}

if ($stmt->execute()) {
    // Fetch customer info
    $customer_id = $appointment_data['customer_id'];
    $sql = "SELECT customer_name, customer_email FROM customers WHERE customer_id = ?";
    $cust_stmt = $conn->prepare($sql);
    
    if (!$cust_stmt) {
        die("Error preparing customer fetch: " . $conn->error);
    }

    $cust_stmt->bind_param("i", $customer_id);
    $cust_stmt->execute();
    $cust_stmt->bind_result($customer_name, $customer_email);
    $cust_stmt->fetch();
    $cust_stmt->close();

    // Send email confirmation
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'irsyaduddn@gmail.com'; // Your Gmail
        $mail->Password   = 'mvap bryp xrxf knxk';  // Gmail App Password (keep this secure)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('irsyaduddn@gmail.com', 'GS Barbershop'); // Match your Gmail
        $mail->addAddress($customer_email, $customer_name);

        $mail->isHTML(true);
        $mail->Subject = 'Your Appointment at GS Barbershop is Confirmed!';
        $mail->Body    = "
            <h2>Thank you for your booking!</h2>
            <p>Dear <strong>$customer_name</strong>,</p>
            <p>Your appointment has been successfully confirmed.</p>
            <p><strong>Service:</strong> {$checkout_details['service_name']}<br>
            <strong>Variation:</strong> {$checkout_details['variation']}<br>
            <strong>Total Amount:</strong> RM " . number_format($checkout_details['subtotal'], 2) . "</p>
            <p>We look forward to seeing you!<br>— GS Barbershop</p>
        ";

        $mail->send();
    } catch (Exception $e) {
        error_log("Email error: {$mail->ErrorInfo}");
    }

    // Cleanup
    unset($_SESSION['appointment_data'], $_SESSION['checkout_details']);
    $_SESSION['payment_success'] = true;
    $_SESSION['appointment_id'] = $appointment_data['appointment_id'];
    header("Location: payment_success.php");
    exit();
} else {
    die("Error updating appointment: " . $stmt->error);
}
