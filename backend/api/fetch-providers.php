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

// Fetch providers from the database
try {
    // Query to join users and provider_details tables
    $query = "SELECT 
        u.id as user_id,
        CONCAT(u.first_name, ' ', u.last_name) as name,
        u.email,
        p.provider_type,
        p.license_number,
        p.bio,
        p.services_offered
    FROM users u
    JOIN provider_details p ON u.id = p.user_id
    WHERE u.user_type = 'provider'";
    
    error_log("Executing query: $query");
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        error_log("Database error: " . mysqli_error($conn));
        send_json_response(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)], 500);
        exit;
    }
    
    $providers = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        // Use license_number as the provider ID
        $providers[] = [
            'id' => $row['license_number'],  // Use license_number as ID
            'user_id' => $row['user_id'],    // Keep user_id for reference
            'name' => $row['name'],
            'email' => $row['email'],
            'provider_type' => $row['provider_type'],
            'license_number' => $row['license_number'],
            'bio' => $row['bio'],
            'services_offered' => $row['services_offered']
        ];
    }
    
    // Check if we have any providers
    if (count($providers) === 0) {
        // Add fallback test data if no providers found
        $providers = [
            [
                'id' => 'LAW-789012',
                'user_id' => 1,
                'name' => 'Jane Smith',
                'email' => 'provider@example.com',
                'provider_type' => 'Criminal Legal Advisor',
                'license_number' => 'LAW-789012',
                'bio' => 'Experienced criminal legal advisor with over 10 years in the field.',
                'services_offered' => 'Criminal law consultation, Legal defense, Court representation'
            ]
        ];
        send_json_response(['success' => true, 'message' => 'No providers found in database, returning test data', 'providers' => $providers]);
    } else {
        send_json_response(['success' => true, 'providers' => $providers]);
    }
    
} catch (Exception $e) {
    error_log("Error fetching providers: " . $e->getMessage());
    send_json_response(['success' => false, 'message' => 'Error fetching providers: ' . $e->getMessage()], 500);
}
?> 