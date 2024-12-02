<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Log function for debugging
function log_message($message) {
    file_put_contents('debug.log', date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
}

// Sanitize input function to avoid injection attacks
function sanitize_input($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

log_message("Script started");
log_message("POST data: " . print_r($_POST, true));

// Database connection details
$servername = "127.0.0.1";
$username = "u783522058_QTyvg";
$password = "TGF0v4G5Mv";
$dbname = "u783522058_gt5An";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    log_message("Database connection failed: " . $conn->connect_error);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

log_message("Database connected successfully");

// Ensure that the form is submitted using POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize form data
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $namePrefix = sanitize_input($_POST['name_prefix'] ?? '');
    $firstName = sanitize_input($_POST['first_name'] ?? '');
    $lastName = sanitize_input($_POST['last_name'] ?? '');
    $companyName = sanitize_input($_POST['company_name'] ?? '');
    $jobRole = sanitize_input($_POST['job_role'] ?? '');
    $mobileNumber = sanitize_input($_POST['mobile_number'] ?? ''); // Mobile number can be optional
    $verificationCode = $_SESSION['verification_code'] ?? '';
    $verificationStatus = "Verified";
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'];

    log_message("Sanitized input: " . print_r(compact('email', 'namePrefix', 'firstName', 'lastName', 'companyName', 'jobRole', 'mobileNumber'), true));

    // Input validation
    if (empty($email) || empty($firstName) || empty($lastName) || empty($companyName) || empty($jobRole)) {
        log_message("Required fields are missing");
        echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
        exit;
    }

    // Check if the email already exists in the database
    $stmt = $conn->prepare("SELECT id FROM user_information WHERE email = ?");
    if (!$stmt) {
        log_message("Prepare failed: " . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        log_message("Execute failed: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }

    $result = $stmt->get_result();
    $stmt->close(); // Close the SELECT statement

    // SQL query for inserting/updating record
    if ($result->num_rows > 0) {
        // Email exists, get the user ID and prepare to update
        $existing_user = $result->fetch_assoc();
        $user_id = $existing_user['id'];
        log_message("Existing user found. User ID: " . $user_id);

        $sql = "UPDATE user_information SET 
                name_prefix = ?, first_name = ?, last_name = ?, company_name = ?, 
                job_role = ?, mobile_number = ?, verification_code = ?, 
                verification_status = ?, ip_address = ?, user_agent = ? 
                WHERE email = ?";
    } else {
        log_message("New user, inserting record");
        // Prepare to insert new record
        $sql = "INSERT INTO user_information (email, name_prefix, first_name, last_name, 
                company_name, job_role, mobile_number, verification_code, 
                verification_status, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    }

    // Prepare the new statement for either insert or update
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        log_message("Prepare failed: " . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }

    // Bind parameters and execute the query based on whether updating or inserting
    if ($result->num_rows > 0) {
        // Bind parameters for update
        $stmt->bind_param("sssssssssss", $namePrefix, $firstName, $lastName, $companyName, 
                          $jobRole, $mobileNumber, $verificationCode, $verificationStatus, 
                          $ipAddress, $userAgent, $email);
    } else {
        // Bind parameters for insert
        $stmt->bind_param("sssssssssss", $email, $namePrefix, $firstName, $lastName, 
                          $companyName, $jobRole, $mobileNumber, $verificationCode, 
                          $verificationStatus, $ipAddress, $userAgent);
    }

    // Execute the statement and handle success or failure
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id ?: $user_id;  // Use inserted ID or previously fetched ID
        log_message("Database operation successful. User ID: " . $user_id);
        echo json_encode([
            'success' => true,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'user_id' => $user_id
        ]);
    } else {
        log_message("Database operation failed: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to save user information: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    log_message("Invalid request method: " . $_SERVER["REQUEST_METHOD"]);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
log_message("Script ended");
?>
