<?php
// reminder_cron.php
include("db_connect.php");

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

// Get appointments happening in exactly 1 hour
$oneHourLater = date('Y-m-d H:i:s', strtotime('+1 hour'));
$query = "SELECT a.*, c.customer_email, c.customer_name 
          FROM appointments a
          JOIN customers c ON a.customer_id = c.customer_id
          WHERE CONCAT(a.appointment_date, ' ', a.appointment_time) = ? 
          AND a.remider_sent = 0 
          AND a.status = 'confirmed'
          AND a.is_booked = 1";

$stmt = $pdo->prepare($query);
$stmt->execute([$oneHourLater]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Logging setup
$logFile = 'reminder_log_'.date('Y-m-d').'.txt';
file_put_contents($logFile, "=== Reminder Cron Started at ".date('Y-m-d H:i:s')." ===\n", FILE_APPEND);

foreach ($appointments as $appointment) {
    // Send email using PHPMailer
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'irsyaduddn@gmail.com';
        $mail->Password   = 'mvap bryp xrxf knxk'; // Consider moving this to environment variables
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        // Recipients
        $mail->setFrom('irsyaduddn@gmail.com', 'GS Barbershop');
        $mail->addAddress($appointment['customer_email'], $appointment['customer_name']);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Reminder: Your appointment is in 1 hour';
        
        $appointmentDateTime = date('F j, Y g:i a', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']));
        $mail->Body    = "Hello {$appointment['customer_name']},<br><br>
                          This is a reminder that you have an appointment scheduled for:<br>
                          <strong>{$appointmentDateTime}</strong><br><br>
                          Please arrive 5 minutes early.<br><br>
                          Thank you for choosing GS Barbershop!";
                          
        $mail->AltBody = "Hello {$appointment['customer_name']},\n\nThis is a reminder that you have an appointment scheduled for:\n{$appointmentDateTime}\n\nPlease arrive 5 minutes early.\n\nThank you for choosing GS Barbershop!";
        
        if ($mail->send()) {
            // Mark as sent in database
            $update = $pdo->prepare("UPDATE appointments SET remider_sent = 1 WHERE appointment_id = ?");
            $update->execute([$appointment['appointment_id']]);
            
            $logMessage = "[".date('Y-m-d H:i:s')."] Reminder sent for appointment ID: {$appointment['appointment_id']} to {$appointment['customer_email']}\n";
            echo $logMessage;
            file_put_contents($logFile, $logMessage, FILE_APPEND);
        }
        
    } catch (Exception $e) {
        $errorMessage = "[".date('Y-m-d H:i:s')."] Error sending to appointment ID {$appointment['appointment_id']}: {$mail->ErrorInfo}\n";
        error_log($errorMessage);
        file_put_contents($logFile, $errorMessage, FILE_APPEND);
    }
}

file_put_contents($logFile, "=== Cron Completed at ".date('Y-m-d H:i:s')." ===\n\n", FILE_APPEND);