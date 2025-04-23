<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database config
require_once __DIR__ . '/config/database.php';

// Set header for JSON output
header('Content-Type: application/json');

// Backup the appointments table before making changes
function backupAppointmentsTable($conn) {
    // Create backup table
    $backup_query = "CREATE TABLE IF NOT EXISTS appointments_backup LIKE appointments";
    $backup_result = mysqli_query($conn, $backup_query);
    
    if (!$backup_result) {
        return [
            'success' => false,
            'message' => 'Failed to create backup table: ' . mysqli_error($conn)
        ];
    }
    
    // Copy data to backup table
    $copy_query = "INSERT INTO appointments_backup SELECT * FROM appointments";
    $copy_result = mysqli_query($conn, $copy_query);
    
    if (!$copy_result) {
        return [
            'success' => false,
            'message' => 'Failed to copy data to backup table: ' . mysqli_error($conn)
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Successfully created backup table appointments_backup'
    ];
}

// Get a mapping of license numbers to user IDs
function getLicenseToUserIdMap($conn) {
    $query = "SELECT pd.license_number, u.id as user_id, CONCAT(u.first_name, ' ', u.last_name) as name
              FROM provider_details pd 
              JOIN users u ON pd.user_id = u.id
              WHERE pd.license_number IS NOT NULL AND pd.license_number != ''";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return [
            'success' => false,
            'message' => 'Failed to query license numbers: ' . mysqli_error($conn)
        ];
    }
    
    $mapping = [];
    $providers = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $mapping[$row['license_number']] = $row['user_id'];
        $providers[] = [
            'license_number' => $row['license_number'],
            'user_id' => $row['user_id'],
            'name' => $row['name']
        ];
    }
    
    return [
        'success' => true,
        'mapping' => $mapping,
        'providers' => $providers
    ];
}

// Update appointments table to use user IDs
function updateAppointmentsTable($conn, $mapping) {
    $updated_count = 0;
    $errors = [];
    
    // Get current provider IDs in appointments table
    $query = "SELECT DISTINCT provider_id FROM appointments";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return [
            'success' => false,
            'message' => 'Failed to query appointments: ' . mysqli_error($conn)
        ];
    }
    
    $provider_ids = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $provider_ids[] = $row['provider_id'];
    }
    
    // Update each provider ID if it's a license number
    foreach ($provider_ids as $provider_id) {
        if (isset($mapping[$provider_id])) {
            $user_id = $mapping[$provider_id];
            
            $update_query = "UPDATE appointments SET provider_id = ? WHERE provider_id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, 'ss', $user_id, $provider_id);
            $update_result = mysqli_stmt_execute($stmt);
            
            if ($update_result) {
                $affected_rows = mysqli_stmt_affected_rows($stmt);
                $updated_count += $affected_rows;
            } else {
                $errors[] = "Failed to update provider_id '$provider_id': " . mysqli_error($conn);
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    
    return [
        'success' => $updated_count > 0 || count($errors) === 0,
        'updated_count' => $updated_count,
        'provider_ids_found' => $provider_ids,
        'errors' => $errors
    ];
}

// Main execution

// 1. Create backup
$backup_result = backupAppointmentsTable($conn);
if (!$backup_result['success']) {
    echo json_encode([
        'success' => false,
        'message' => 'Backup failed. Aborting update.',
        'backup_result' => $backup_result
    ]);
    exit;
}

// 2. Get license to user ID mapping
$mapping_result = getLicenseToUserIdMap($conn);
if (!$mapping_result['success']) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to get license mapping. Aborting update.',
        'mapping_result' => $mapping_result
    ]);
    exit;
}

// 3. Update appointments table
$update_result = updateAppointmentsTable($conn, $mapping_result['mapping']);

// Output results
echo json_encode([
    'success' => $update_result['success'],
    'message' => $update_result['success'] 
        ? "Successfully updated {$update_result['updated_count']} appointments to use user IDs" 
        : "Failed to update appointments",
    'backup_result' => $backup_result,
    'providers' => $mapping_result['providers'],
    'update_result' => $update_result
], JSON_PRETTY_PRINT);

// Close connection
mysqli_close($conn);
?> 