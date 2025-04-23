<?php
// Enable detailed error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include initialization file
require_once __DIR__ . '/../inc/init.php';

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get the request data
$requestMethod = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

// GET request - Retrieve appointments
if ($requestMethod === 'GET') {
    // Validate authentication first
    $auth_header = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
    $token = '';
    
    // Extract token from Authorization header
    if (!empty($auth_header)) {
        if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
            $token = $matches[1];
        }
    }
    
    // If no token in header, check if it's in the query params or session
    if (empty($token) && isset($_GET['token'])) {
        $token = $_GET['token'];
    } else if (empty($token) && isset($_SESSION['session_id'])) {
        $token = $_SESSION['session_id'];
    }
    
    // Skip strict auth check for development - will just use the providerId param
    // In production, should require proper authentication for all requests
    
    // Get provider ID from query params
    $providerId = '';
    if (isset($_GET['providerId']) && !empty($_GET['providerId'])) {
        $providerId = sanitize_input($_GET['providerId']);
    }
    
    // If token exists, verify it and get the user ID
    if (!empty($token)) {
        $session_query = "SELECT s.user_id, u.user_type 
                         FROM sessions s 
                         JOIN users u ON s.user_id = u.id 
                         WHERE s.id = '$token' AND s.expires_at > NOW()";
        $session_result = mysqli_query($conn, $session_query);
        
        if ($session_result && mysqli_num_rows($session_result) > 0) {
            $session_data = mysqli_fetch_assoc($session_result);
            
            // If user is a provider and no providerId specified, use the authenticated user's ID
            if ($session_data['user_type'] === 'provider' && empty($providerId)) {
                $providerId = $session_data['user_id'];
            }
            
            // For security, if a providerId is specified and user is a provider,
            // make sure they can only get their own appointments
            if ($session_data['user_type'] === 'provider' && !empty($providerId) && $providerId !== $session_data['user_id']) {
                send_json_response([
                    'success' => false, 
                    'message' => 'You can only access your own appointments'
                ], 403);
                exit;
            }
        }
    }
    
    // If we still don't have a provider ID, show no appointments
    if (empty($providerId)) {
        send_json_response(['success' => true, 'appointments' => []]);
        exit;
    }
    
    // Build query to get appointments
    $query = "SELECT * FROM appointments";
    
    // Filter by provider ID
    $query .= " WHERE provider_id = '$providerId'";
    
    // Log the query for debugging
    error_log("Appointments query: $query");
    
    // Order by date and time
    $query .= " ORDER BY appointment_date, appointment_time";
    
    // Execute query
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        send_json_response([
            'success' => false, 
            'message' => 'Failed to fetch appointments: ' . mysqli_error($conn)
        ], 500);
        exit;
    }
    
    // Format appointments
    $appointments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Convert database column names to camelCase for frontend
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
    
    // Send response
    send_json_response(['success' => true, 'appointments' => $appointments]);
    exit;
}

// POST request - Create a new appointment
if ($requestMethod === 'POST') {
    // Validate required fields
    if (empty($data['providerId']) || empty($data['date']) || empty($data['time'])) {
        send_json_response(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    // Sanitize inputs - id will be auto_increment, don't set it
    $providerId = sanitize_input($data['providerId']);
    $providerName = sanitize_input($data['providerName'] ?? 'Unknown Provider');
    $clientId = sanitize_input($data['clientId'] ?? 'client_' . uniqid());
    $clientName = sanitize_input($data['clientName'] ?? 'Anonymous Client');
    $date = sanitize_input($data['date']);
    $time = sanitize_input($data['time']);
    $type = sanitize_input($data['type'] ?? 'Consultation');
    $description = sanitize_input($data['description'] ?? '');
    $status = sanitize_input($data['status'] ?? 'pending');
    
    // Insert appointment into database
    $query = "INSERT INTO appointments (
                provider_id, provider_name, client_id, client_name, 
                appointment_date, appointment_time, appointment_type, description, status
              ) VALUES (
                '$providerId', '$providerName', '$clientId', '$clientName', 
                '$date', '$time', '$type', '$description', '$status'
              )";
    
    error_log("Insert query: $query");
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        error_log("MySQL Error: " . mysqli_error($conn));
        send_json_response([
            'success' => false, 
            'message' => 'Failed to create appointment: ' . mysqli_error($conn)
        ], 500);
        exit;
    }
    
    // Get the auto-increment ID
    $id = mysqli_insert_id($conn);
    
    // Build response appointment object
    $appointmentData = [
        'id' => $id,
        'providerId' => $providerId,
        'providerName' => $providerName,
        'clientId' => $clientId,
        'clientName' => $clientName,
        'date' => $date,
        'time' => $time,
        'type' => $type,
        'description' => $description,
        'status' => $status
    ];
    
    // Return success response
    send_json_response([
        'success' => true, 
        'message' => 'Appointment created successfully',
        'appointment' => $appointmentData
    ]);
    exit;
}

