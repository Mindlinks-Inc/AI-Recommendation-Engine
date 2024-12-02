<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

header('Content-Type: application/json');

require __DIR__ . '/PHPmailer/src/Exception.php';
require __DIR__ . '/PHPmailer/src/PHPMailer.php';
require __DIR__ . '/PHPmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to send response
function sendResponse($status, $message, $emailSent = false) {
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'emailSent' => $emailSent
    ]);
    exit;
}

try {
    // Get and validate input
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $firstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';

    error_log("Processing request for email: $email, name: $firstName");

    if (empty($email) || empty($firstName)) {
        error_log("Missing required fields");
        sendResponse('error', 'Missing required fields');
    }

    // Generate verification code
    $verificationCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Store in session
    $_SESSION['verification_code'] = $verificationCode;
    $_SESSION['email'] = $email;
    $_SESSION['first_name'] = $firstName;

    $mail = new PHPMailer(true);

    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'sales@neuralroots.ai';
    $mail->Password = 'otdy dyut zdlk ralx';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    // Recipients
    $mail->setFrom('sales@neuralroots.ai', 'Neural Roots AI');
    $mail->addAddress($email);

    // Email content
    $mail->isHTML(true);
    $mail->Subject = 'Verify Your Neural Roots AI Assessment Account';
    
    // Email template
    $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #333333;'>Email Verification</h2>
            <p style='color: #333333; font-size: 16px;'>Dear {$firstName},</p>
            <p style='color: #555555; line-height: 1.6;'>
                Thank you for starting your AI assessment journey with Neural Roots. We're excited to help you explore 
                the potential of AI for your business.
            </p>
            <div style='background-color: #f8f9fa; padding: 15px; margin: 25px 0; text-align: center;'>
                <p style='margin: 0;'>Your Verification Code:</p>
                <h1 style='color: #0057b8; margin: 10px 0;'>{$verificationCode}</h1>
                <p style='color: #666666; font-size: 12px;'>This code will expire in 5 minutes</p>
            </div>
            <p>Need help? Contact us at <a href='mailto:sales@neuralroots.ai'>sales@neuralroots.ai</a></p>
            <p>Best regards,<br>The Neural Roots Team</p>
        </div>
    ";

    // Send email
    $emailSent = $mail->send();
    
    if ($emailSent) {
        error_log("Email sent successfully to $email");
        sendResponse('success', 'Verification code sent successfully', true);
    } else {
        throw new Exception("Email sending failed");
    }

} catch (Exception $e) {
    error_log("Error in send_code.php: " . $e->getMessage());
    // If the error occurred after email was sent, still indicate partial success
    $emailSent = isset($emailSent) ? $emailSent : false;
    sendResponse('error', 'There was an error, but your verification code may have been sent.', $emailSent);
}
?>