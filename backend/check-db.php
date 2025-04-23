<?php
// Start output buffering to prevent PHP errors from corrupting JSON output
ob_start();

// Disable error display but log errors
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set content type to JSON
header('Content-Type: application/json');

// Function to clean output buffer and return JSON
function return_json($success, $message, $data = null) {
    ob_end_clean();
    $response = ['success' => $success, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

try {
    // Test if config file exists
    if (!file_exists(__DIR__ . '/config/database.php')) {
        return_json(false, 'Database configuration file not found');
    }
    
    // Include database config - capture any output in buffer
    require_once __DIR__ . '/config/database.php';
    
    // Check if $conn variable exists and is a valid connection
    if (!isset($conn) || !$conn) {
        return_json(false, 'Database connection not established in config file');
    }
    
    // Test connection
    if (mysqli_connect_errno()) {
        return_json(false, 'Failed to connect to MySQL: ' . mysqli_connect_error());
    }
    
    // Get database info
    $db_info = [
        'server_info' => mysqli_get_server_info($conn),
        'host_info' => mysqli_get_host_info($conn),
        'protocol_version' => mysqli_get_proto_info($conn)
    ];
    
    // Try to get total user count
    $query = "SELECT COUNT(*) as total, 
              SUM(CASE WHEN user_type = 'user' THEN 1 ELSE 0 END) as clients,
              SUM(CASE WHEN user_type = 'provider' THEN 1 ELSE 0 END) as providers
              FROM users";
    
    $result = mysqli_query($conn, $query);
    if (!$result) {
        return_json(false, 'Error querying users table: ' . mysqli_error($conn), $db_info);
    }
    
    $counts = mysqli_fetch_assoc($result);
    
    // Add database info to returned data
    $data = [
        'connection' => $db_info,
        'users' => $counts
    ];
    
    // List tables in database
    $tables_query = "SHOW TABLES";
    $tables_result = mysqli_query($conn, $tables_query);
    
    if ($tables_result) {
        $tables = [];
        while ($row = mysqli_fetch_row($tables_result)) {
            $tables[] = $row[0];
        }
        $data['tables'] = $tables;
    }
    
    // Return success with connection and database info
    return_json(true, 'Successfully connected to database', $data);
    
} catch (Exception $e) {
    return_json(false, 'Error: ' . $e->getMessage());
}

// Close connection if it exists
if (isset($conn)) {
    mysqli_close($conn);
}
?> 