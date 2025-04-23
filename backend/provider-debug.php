<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database config
require_once __DIR__ . '/config/database.php';

// Set header for JSON output
header('Content-Type: application/json');

// Function to get all providers
function getAllProviders($conn) {
    $providers = [];
    
    // Query to get all providers with their details
    $query = "SELECT u.id, u.first_name, u.last_name, u.email, u.user_type, 
                     pd.provider_type, pd.license_number, pd.bio, pd.services_offered
              FROM users u
              LEFT JOIN provider_details pd ON u.id = pd.user_id
              WHERE u.user_type = 'provider'
              ORDER BY u.id";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return ["error" => "Database error: " . mysqli_error($conn)];
    }
    
    if (mysqli_num_rows($result) === 0) {
        return ["message" => "No providers found in the database"];
    }
    
    while ($row = mysqli_fetch_assoc($result)) {
        $providers[] = [
            "id" => $row['id'],
            "name" => $row['first_name'] . ' ' . $row['last_name'],
            "email" => $row['email'],
            "user_type" => $row['user_type'],
            "provider_type" => $row['provider_type'],
            "license_number" => $row['license_number'],
            "bio" => $row['bio'],
            "services_offered" => $row['services_offered']
        ];
    }
    
    return ["providers" => $providers, "count" => count($providers)];
}

// Function to check appointment table structure
function checkAppointmentsTable($conn) {
    $query = "SHOW TABLES LIKE 'appointments'";
    $result = mysqli_query($conn, $query);
    
    if (!$result || mysqli_num_rows($result) === 0) {
        return ["error" => "Appointments table does not exist"];
    }
    
    $query = "SHOW COLUMNS FROM appointments";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return ["error" => "Could not get columns: " . mysqli_error($conn)];
    }
    
    $columns = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $columns[] = $row['Field'];
    }
    
    // Check how many appointments exist
    $query = "SELECT COUNT(*) as total FROM appointments";
    $result = mysqli_query($conn, $query);
    
    $appointmentCount = 0;
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $appointmentCount = $row['total'];
    }
    
    return [
        "table_exists" => true,
        "columns" => $columns,
        "appointment_count" => $appointmentCount
    ];
}

// Function to get appointments for a specific provider
function getProviderAppointments($conn, $providerId) {
    $query = "SELECT * FROM appointments WHERE provider_id = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 's', $providerId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        return ["error" => "Database error: " . mysqli_error($conn)];
    }
    
    $appointments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $appointments[] = $row;
    }
    
    return [
        "provider_id" => $providerId,
        "appointments" => $appointments,
        "count" => count($appointments)
    ];
}

// Get all database info
$results = [
    "providers" => getAllProviders($conn),
    "appointments_table" => checkAppointmentsTable($conn),
    "database_info" => [
        "host" => defined('DB_HOST') ? DB_HOST : 'unknown',
        "database" => defined('DB_NAME') ? DB_NAME : 'unknown',
        "connected" => $conn ? true : false
    ],
    "timestamp" => date('Y-m-d H:i:s')
];

// Check for specific provider
if (isset($_GET['providerId'])) {
    $providerId = $_GET['providerId'];
    $results["provider_appointments"] = getProviderAppointments($conn, $providerId);
}

// Output results
echo json_encode($results, JSON_PRETTY_PRINT);

// Close connection
mysqli_close($conn);
?> 