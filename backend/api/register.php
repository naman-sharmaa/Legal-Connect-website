<?php
// Include initialization file
require_once __DIR__ . '/../inc/init.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(['error' => 'Method not allowed'], 405);
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST; // Fallback to $_POST if JSON is not available
}

// Check user type
$user_type = isset($data['user_type']) ? sanitize_input($data['user_type']) : 'user';
if (!in_array($user_type, ['user', 'provider'])) {
    send_json_response(['error' => 'Invalid user type'], 400);
}

// Validate required fields for all users
$required_fields = ['email', 'password', 'confirm_password'];
if ($user_type === 'user') {
    $required_fields = array_merge($required_fields, ['first_name', 'last_name']);
} else {
    $required_fields = array_merge($required_fields, ['fullname', 'provider_type', 'license_number']);
}

foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        send_json_response(['error' => ucfirst(str_replace('_', ' ', $field)) . ' is required'], 400);
    }
}

// Check if email already exists
$email = sanitize_input($data['email']);
$check_email_query = "SELECT id FROM users WHERE email = '$email'";
$result = mysqli_query($conn, $check_email_query);
if (mysqli_num_rows($result) > 0) {
    send_json_response(['error' => 'Email already exists'], 400);
}

// Validate password match
if ($data['password'] !== $data['confirm_password']) {
    send_json_response(['error' => 'Passwords do not match'], 400);
}

// Hash password
$hashed_password = hash_password($data['password']);

// Insert user data
if ($user_type === 'user') {
    $first_name = sanitize_input($data['first_name']);
    $last_name = sanitize_input($data['last_name']);
    $phone = isset($data['phone']) ? sanitize_input($data['phone']) : '';
    
    $query = "INSERT INTO users (first_name, last_name, email, phone, password, user_type) 
              VALUES ('$first_name', '$last_name', '$email', '$phone', '$hashed_password', '$user_type')";
} else {
    // For providers, we'll split the fullname into first_name and last_name
    $fullname = sanitize_input($data['fullname']);
    $name_parts = explode(' ', $fullname, 2);
    $first_name = $name_parts[0];
    $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
    $phone = isset($data['provider_phone']) ? sanitize_input($data['provider_phone']) : '';
    
    // Begin transaction
    mysqli_begin_transaction($conn);
    
    $query = "INSERT INTO users (first_name, last_name, email, phone, password, user_type) 
              VALUES ('$first_name', '$last_name', '$email', '$phone', '$hashed_password', '$user_type')";
    
    if (mysqli_query($conn, $query)) {
        $user_id = mysqli_insert_id($conn);
        $provider_type = sanitize_input($data['provider_type']);
        $license_number = sanitize_input($data['license_number']);
        
        // Add bio and services_offered if they exist
        $bio = isset($data['bio']) ? sanitize_input($data['bio']) : '';
        $services_offered = isset($data['services_offered']) ? sanitize_input($data['services_offered']) : '';
        
        $provider_query = "INSERT INTO provider_details (user_id, provider_type, license_number, bio, services_offered) 
                          VALUES ('$user_id', '$provider_type', '$license_number', '$bio', '$services_offered')";
        
        if (!mysqli_query($conn, $provider_query)) {
            // Rollback if provider details insertion fails
            mysqli_rollback($conn);
            send_json_response(['error' => 'Registration failed: ' . mysqli_error($conn)], 500);
        }
        
        // Commit transaction
        mysqli_commit($conn);
        send_json_response(['success' => true, 'message' => 'Provider registered successfully']);
    } else {
        mysqli_rollback($conn);
        send_json_response(['error' => 'Registration failed: ' . mysqli_error($conn)], 500);
    }
    
    exit; // Exit early for provider registration
}

// Execute the query for regular users
if (mysqli_query($conn, $query)) {
    send_json_response(['success' => true, 'message' => 'User registered successfully']);
} else {
    send_json_response(['error' => 'Registration failed: ' . mysqli_error($conn)], 500);
} 