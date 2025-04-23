<?php
// Enable detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/config/database.php';

echo "<h1>Update Provider IDs to License Numbers</h1>";

// Check connection
if (!$conn) {
    die("<p style='color: red;'>Connection failed: " . mysqli_connect_error() . "</p>");
}

echo "<p>Database connection: <strong>successful</strong></p>";

// First, get the test provider's license number
$license_number = 'LIC-12345'; // This is the license number from the test provider data

// Update existing appointments to use the license number as provider_id
$update_sql = "UPDATE appointments SET provider_id = '$license_number'";
if (mysqli_query($conn, $update_sql)) {
    $rows = mysqli_affected_rows($conn);
    echo "<p style='color: green;'>Successfully updated $rows appointments with provider_id = '$license_number'</p>";
} else {
    echo "<p style='color: red;'>Error updating appointments: " . mysqli_error($conn) . "</p>";
}

// Verify if the update worked
$verify_sql = "SELECT id, provider_id, provider_name, client_name, appointment_date FROM appointments";
$result = mysqli_query($conn, $verify_sql);

if ($result) {
    $count = mysqli_num_rows($result);
    echo "<p>Found $count appointments in the database</p>";
    
    if ($count > 0) {
        echo "<h2>Current Appointments</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Provider ID</th><th>Provider Name</th><th>Client</th><th>Date</th></tr>";
        
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['provider_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['provider_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['client_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['appointment_date']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p style='color: red;'>Error querying appointments: " . mysqli_error($conn) . "</p>";
}

echo "<p><a href='../HTML/provider-dashboard.html' style='padding: 10px 15px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>Go to Provider Dashboard</a></p>";

// Close the connection
mysqli_close($conn);
?> 