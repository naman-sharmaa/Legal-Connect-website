<?php
// Include initialization file
require_once __DIR__ . '/../inc/init.php';

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_json_response(['error' => 'Method not allowed'], 405);
}

// Fetch all providers from the database
$query = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.user_type, 
          pd.provider_type, pd.license_number, pd.bio, pd.services_offered, pd.available_hours 
          FROM users u
          JOIN provider_details pd ON u.id = pd.user_id
          WHERE u.user_type = 'provider'";

// Check if we should filter by provider type
if (isset($_GET['provider_type']) && !empty($_GET['provider_type'])) {
    $provider_type = sanitize_input($_GET['provider_type']);
    $query .= " AND pd.provider_type = '$provider_type'";
}

// Execute query
$result = mysqli_query($conn, $query);

if (!$result) {
    send_json_response([
        'success' => false,
        'error' => 'Failed to fetch providers: ' . mysqli_error($conn)
    ], 500);
}

// Prepare providers array
$providers = [];

while ($row = mysqli_fetch_assoc($result)) {
    // Format provider data
    $providers[] = [
        'id' => $row['id'],
        'name' => $row['first_name'] . ' ' . $row['last_name'],
        'email' => $row['email'],
        'phone' => $row['phone'],
        'user_type' => $row['user_type'],
        'provider_type' => $row['provider_type'],
        'license_number' => $row['license_number'],
        'bio' => $row['bio'],
        'services_offered' => $row['services_offered'],
        'available_hours' => $row['available_hours']
    ];
}

// Send response
send_json_response([
    'success' => true,
    'providers' => $providers
]); 