<?php
// Location: public_html/Assesment-Templates/backend/process-form.php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set headers for security and CORS
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header('Content-Type: application/json');

// Allow from your domain
header("Access-Control-Allow-Origin: *"); // Replace * with your domain in production
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Include the logger
require_once 'logger.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get the raw POST data
    $postData = file_get_contents('php://input');
    $formData = json_decode($postData, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        // Try regular POST data if JSON parsing fails
        $formData = $_POST;
    }

    // Add server information
    $formData['ip_address'] = $_SERVER['REMOTE_ADDR'];
    $formData['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    $formData['submission_time'] = date('Y-m-d H:i:s');

    // Initialize the logger
    $logger = new FormLogger();
    
    // Log the submission
    $logged = $logger->logSubmission($formData);

    if ($logged) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Form data logged successfully']);
    } else {
        throw new Exception('Failed to log form data');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred while processing the form',
        'debug' => $e->getMessage()
    ]);
}
?>