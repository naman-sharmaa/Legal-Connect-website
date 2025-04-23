<?php
// Include initialization file 
require_once __DIR__ . '/../inc/init.php';

// Enable detailed error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    exit;
}

// Get the request data
$raw_data = file_get_contents("php://input");
error_log("Raw appointment data: " . $raw_data);

$data = json_decode($raw_data, true);

// Check if data was properly decoded
if ($data === null) {
    send_json_response(['success' => false, 'message' => 'Invalid JSON data provided: ' . json_last_error_msg()], 400);
    exit;
}

// Validate required fields
$required_fields = ['providerId', 'date', 'time', 'type'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        send_json_response(['success' => false, 'message' => "Missing required field: $field"], 400);
        exit;
    }
}

// Sanitize input data
$provider_id = sanitize_input($data['providerId']);
$provider_name = sanitize_input($data['providerName'] ?? 'Unknown Provider');
$client_id = sanitize_input($data['clientId'] ?? '0');
$client_name = sanitize_input($data['clientName'] ?? 'Anonymous Client');
$appointment_date = sanitize_input($data['date']);
$appointment_time = sanitize_input($data['time']);
$appointment_type = sanitize_input($data['type']);
$description = sanitize_input($data['description'] ?? '');
$status = sanitize_input($data['status'] ?? 'pending');

// Verify provider license number against the database
$verify_provider_query = "SELECT pd.license_number, CONCAT(u.first_name, ' ', u.last_name) as provider_name 
                         FROM provider_details pd 
                         JOIN users u ON pd.user_id = u.id 
                         WHERE pd.license_number = ?";

$verify_stmt = mysqli_prepare($conn, $verify_provider_query);
mysqli_stmt_bind_param($verify_stmt, 's', $provider_id);
mysqli_stmt_execute($verify_stmt);
$verify_result = mysqli_stmt_get_result($verify_stmt);

$verified_provider = false;
$verified_provider_name = '';

if ($verify_result && mysqli_num_rows($verify_result) > 0) {
    $provider_data = mysqli_fetch_assoc($verify_result);
    $verified_provider = true;
    $verified_provider_name = $provider_data['provider_name'];
    
    // Use the accurate provider name from the database
    if ($verified_provider_name) {
        $provider_name = $verified_provider_name;
    }
    
    error_log("Verified provider license number in database. Name: $provider_name");
} else {
    // For backward compatibility, allow appointment creation even if license doesn't match
    // but log a warning
    error_log("Warning: Provider license number '$provider_id' not found in database. Using provided name: $provider_name");
}

mysqli_stmt_close($verify_stmt);

// Insert appointment into database
$query = "INSERT INTO appointments (
    provider_id, provider_name, client_id, client_name, 
    appointment_date, appointment_time, appointment_type, description, status
) VALUES (
    '$provider_id', '$provider_name', '$client_id', '$client_name', 
    '$appointment_date', '$appointment_time', '$appointment_type', '$description', '$status'
)";

error_log("SQL Query: $query");

// Execute query
if (mysqli_query($conn, $query)) {
    // Get the auto-increment ID
    $appointment_id = mysqli_insert_id($conn);
    
    // Create response data
    $appointment_data = [
        'id' => $appointment_id,
        'providerId' => $provider_id,
        'providerName' => $provider_name,
        'clientId' => $client_id,
        'clientName' => $client_name,
        'date' => $appointment_date, 
        'time' => $appointment_time,
        'type' => $appointment_type,
        'description' => $description,
        'status' => $status,
        'verifiedProvider' => $verified_provider
    ];
    
    // Send success response
    send_json_response([
        'success' => true,
        'message' => 'Appointment created successfully',
        'appointment' => $appointment_data
    ]);
} else {
    // Send error response
    error_log("Database error: " . mysqli_error($conn));
    send_json_response([
        'success' => false,
        'message' => 'Failed to create appointment: ' . mysqli_error($conn)
    ], 500);
}
?> 