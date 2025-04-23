<?php
// Enable detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/config/database.php';

echo "<h1>Fix Provider IDs</h1>";

// Check connection
if (!$conn) {
    die("<p style='color: red;'>Connection failed: " . mysqli_connect_error() . "</p>");
}

echo "<p>Database connection: <strong>successful</strong></p>";

// Fix issue with provider_id
$update_sql = "UPDATE appointments SET provider_id = 'provider-123'";
if (mysqli_query($conn, $update_sql)) {
    $rows = mysqli_affected_rows($conn);
    echo "<p style='color: green;'>Successfully updated $rows appointments with provider_id = 'provider-123'</p>";
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

// Check the fetch-provider-appointments.php endpoint manually
echo "<h2>Test API Endpoint</h2>";
$url = "http://localhost/Major%20project/backend/fetch-provider-appointments.php?providerId=provider-123";
echo "<p>Testing API call to: <code>$url</code></p>";

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

echo "<p>API Response Code: <strong>$http_code</strong></p>";
if ($response) {
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<p>API Response: <pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre></p>";
        
        if (isset($data['appointments']) && count($data['appointments']) > 0) {
            echo "<p style='color: green;'>Success! Found " . count($data['appointments']) . " appointments from API</p>";
        } else {
            echo "<p style='color: red;'>API returned no appointments</p>";
        }
    } else {
        echo "<p style='color: red;'>Error parsing JSON response: " . json_last_error_msg() . "</p>";
        echo "<p>Raw response: <pre>" . htmlspecialchars($response) . "</pre></p>";
    }
} else {
    echo "<p style='color: red;'>No response from API</p>";
}

echo "<p><a href='../HTML/provider-dashboard.html' style='padding: 10px 15px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>Go to Provider Dashboard</a></p>";

// Close the connection
mysqli_close($conn);
?> 