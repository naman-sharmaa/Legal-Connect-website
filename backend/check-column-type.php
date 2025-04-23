<?php
// Enable detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/config/database.php';

echo "<h1>Check Column Types</h1>";

// Check if database connection is successful
if (!$conn) {
    die("<p style='color: red;'>Database connection failed: " . mysqli_connect_error() . "</p>");
}

echo "<p>Database connection: <strong>successful</strong></p>";

// Get the structure of the appointments table
$query = "DESCRIBE appointments";
$result = mysqli_query($conn, $query);

if ($result) {
    echo "<h2>Appointments Table Structure</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p style='color: red;'>Error querying table structure: " . mysqli_error($conn) . "</p>";
}

// Try to manually update one record
$update_one = "UPDATE appointments SET provider_id = 'LIC-12345' WHERE id = 1";
if (mysqli_query($conn, $update_one)) {
    $affected = mysqli_affected_rows($conn);
    echo "<p style='color: green;'>Successfully updated $affected appointment (ID=1)</p>";
} else {
    echo "<p style='color: red;'>Error updating appointment: " . mysqli_error($conn) . "</p>";
}

// Check a single appointment after update
$check_query = "SELECT id, provider_id, provider_name FROM appointments WHERE id = 1";
$check_result = mysqli_query($conn, $check_query);
if ($check_result && mysqli_num_rows($check_result) > 0) {
    $appointment = mysqli_fetch_assoc($check_result);
    echo "<p>Appointment ID=1 now has provider_id: <strong>" . htmlspecialchars($appointment['provider_id']) . "</strong></p>";
}

echo "<p><a href='../HTML/provider-dashboard.html' style='padding: 10px 15px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>Go to Provider Dashboard</a></p>";

// Close the connection
mysqli_close($conn);
?> 