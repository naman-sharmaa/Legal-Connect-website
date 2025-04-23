<?php
// Include initialization file
require_once __DIR__ . '/../inc/init.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(['error' => 'Method not allowed'], 405);
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST; // Fallback to $_POST if JSON is not available
}

// Validate required fields
$required_fields = ['email', 'password'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        send_json_response(['error' => ucfirst($field) . ' is required'], 400);
    }
}

// Sanitize input
$email = sanitize_input($data['email']);
$password = $data['password'];
$user_type = isset($data['user_type']) ? sanitize_input($data['user_type']) : null;

// Build query
$query = "SELECT id, first_name, last_name, email, password, user_type FROM users WHERE email = '$email'";
if ($user_type) {
    $query .= " AND user_type = '$user_type'";
}

// Execute query
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) === 0) {
    send_json_response(['error' => 'Invalid email or password'], 401);
}

// Get user data
$user = mysqli_fetch_assoc($result);

// Verify password
if (!verify_password($password, $user['password'])) {
    send_json_response(['error' => 'Invalid email or password'], 401);
}

// Get extra info for providers
$provider_details = null;
if ($user['user_type'] === 'provider') {
    $user_id = $user['id'];
    $provider_query = "SELECT provider_type, license_number FROM provider_details WHERE user_id = '$user_id'";
    $provider_result = mysqli_query($conn, $provider_query);
    if (mysqli_num_rows($provider_result) > 0) {
        $provider_details = mysqli_fetch_assoc($provider_result);
    }
}

// Generate session token
$session_id = generate_token();
$user_id = $user['id'];
$ip_address = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];
$expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));

// Store session in database
$session_query = "INSERT INTO sessions (id, user_id, ip_address, user_agent, expires_at) 
                  VALUES ('$session_id', '$user_id', '$ip_address', '$user_agent', '$expires_at')";
mysqli_query($conn, $session_query);

// Set session in PHP
$_SESSION['user_id'] = $user_id;
$_SESSION['user_type'] = $user['user_type'];
$_SESSION['session_id'] = $session_id;

// Prepare response data
$response = [
    'success' => true,
    'message' => 'Login successful',
    'user' => [
        'id' => $user['id'],
        'name' => $user['first_name'] . ' ' . $user['last_name'],
        'email' => $user['email'],
        'user_type' => $user['user_type']
    ],
    'session_id' => $session_id
];

// Add provider details if applicable
if ($provider_details) {
    $response['user']['provider_type'] = $provider_details['provider_type'];
    $response['user']['license_number'] = $provider_details['license_number'];
}

// Send response
send_json_response($response); 