// PUT request - Update an appointment
if ($requestMethod === 'POST' && isset($_GET['action']) && $_GET['action'] === 'update') {
    // Validate the appointment ID
    if (empty($data['id'])) {
        send_json_response(['success' => false, 'message' => 'Appointment ID is required']);
        exit;
    }
    
    // Sanitize ID
    $appointmentId = sanitize_input($data['id']);
    
    // Build update query
    $updateFields = [];
    
    if (!empty($data['status'])) {
        $status = sanitize_input($data['status']);
        $updateFields[] = "status = '$status'";
    }
    
    if (!empty($data['date'])) {
        $date = sanitize_input($data['date']);
        $updateFields[] = "appointment_date = '$date'";
    }
    
    if (!empty($data['time'])) {
        $time = sanitize_input($data['time']);
        $updateFields[] = "appointment_time = '$time'";
    }
    
    if (!empty($data['type'])) {
        $type = sanitize_input($data['type']);
        $updateFields[] = "appointment_type = '$type'";
    }
    
    if (isset($data['description'])) {
        $description = sanitize_input($data['description']);
        $updateFields[] = "description = '$description'";
    }
    
    // If no fields to update
    if (empty($updateFields)) {
        send_json_response(['success' => false, 'message' => 'No fields to update']);
        exit;
    }
    
    // Create update query
    $query = "UPDATE appointments SET " . implode(', ', $updateFields) . " WHERE id = '$appointmentId'";
    
    // Execute query
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        send_json_response([
            'success' => false, 
            'message' => 'Failed to update appointment: ' . mysqli_error($conn)
        ], 500);
        exit;
    }
    
    // Check if any rows were affected
    if (mysqli_affected_rows($conn) === 0) {
        send_json_response(['success' => false, 'message' => 'Appointment not found or no changes made']);
        exit;
    }
    
    // Return success response
    send_json_response(['success' => true, 'message' => 'Appointment updated successfully']);
    exit;
}

// DELETE request - Delete an appointment
if ($requestMethod === 'DELETE') {
    // Validate the appointment ID
    if (empty($data['id'])) {
        send_json_response(['success' => false, 'message' => 'Appointment ID is required']);
        exit;
    }
    
    // Sanitize ID
    $appointmentId = sanitize_input($data['id']);
    
    // Delete appointment
    $query = "DELETE FROM appointments WHERE id = '$appointmentId'";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        send_json_response([
            'success' => false, 
            'message' => 'Failed to delete appointment: ' . mysqli_error($conn)
        ], 500);
        exit;
    }
    
    // Check if any rows were affected
    if (mysqli_affected_rows($conn) === 0) {
        send_json_response(['success' => false, 'message' => 'Appointment not found']);
        exit;
    }
    
    // Return success response
    send_json_response(['success' => true, 'message' => 'Appointment deleted successfully']);
    exit;
}

// If we reach here, the request method is not supported
send_json_response(['success' => false, 'message' => 'Unsupported request method'], 405);
?> 