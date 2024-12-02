<?php
// Database connection details
$servername = "127.0.0.1";
$username = "u783522058_QTyvg";
$password = "TGF0v4G5Mv";
$dbname = "u783522058_gt5An";

// Create a new database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the email parameter is set
if (isset($_POST['email'])) {
    $email = $conn->real_escape_string($_POST['email']);
    
    // Query to check if the email already exists in the database
    $sql = "SELECT id FROM users WHERE email = '$email'";
    $result = $conn->query($sql);
    
    // If a record is found, the email is already used
    if ($result->num_rows > 0) {
        echo json_encode(['exists' => true, 'message' => 'This email is already used. Try another one.']);
    } else {
        echo json_encode(['exists' => false, 'message' => 'Email is available.']);
    }
} else {
    echo json_encode(['error' => 'Email parameter is missing.']);
}

// Close the database connection
$conn->close();
?>
