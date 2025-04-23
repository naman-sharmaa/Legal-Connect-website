<?php
// Define constant to prevent direct file access
define('DIRECT_ACCESS_CHECK', true);

// Start session
session_start();

// Set error reporting based on environment
$environment = 'development'; // Change to 'production' for live site
if ($environment === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Enable CORS for API endpoints
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include configuration and utility files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php'; 