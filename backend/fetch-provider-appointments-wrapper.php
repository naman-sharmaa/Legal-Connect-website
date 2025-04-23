<?php
header('Content-Type: application/json');

// Force test provider check to prevent John Lawyer data
if (isset($_GET['providerId']) && ($_GET['providerId'] === 'provider-123' || $_GET['providerId'] === 'LAW-789012')) {
    echo json_encode([
        'success' => false,
        'message' => 'Test provider IDs are disabled for security reasons'
    ]);
    exit;
}

// Include the original file
require_once 'fetch-provider-appointments.php';
?> 