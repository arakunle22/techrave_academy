<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'info.peacedev@gmail.com';
    $mail->Password = '@Peacecode22';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Recipients
    $mail->setFrom('info.peacedev@gmail.com', 'Techrave ICT Academy');
    $mail->addAddress('peacearakunle@gmail.com'); // Replace with your email address

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email';
    $mail->Body = 'This is a test email sent from PHPMailer.';

    $mail->send();
    echo 'Test email sent successfully!';
} catch (Exception $e) {
    echo "Test email could not be sent. Error: {$mail->ErrorInfo}";
}
?>
