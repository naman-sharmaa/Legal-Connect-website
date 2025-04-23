<?php
// Include initialization file
require_once __DIR__ . '/../inc/init.php';

// Enable detailed error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    exit;
}

// Get provider ID from query parameters
$provider_id = isset($_GET['providerId']) ? sanitize_input($_GET['providerId']) : '';

// Validate provider ID
if (empty($provider_id)) {
    send_json_response(['success' => false, 'message' => 'Provider ID is required'], 400);
    exit;
}

// Log request
error_log("Getting appointments for provider ID: $provider_id");

// Get session token for authentication (if any)
$auth_header = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
$token = '';

if (!empty($auth_header)) {
    if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        $token = $matches[1];
        error_log("Authentication token provided: $token");
    }
}

// Build query to get appointments for the provider
$query = "SELECT * FROM appointments WHERE provider_id = '$provider_id' ORDER BY appointment_date, appointment_time";

// Execute query
$result = mysqli_query($conn, $query);

if (!$result) {
    error_log("Database error: " . mysqli_error($conn));
    send_json_response(['success' => false, 'message' => 'Failed to fetch appointments: ' . mysqli_error($conn)], 500);
    exit;
}

// Build appointments array
$appointments = [];
while ($row = mysqli_fetch_assoc($result)) {
    $appointments[] = [
        'id' => $row['id'],
        'providerId' => $row['provider_id'],
        'providerName' => $row['provider_name'],
        'clientId' => $row['client_id'],
        'clientName' => $row['client_name'],
        'date' => $row['appointment_date'],
        'time' => $row['appointment_time'],
        'type' => $row['appointment_type'],
        'description' => $row['description'],
        'status' => $row['status'],
        'createdAt' => $row['created_at']
    ];
}

// Log response
error_log("Found " . count($appointments) . " appointments for provider ID: $provider_id");

// Send response
send_json_response(['success' => true, 'appointments' => $appointments]);
?> 