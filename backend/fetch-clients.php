<?php
// Start output buffering to prevent PHP errors from corrupting JSON output
ob_start();

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable output of errors to the browser

// Set header for JSON response
header('Content-Type: application/json');

try {
    // Include database configuration
    require_once 'config/database.php';
    
    // Check if database connection is valid
    if (!isset($conn) || !$conn) {
        throw new Exception("Database connection failed");
    }

    // Fetch all clients (users with user_type = "user")
    $query = "SELECT id, first_name, last_name, email, phone, created_at 
              FROM users 
              WHERE user_type = 'user' 
              ORDER BY created_at DESC";

    $result = mysqli_query($conn, $query);

    if (!$result) {
        throw new Exception("Database query error: " . mysqli_error($conn));
    }

    // Fetch clients into array
    $clients = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Format the created_at date
        if (!empty($row['created_at'])) {
            $created_at = new DateTime($row['created_at']);
            $row['formatted_date'] = $created_at->format('M d, Y');
        } else {
            $row['formatted_date'] = 'Unknown';
        }
        
        // Create full name
        $row['full_name'] = $row['first_name'] . ' ' . $row['last_name'];
        
        // Add to clients array
        $clients[] = $row;
    }

    // Clear the output buffer
    ob_end_clean();
    
    // Return success response with clients data
    echo json_encode([
        'success' => true,
        'count' => count($clients),
        'clients' => $clients
    ]);

} catch (Exception $e) {
    // Clear the output buffer
    ob_end_clean();
    
    // Return a clean JSON error response
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

// Close connection
if (isset($conn)) {
    mysqli_close($conn);
}
?> 