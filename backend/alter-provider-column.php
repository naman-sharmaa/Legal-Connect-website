<?php
// Enable detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/config/database.php';

echo "<h1>Modify Provider ID Column</h1>";

// Check if database connection is successful
if (!$conn) {
    die("<p style='color: red;'>Database connection failed: " . mysqli_connect_error() . "</p>");
}

echo "<p>Database connection: <strong>successful</strong></p>";

// First, check the current column type
$query = "SHOW COLUMNS FROM appointments WHERE Field = 'provider_id'";
$result = mysqli_query($conn, $query);
if ($result && mysqli_num_rows($result) > 0) {
    $column = mysqli_fetch_assoc($result);
    echo "<p>Current provider_id column type: <strong>" . htmlspecialchars($column['Type']) . "</strong></p>";
}

// Alter the provider_id column type to VARCHAR(100)
$alter_query = "ALTER TABLE appointments MODIFY COLUMN provider_id VARCHAR(100) NOT NULL";
if (mysqli_query($conn, $alter_query)) {
    echo "<p style='color: green;'>Successfully modified provider_id column to VARCHAR(100)</p>";
} else {
    echo "<p style='color: red;'>Error modifying column: " . mysqli_error($conn) . "</p>";
}

// Verify the column type after modification
$verify_query = "SHOW COLUMNS FROM appointments WHERE Field = 'provider_id'";
$verify_result = mysqli_query($conn, $verify_query);
if ($verify_result && mysqli_num_rows($verify_result) > 0) {
    $column = mysqli_fetch_assoc($verify_result);
    echo "<p>New provider_id column type: <strong>" . htmlspecialchars($column['Type']) . "</strong></p>";
}

// Now update the appointments to use license number
$license_number = 'LIC-12345'; // Test provider's license number
$update_query = "UPDATE appointments SET provider_id = '$license_number'";
if (mysqli_query($conn, $update_query)) {
    $affected = mysqli_affected_rows($conn);
    echo "<p style='color: green;'>Successfully updated $affected appointments to use license number $license_number</p>";
} else {
    echo "<p style='color: red;'>Error updating appointments: " . mysqli_error($conn) . "</p>";
}

// Check the updated appointments
$check_query = "SELECT id, provider_id, provider_name FROM appointments LIMIT 5";
$check_result = mysqli_query($conn, $check_query);
if ($check_result && mysqli_num_rows($check_result) > 0) {
    echo "<h2>Sample of Updated Appointments</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Provider ID</th><th>Provider Name</th></tr>";
    
    while ($row = mysqli_fetch_assoc($check_result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['provider_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['provider_name']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

echo "<p><a href='../HTML/provider-dashboard.html' style='padding: 10px 15px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>Go to Provider Dashboard</a></p>";

// Close the connection
mysqli_close($conn);
?> 