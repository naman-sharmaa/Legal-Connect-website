<?php
// Include initialization file
require_once __DIR__ . '/../inc/init.php';

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_json_response(['error' => 'Method not allowed'], 405);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    send_json_response(['error' => 'Unauthorized'], 401);
}

$user_id = $_SESSION['user_id'];

// Get user data
$query = "SELECT id, first_name, last_name, email, phone, user_type FROM users WHERE id = '$user_id'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) === 0) {
    send_json_response(['error' => 'User not found'], 404);
}

$user = mysqli_fetch_assoc($result);

// Get provider details if applicable
if ($user['user_type'] === 'provider') {
    $provider_query = "SELECT provider_type, license_number, bio, services_offered, available_hours 
                      FROM provider_details WHERE user_id = '$user_id'";
    $provider_result = mysqli_query($conn, $provider_query);
    
    if (mysqli_num_rows($provider_result) > 0) {
        $provider_details = mysqli_fetch_assoc($provider_result);
        $user = array_merge($user, $provider_details);
    }
}

// Prepare response
$response = [
    'success' => true,
    'user' => $user
];

// Send response
send_json_response($response); 