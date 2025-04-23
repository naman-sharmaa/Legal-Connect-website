<?php
// Include initialization file
require_once __DIR__ . '/../inc/init.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(['error' => 'Method not allowed'], 405);
}

// Get session ID from various sources
$session_id = '';

// Check if we have a session ID in PHP session
if (isset($_SESSION['session_id'])) {
    $session_id = $_SESSION['session_id'];
}

// If not, check authorization header
if (empty($session_id)) {
    $auth_header = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
    
    if (!empty($auth_header) && preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        $session_id = $matches[1];
    }
}

// If we found a session ID, remove it from the database
if (!empty($session_id)) {
    $query = "DELETE FROM sessions WHERE id = '$session_id'";
    mysqli_query($conn, $query);
}

// Clear PHP session data
$_SESSION = array();

// If a session cookie exists, destroy it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Send successful response
send_json_response([
    'success' => true,
    'message' => 'Logged out successfully'
]);
?> 