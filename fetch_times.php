<?php
include("db_connect.php");

// Set timezone (adjust to your location)
date_default_timezone_set('Asia/Kuala_Lumpur');

if (!isset($_POST['barber_id'], $_POST['appointment_date'], $_POST['service_id'])) {
    echo json_encode(["status" => "error", "message" => "Missing required parameters."]);
    exit();
}

$barber_id = $_POST['barber_id'];
$appointment_date = $_POST['appointment_date'];
$service_id = $_POST['service_id'];

// Validate input parameters
if (empty($barber_id) || empty($appointment_date) || empty($service_id)) {
    echo json_encode(["status" => "error", "message" => "Missing required parameters."]);
    exit();
}

// Get current date and time
$current_date = date('Y-m-d');
$current_time = date('H:i:s');

// Check if the selected date is in the past
if ($appointment_date < $current_date) {
    echo json_encode(["status" => "error", "message" => "Cannot select a past date."]);
    exit();
}

// Prepare SQL query to fetch available time slots
$sql = "SELECT appointment_time 
        FROM appointments 
        WHERE barber_id = ? 
        AND appointment_date = ? 
        AND service_id = ? 
        AND is_booked = 0";

// If selected date is today, only get times after current time
if ($appointment_date == $current_date) {
    $sql .= " AND appointment_time > ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $barber_id, $appointment_date, $service_id, $current_time);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $barber_id, $appointment_date, $service_id);
}

$stmt->execute();
$result = $stmt->get_result();

$available_times = [];
while ($row = $result->fetch_assoc()) {
    $available_times[] = $row['appointment_time'];
}

// Sort times in chronological order
sort($available_times);

if (count($available_times) > 0) {
    echo json_encode([
        "status" => "success", 
        "available_times" => $available_times,
        "is_today" => ($appointment_date == $current_date)
    ]);
} else {
    $message = ($appointment_date == $current_date) 
        ? "No available time slots left for today. Please choose another date."
        : "No available times left for the selected date. Please choose another date";
    echo json_encode(["status" => "error", "message" => $message]);
}

$stmt->close();
?>