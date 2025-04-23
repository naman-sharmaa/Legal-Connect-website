<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database config
require_once __DIR__ . '/config/database.php';

// Set header for JSON output
header('Content-Type: application/json');

// Function to get unique provider IDs from appointments
function getUniqueProviderIds($conn) {
    $query = "SELECT DISTINCT provider_id FROM appointments ORDER BY provider_id";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return ["error" => "Database error: " . mysqli_error($conn)];
    }
    
    $provider_ids = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $provider_ids[] = $row['provider_id'];
    }
    
    return $provider_ids;
}

// Function to get provider details for each ID
function getProviderDetails($conn, $provider_ids) {
    $providers = [];
    
    foreach ($provider_ids as $id) {
        // Check if it's a user ID
        $query = "SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) as name, 'user_id' as id_type 
                  FROM users u 
                  WHERE u.id = ? AND u.user_type = 'provider'";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 's', $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $is_user_id = false;
        if ($result && mysqli_num_rows($result) > 0) {
            $user_data = mysqli_fetch_assoc($result);
            $providers[] = [
                'provider_id' => $id,
                'id_type' => 'user_id',
                'name' => $user_data['name'],
                'id_match' => true
            ];
            $is_user_id = true;
        }
        mysqli_stmt_close($stmt);
        
        // If not a user ID, check if it's a license number
        if (!$is_user_id) {
            $query = "SELECT pd.license_number, CONCAT(u.first_name, ' ', u.last_name) as name, 'license_number' as id_type 
                      FROM provider_details pd 
                      JOIN users u ON pd.user_id = u.id 
                      WHERE pd.license_number = ?";
            
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 's', $id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $license_data = mysqli_fetch_assoc($result);
                $providers[] = [
                    'provider_id' => $id,
                    'id_type' => 'license_number',
                    'name' => $license_data['name'],
                    'id_match' => true
                ];
            } else {
                // Unknown ID type
                $providers[] = [
                    'provider_id' => $id,
                    'id_type' => 'unknown',
                    'name' => 'Not Found',
                    'id_match' => false
                ];
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    return $providers;
}

// Get number of appointments for each provider ID
function getAppointmentCounts($conn) {
    $query = "SELECT provider_id, COUNT(*) as count FROM appointments GROUP BY provider_id ORDER BY count DESC";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return ["error" => "Database error: " . mysqli_error($conn)];
    }
    
    $counts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $counts[$row['provider_id']] = $row['count'];
    }
    
    return $counts;
}

// Get analysis of providers and their appointments
$provider_ids = getUniqueProviderIds($conn);
$provider_details = getProviderDetails($conn, $provider_ids);
$appointment_counts = getAppointmentCounts($conn);

// Create summary
$summary = [
    'providers_in_appointments' => count($provider_ids),
    'providers_matched' => 0,
    'providers_not_matched' => 0,
    'providers_by_user_id' => 0,
    'providers_by_license' => 0,
    'providers_unknown' => 0
];

foreach ($provider_details as $provider) {
    if ($provider['id_match']) {
        $summary['providers_matched']++;
        if ($provider['id_type'] === 'user_id') {
            $summary['providers_by_user_id']++;
        } else if ($provider['id_type'] === 'license_number') {
            $summary['providers_by_license']++;
        }
    } else {
        $summary['providers_not_matched']++;
        if ($provider['id_type'] === 'unknown') {
            $summary['providers_unknown']++;
        }
    }
}

// Output results
echo json_encode([
    'summary' => $summary,
    'provider_ids' => $provider_ids,
    'provider_details' => $provider_details,
    'appointment_counts' => $appointment_counts
], JSON_PRETTY_PRINT);

// Close connection
mysqli_close($conn);
?> 