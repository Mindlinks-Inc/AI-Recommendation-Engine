<?php
session_start();
header('Content-Type: application/json');

// Log function to log to php_errors.log
function log_message($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, __DIR__ . '/php_errors.log');
}

// Log the start of the confirmation process
log_message("=== Confirmation process started ===");

// Retrieve the submitted verification code from the POST request, and trim any whitespace
$submittedCode = isset($_POST['verification_code']) ? trim((string)$_POST['verification_code']) : null;

// Retrieve the session-stored verification code, and trim any whitespace
$sessionCode = isset($_SESSION['verification_code']) ? trim((string)$_SESSION['verification_code']) : null;

// Log the submitted code and session code for debugging
log_message("Submitted code: " . $submittedCode);
log_message("Session-stored code: " . $sessionCode);

// Check if the submitted code matches the session-stored code
if ($submittedCode && $submittedCode === $sessionCode) {
    // Log success and respond with success
    log_message("Verification successful. Code matched.");
    echo json_encode(['status' => 'success', 'message' => 'Verification successful!']);
} else {
    // Log failure and respond with error
    log_message("Verification failed. Invalid code.");
    echo json_encode(['status' => 'failed', 'message' => 'Invalid verification code.']);
}

// Log the end of the confirmation process
log_message("=== Confirmation process ended ===");

exit;
