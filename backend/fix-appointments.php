<?php
// Enable detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/config/database.php';

echo "<h1>Fix Appointment Provider IDs</h1>";

// First check current appointments
$query = "SELECT id, provider_id, provider_name FROM appointments";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    echo "<h2>Current Provider IDs Before Update</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Provider ID</th><th>Provider Name</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['provider_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['provider_name']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// Check the data types
echo "<h2>Column Types</h2>";
$columnQuery = "SHOW COLUMNS FROM appointments WHERE Field = 'provider_id'";
$columnResult = mysqli_query($conn, $columnQuery);
if ($columnResult && mysqli_num_rows($columnResult) > 0) {
    $column = mysqli_fetch_assoc($columnResult);
    echo "<p>provider_id column type: <strong>" . $column['Type'] . "</strong></p>";
}

// Try a direct update with a specific provider_id value
$hardcodeUpdate = "UPDATE appointments SET provider_id = 'provider-123'";
if (mysqli_query($conn, $hardcodeUpdate)) {
    $rowsAffected = mysqli_affected_rows($conn);
    echo "<p style='color: green;'>Successfully updated {$rowsAffected} appointment(s) to use provider_id 'provider-123'</p>";
} else {
    echo "<p style='color: red;'>Error updating provider IDs: " . mysqli_error($conn) . "</p>";
}

// Verify the update
$afterQuery = "SELECT id, provider_id, provider_name FROM appointments";
$afterResult = mysqli_query($conn, $afterQuery);

if ($afterResult && mysqli_num_rows($afterResult) > 0) {
    echo "<h2>Provider IDs After Update</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Provider ID</th><th>Provider Name</th></tr>";
    
    while ($row = mysqli_fetch_assoc($afterResult)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['provider_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['provider_name']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

echo "<p><a href='../HTML/provider-dashboard.html' style='padding: 10px 15px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>Go to Provider Dashboard</a></p>";

// Close connection
mysqli_close($conn);
?> 