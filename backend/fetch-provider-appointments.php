<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/config/database.php';

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Get provider ID from query parameters
$provider_id = isset($_GET['providerId']) ? $_GET['providerId'] : '';

// Validate provider ID
if (empty($provider_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'Provider ID is required'
    ]);
    exit;
}

// Log request for debugging
error_log("Fetching appointments for provider ID (license number): $provider_id");

// First, let's check what column names the table actually has
$checkColumnQuery = "SHOW COLUMNS FROM appointments";
$columnResult = mysqli_query($conn, $checkColumnQuery);

// Default to column names from original schema
$dateColumn = 'appointment_date';
$timeColumn = 'appointment_time';
$typeColumn = 'appointment_type';

// If we can check columns, update our references
if ($columnResult) {
    $columns = [];
    while ($columnRow = mysqli_fetch_assoc($columnResult)) {
        $columns[] = $columnRow['Field'];
    }
    
    // Log found columns
    error_log("Found columns in appointments table: " . implode(", ", $columns));
    
    // Check if the table uses the newer naming convention (without 'appointment_' prefix)
    if (in_array('date', $columns) && !in_array('appointment_date', $columns)) {
        $dateColumn = 'date';
        $timeColumn = 'time';
        $typeColumn = 'type';
        error_log("Using simplified column names (date, time, type)");
    } else {
        error_log("Using standard column names (appointment_date, appointment_time, appointment_type)");
    }
}

// Check if the provider ID is a user ID or a license number
// First, check if it's a user ID in the users table
$check_user_query = "SELECT u.id as user_id, CONCAT(u.first_name, ' ', u.last_name) as provider_name 
                      FROM users u 
                      WHERE u.id = ? AND u.user_type = 'provider'";

$check_stmt = mysqli_prepare($conn, $check_user_query);
mysqli_stmt_bind_param($check_stmt, 's', $provider_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

$user_exists = false;
$user_id = null;
$provider_name_from_db = null;

if ($check_result && mysqli_num_rows($check_result) > 0) {
    $user_data = mysqli_fetch_assoc($check_result);
    $user_exists = true;
    $user_id = $user_data['user_id'];
    $provider_name_from_db = $user_data['provider_name'];
    error_log("Found user ID in database. User ID: $user_id, Name: $provider_name_from_db");
}

mysqli_stmt_close($check_stmt);

// If not found as user ID, check if it's a license number
if (!$user_exists) {
    $check_license_query = "SELECT pd.license_number, u.id as user_id, CONCAT(u.first_name, ' ', u.last_name) as provider_name 
                            FROM provider_details pd 
                            JOIN users u ON pd.user_id = u.id 
                            WHERE pd.license_number = ?";

    $check_stmt = mysqli_prepare($conn, $check_license_query);
    mysqli_stmt_bind_param($check_stmt, 's', $provider_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);

    if ($check_result && mysqli_num_rows($check_result) > 0) {
        $license_data = mysqli_fetch_assoc($check_result);
        $user_exists = true;
        $user_id = $license_data['user_id'];
        $provider_name_from_db = $license_data['provider_name'];
        error_log("Found license number in database. User ID: $user_id, Name: $provider_name_from_db");
    }

    mysqli_stmt_close($check_stmt);
}

// Create and execute query to get appointments for the provider
if ($user_exists) {
    // If we found a valid user (by ID or license), use the user_id OR license_number
    // This ensures we find appointments regardless of which ID is stored in the database
    $query = "SELECT * FROM appointments WHERE provider_id = ? OR provider_id = ? ORDER BY $dateColumn, $timeColumn";
    $param_type = 'ss';
    $license_number = '';
    
    // Get the license number if we found a user
    $license_query = "SELECT license_number FROM provider_details WHERE user_id = ?";
    $license_stmt = mysqli_prepare($conn, $license_query);
    mysqli_stmt_bind_param($license_stmt, 's', $user_id);
    mysqli_stmt_execute($license_stmt);
    $license_result = mysqli_stmt_get_result($license_stmt);
    
    if ($license_result && mysqli_num_rows($license_result) > 0) {
        $license_data = mysqli_fetch_assoc($license_result);
        $license_number = $license_data['license_number'];
    }
    mysqli_stmt_close($license_stmt);
    
    error_log("Using user_id: $user_id OR license_number: $license_number for appointments query");
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, $param_type, $user_id, $license_number);
} else {
    // Fallback to the original provider_id from the request
    $query = "SELECT * FROM appointments WHERE provider_id = ? ORDER BY $dateColumn, $timeColumn";
    $param_type = 's';
    $param_value = $provider_id;
    
    error_log("No user found, falling back to provided ID: $provider_id");
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, $param_type, $param_value);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . mysqli_error($conn)
    ]);
    exit;
}

// Build appointments array
$appointments = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Determine the correct column names dynamically
    $date = isset($row[$dateColumn]) ? $row[$dateColumn] : (isset($row['date']) ? $row['date'] : null);
    $time = isset($row[$timeColumn]) ? $row[$timeColumn] : (isset($row['time']) ? $row['time'] : null);
    $type = isset($row[$typeColumn]) ? $row[$typeColumn] : (isset($row['type']) ? $row['type'] : null);
    
    $appointments[] = [
        'id' => $row['id'],
        'providerId' => $row['provider_id'],
        'providerName' => $row['provider_name'],
        'clientId' => $row['client_id'],
        'clientName' => $row['client_name'],
        'date' => $date,
        'time' => $time,
        'type' => $type,
        'description' => $row['description'],
        'status' => $row['status'],
        'createdAt' => $row['created_at']
    ];
}

// Log result count
error_log("Found " . count($appointments) . " appointments for provider ID: $provider_id");

// Return appointments as JSON
echo json_encode([
    'success' => true,
    'appointments' => $appointments,
    'count' => count($appointments),
    'providerId' => $provider_id,
    'licenseExists' => $user_exists,
    'userId' => $user_id,
    'providerName' => $provider_name_from_db
]);

// Close statement and connection
mysqli_stmt_close($stmt);
mysqli_close($conn);
?> 