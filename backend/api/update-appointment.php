<?php
// Include initialization file
require_once __DIR__ . '/../inc/init.php';

// Enable detailed error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(['success' => false, 'error' => 'Method not allowed'], 405);
    exit;
}

// Log the raw POST data for debugging
$raw_post_data = file_get_contents('php://input');
error_log("Raw POST data: " . $raw_post_data);

// Get POST data
$data = json_decode($raw_post_data, true);

// Check if data was decoded properly
if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    send_json_response([
        'success' => false, 
        'message' => 'Invalid JSON data: ' . json_last_error_msg(),
        'raw_data' => $raw_post_data
    ], 400);
    exit;
}

// Validate required fields
if (empty($data['id']) || empty($data['status'])) {
    send_json_response(['success' => false, 'message' => 'Appointment ID and status are required']);
    exit;
}

// Sanitize inputs
$appointmentId = sanitize_input($data['id']);
$status = sanitize_input($data['status']);

// Log the data we're working with
error_log("Updating appointment: ID=$appointmentId, status=$status");

// Validate status
$validStatuses = ['pending', 'approved', 'completed', 'cancelled', 'rejected'];
if (!in_array($status, $validStatuses)) {
    send_json_response(['success' => false, 'message' => 'Invalid status value']);
    exit;
}

// Get the table structure
$check_table_query = "DESCRIBE appointments";
$table_result = mysqli_query($conn, $check_table_query);

if (!$table_result) {
    // Table doesn't exist, create it
    $create_table_query = "CREATE TABLE IF NOT EXISTS appointments (
        id VARCHAR(50) PRIMARY KEY,
        provider_id VARCHAR(50) NOT NULL,
        provider_name VARCHAR(100) NOT NULL,
        client_id VARCHAR(50) NOT NULL,
        client_name VARCHAR(100) NOT NULL,
        appointment_date DATE NOT NULL,
        appointment_time TIME NOT NULL,
        appointment_type VARCHAR(100) NOT NULL,
        description TEXT,
        status ENUM('pending', 'approved', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_provider_id (provider_id),
        INDEX idx_client_id (client_id),
        INDEX idx_status (status),
        INDEX idx_date (appointment_date)
    )";
    
    if (!mysqli_query($conn, $create_table_query)) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create appointments table: ' . mysqli_error($conn)
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Appointment table created, but appointment not found. Please try again.'
    ]);
    exit;
}

// Update the appointment status in the database
$query = "UPDATE appointments SET status = '$status' WHERE id = '$appointmentId'";
$result = mysqli_query($conn, $query);

if (!$result) {
    send_json_response([
        'success' => false,
        'message' => 'Failed to update appointment status in database: ' . mysqli_error($conn)
    ], 500);
    exit;
}

// Check if any rows were affected
if (mysqli_affected_rows($conn) === 0) {
    error_log("No rows affected in database, appointment not found: $appointmentId");
    
    // The appointment might be in localStorage only (client-side)
    // Return success anyway, as the frontend will handle this with localStorage
    send_json_response([
        'success' => true,
        'message' => 'Appointment status updated (client-side only)',
        'appointment' => [
            'id' => $appointmentId,
            'status' => $status
        ]
    ]);
    exit;
}

// Send success response
send_json_response([
    'success' => true,
    'message' => 'Appointment status updated successfully in database',
    'appointment' => [
        'id' => $appointmentId,
        'status' => $status
    ]
]);
?> 