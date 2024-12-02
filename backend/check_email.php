<?php
header('Content-Type: application/json');

// Database connection details
$servername = "127.0.0.1";
$username = "u783522058_QTyvg";
$password = "TGF0v4G5Mv";
$dbname = "u783522058_gt5An";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get the email from the POST request
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Invalid email']);
    exit;
}

// Check if the email exists in the database
$stmt = $conn->prepare("SELECT id FROM user_information WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'This email already exists. For further information, please contact us at sales@neuralroots.ai']);
} else {
    echo json_encode(['success' => true]);
}

$stmt->close();
$conn->close();
?>