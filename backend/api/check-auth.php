<?php
// Include initialization file
require_once __DIR__ . '/../inc/init.php';

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_json_response(['error' => 'Method not allowed'], 405);
}

// Get auth token from request
$auth_header = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
$token = '';

// Extract token from Authorization header
if (!empty($auth_header)) {
    if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        $token = $matches[1];
    }
}

// If no token in header, check if it's in the query params
if (empty($token) && isset($_GET['token'])) {
    $token = $_GET['token'];
}

// If still no token, check session variable
if (empty($token) && isset($_SESSION['session_id'])) {
    $token = $_SESSION['session_id'];
}

// Log the token for debugging
error_log("Auth check - Token: " . ($token ? substr($token, 0, 10) . '...' : 'None'));

// If no token is found, return unauthenticated
if (empty($token)) {
    send_json_response(['authenticated' => false, 'message' => 'No authentication token provided']);
    exit;
}

// Check if token exists in the database
$query = "SELECT s.*, u.id as user_id, u.first_name, u.last_name, u.email, u.phone, u.user_type 
          FROM sessions s 
          JOIN users u ON s.user_id = u.id 
          WHERE s.id = '$token' AND s.expires_at > NOW()";

error_log("Auth check - Query: $query");

$result = mysqli_query($conn, $query);

if (!$result) {
    error_log("Auth check - Database error: " . mysqli_error($conn));
    send_json_response(['authenticated' => false, 'message' => 'Database error']);
    exit;
}

if (mysqli_num_rows($result) === 0) {
    error_log("Auth check - No matching session found for token");
    send_json_response(['authenticated' => false, 'message' => 'Invalid or expired token']);
    exit;
}

// Get user data
$row = mysqli_fetch_assoc($result);
$user_id = $row['user_id'];
$user_type = $row['user_type'];

error_log("Auth check - Found user: $user_id, Type: $user_type");

// Build user response
$user = [
    'id' => $user_id,
    'name' => $row['first_name'] . ' ' . $row['last_name'],
    'email' => $row['email'],
    'phone' => $row['phone'],
    'user_type' => $row['user_type']
];

// Get additional provider details if user is a provider
if ($user_type === 'provider') {
    $provider_query = "SELECT provider_type, license_number, bio, services_offered, available_hours 
                      FROM provider_details 
                      WHERE user_id = '$user_id'";
    
    error_log("Auth check - Provider query: $provider_query");
    
    $provider_result = mysqli_query($conn, $provider_query);
    
    if (!$provider_result) {
        error_log("Auth check - Provider query error: " . mysqli_error($conn));
    }
    
    if ($provider_result && mysqli_num_rows($provider_result) > 0) {
        $provider_data = mysqli_fetch_assoc($provider_result);
        $user['provider_type'] = $provider_data['provider_type'];
        $user['license_number'] = $provider_data['license_number'];
        $user['bio'] = $provider_data['bio'];
        $user['services_offered'] = $provider_data['services_offered'];
        $user['available_hours'] = $provider_data['available_hours'];
        error_log("Auth check - Provider details added");
    } else {
        error_log("Auth check - No provider details found for user: $user_id");
    }
}

// Update session in the database (refresh expiration)
$new_expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
$update_query = "UPDATE sessions SET expires_at = '$new_expires_at' WHERE id = '$token'";
mysqli_query($conn, $update_query);

// Set session variables
$_SESSION['user_id'] = $user_id;
$_SESSION['user_type'] = $user_type;
$_SESSION['session_id'] = $token;

// Return successful response
error_log("Auth check - Success, returning user data");
send_json_response([
    'authenticated' => true,
    'user' => $user
]);
?> 