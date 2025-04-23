<?php
// Include initialization file
require_once __DIR__ . '/../inc/init.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(['error' => 'Method not allowed'], 405);
}

// Check authentication
$user_id = check_authenticated_request();
if (!$user_id) {
    send_json_response(['error' => 'Authentication required'], 401);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    send_json_response(['error' => 'Invalid request data'], 400);
}

// Ensure required fields are present
if (empty($data['first_name']) || empty($data['last_name'])) {
    send_json_response(['error' => 'First name and last name are required'], 400);
}

// Sanitize inputs
$first_name = sanitize_input($data['first_name']);
$last_name = sanitize_input($data['last_name']);
$phone = !empty($data['phone']) ? sanitize_input($data['phone']) : '';

// Get user from database to check user type
$user_query = "SELECT user_type FROM users WHERE id = '$user_id'";
$user_result = mysqli_query($conn, $user_query);

if (!$user_result || mysqli_num_rows($user_result) === 0) {
    send_json_response(['error' => 'User not found'], 404);
}

$user = mysqli_fetch_assoc($user_result);
$user_type = $user['user_type'];

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Update user info
    $update_query = "UPDATE users SET 
                    first_name = '$first_name', 
                    last_name = '$last_name', 
                    phone = '$phone'
                    WHERE id = '$user_id'";
    
    $update_result = mysqli_query($conn, $update_query);
    
    if (!$update_result) {
        throw new Exception("Failed to update user information: " . mysqli_error($conn));
    }
    
    // If user is a provider, update provider details
    if ($user_type === 'provider' && 
        (isset($data['provider_type']) || isset($data['license_number']) || 
         isset($data['bio']) || isset($data['services_offered']))) {
        
        // Sanitize provider inputs
        $provider_type = isset($data['provider_type']) ? sanitize_input($data['provider_type']) : '';
        $license_number = isset($data['license_number']) ? sanitize_input($data['license_number']) : '';
        $bio = isset($data['bio']) ? sanitize_input($data['bio']) : '';
        $services_offered = isset($data['services_offered']) ? sanitize_input($data['services_offered']) : '';
        
        // Check if provider details exist
        $check_query = "SELECT * FROM provider_details WHERE user_id = '$user_id'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            // Update existing provider details
            $provider_update_query = "UPDATE provider_details SET 
                                    provider_type = '$provider_type', 
                                    license_number = '$license_number', 
                                    bio = '$bio', 
                                    services_offered = '$services_offered'
                                    WHERE user_id = '$user_id'";
        } else {
            // Insert new provider details
            $provider_update_query = "INSERT INTO provider_details 
                                    (user_id, provider_type, license_number, bio, services_offered) 
                                    VALUES 
                                    ('$user_id', '$provider_type', '$license_number', '$bio', '$services_offered')";
        }
        
        $provider_update_result = mysqli_query($conn, $provider_update_query);
        
        if (!$provider_update_result) {
            throw new Exception("Failed to update provider details: " . mysqli_error($conn));
        }
    }
    
    // If everything went well, commit the transaction
    mysqli_commit($conn);
    
    // Prepare response
    $response = [
        'success' => true,
        'message' => 'Profile updated successfully',
        'user' => [
            'id' => $user_id,
            'name' => $first_name . ' ' . $last_name,
            'phone' => $phone,
            'user_type' => $user_type
        ]
    ];
    
    // Add provider details to response if applicable
    if ($user_type === 'provider') {
        $response['user']['provider_type'] = $data['provider_type'] ?? '';
        $response['user']['license_number'] = $data['license_number'] ?? '';
        $response['user']['bio'] = $data['bio'] ?? '';
        $response['user']['services_offered'] = $data['services_offered'] ?? '';
    }
    
    send_json_response($response);
    
} catch (Exception $e) {
    // Something went wrong, rollback the transaction
    mysqli_rollback($conn);
    error_log("Profile update error: " . $e->getMessage());
    send_json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
?> 