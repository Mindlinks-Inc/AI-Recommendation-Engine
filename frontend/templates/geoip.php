<?php
// Your token from ipinfo.io
$token = 'c1b34b1dca140a';

$ip_address = '223.185.128.55';  // Example IP address (you can get this dynamically as needed)
$api_url = "https://ipinfo.io/$ip_address/json?token=" . $token;

// Use file_get_contents or cURL to fetch the data
$response = file_get_contents($api_url);

// If the request is successful, return the data
if ($response !== FALSE) {
    header('Content-Type: application/json');
    echo $response;
} else {
    // If the request fails, return a default response
    echo json_encode(['country' => 'US']);
}
?>
