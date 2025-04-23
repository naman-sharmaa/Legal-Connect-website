<?php
// Prevent direct access to this file
if (!defined('DIRECT_ACCESS_CHECK')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

/**
 * Sanitize user input
 * 
 * @param string $data User input data
 * @return string Sanitized data
 */
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    if ($conn) {
        $data = mysqli_real_escape_string($conn, $data);
    }
    return $data;
}

/**
 * Generate a secure random token
 * 
 * @param int $length Length of the token
 * @return string Random token
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Hash a password securely
 * 
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify a password against a hash
 * 
 * @param string $password Plain text password
 * @param string $hash Hashed password
 * @return bool True if password matches hash
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Send JSON response
 * 
 * @param array $data Response data
 * @param int $status HTTP status code
 */
function send_json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect to a URL
 * 
 * @param string $url URL to redirect to
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Check if the request is authenticated and return the user ID
 * 
 * @return string|null User ID if authenticated, null otherwise
 */
function check_authenticated_request() {
    global $conn;
    
    // First check Authorization header for Bearer token
    $auth_header = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
    $token = '';
    
    // Extract token from Authorization header
    if (!empty($auth_header)) {
        if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
            $token = $matches[1];
        }
    }
    
    // If no token in Authorization header, check if it's in POST data
    if (empty($token)) {
        $data = json_decode(file_get_contents('php://input'), true);
        $token = isset($data['session_id']) ? $data['session_id'] : '';
    }
    
    // If still no token, check if it's in GET params
    if (empty($token) && isset($_GET['token'])) {
        $token = $_GET['token'];
    }
    
    // If still no token, check session
    if (empty($token) && isset($_SESSION['session_id'])) {
        $token = $_SESSION['session_id'];
    }
    
    // If we have a token, validate it
    if (!empty($token)) {
        // Check if token exists in the database
        $query = "SELECT s.*, u.id as user_id 
                 FROM sessions s 
                 JOIN users u ON s.user_id = u.id 
                 WHERE s.id = '$token' AND s.expires_at > NOW()";
        
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) === 1) {
            $row = mysqli_fetch_assoc($result);
            
            // Update session expiration time
            $new_expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
            $update_query = "UPDATE sessions SET expires_at = '$new_expires_at' WHERE id = '$token'";
            mysqli_query($conn, $update_query);
            
            // Set session variables
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['session_id'] = $token;
            
            return $row['user_id'];
        }
    }
    
    return null;
} 