<?php
// Enable detailed error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include initialization file
require_once __DIR__ . '/../inc/init.php';

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html");

// Simple HTML header
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Admin Tools</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .button { 
            display: inline-block; 
            background-color: #4CAF50; 
            color: white; 
            padding: 10px 15px; 
            text-decoration: none; 
            border-radius: 4px;
            margin: 5px;
        }
        .button.danger { background-color: #f44336; }
    </style>
</head>
<body>
    <h1>Database Admin Tools</h1>
';

// Process actions
$action = isset($_GET['action']) ? $_GET['action'] : '';
$message = '';

if ($action === 'recreate_appointments') {
    // Drop appointments table
    mysqli_query($conn, "DROP TABLE IF EXISTS appointments");
    $message = "<p class='success'>Appointments table dropped. Recreating...</p>";
    
    // Create appointments table
    $createTableQuery = "CREATE TABLE IF NOT EXISTS appointments (
        id VARCHAR(255) PRIMARY KEY,
        provider_id VARCHAR(255) NOT NULL,
        provider_name VARCHAR(255) NOT NULL,
        client_id VARCHAR(255) NOT NULL,
        client_name VARCHAR(255) NOT NULL,
        date DATE NOT NULL,
        time TIME NOT NULL,
        type VARCHAR(100) NOT NULL,
        description TEXT,
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (provider_id),
        INDEX (client_id),
        INDEX (status),
        INDEX (date)
    )";
    
    if (mysqli_query($conn, $createTableQuery)) {
        $message .= "<p class='success'>Appointments table created successfully.</p>";
    } else {
        $message .= "<p class='error'>Error creating appointments table: " . mysqli_error($conn) . "</p>";
    }
} elseif ($action === 'create_sample_appointments') {
    // Redirect to sample data creation script
    header("Location: ../create-sample-appointments.php");
    exit;
}

// Display message if any
if (!empty($message)) {
    echo $message;
}

// Check database connection
echo "<h2>Database Connection</h2>";
if ($conn) {
    echo "<p class='success'>Database connection is working</p>";
} else {
    echo "<p class='error'>Database connection failed: " . mysqli_connect_error() . "</p>";
}

// List tables
echo "<h2>Database Tables</h2>";
$result = mysqli_query($conn, "SHOW TABLES");
if ($result) {
    if (mysqli_num_rows($result) > 0) {
        echo "<table>";
        echo "<tr><th>Table Name</th><th>Row Count</th><th>Created</th><th>Status</th></tr>";
        
        while ($row = mysqli_fetch_row($result)) {
            $tableName = $row[0];
            
            // Get row count
            $countResult = mysqli_query($conn, "SELECT COUNT(*) FROM $tableName");
            $rowCount = 0;
            if ($countResult) {
                $countRow = mysqli_fetch_row($countResult);
                $rowCount = $countRow[0];
            }
            
            // Get create time (not directly available in MySQL, so we'll skip)
            $createTime = "N/A";
            
            // Check table status
            $statusResult = mysqli_query($conn, "CHECK TABLE $tableName");
            $status = "Unknown";
            $statusClass = "";
            if ($statusResult) {
                $statusRow = mysqli_fetch_assoc($statusResult);
                if ($statusRow['Msg_text'] === "OK") {
                    $status = "OK";
                    $statusClass = "success";
                } else {
                    $status = $statusRow['Msg_text'];
                    $statusClass = "error";
                }
            }
            
            echo "<tr>";
            echo "<td>$tableName</td>";
            echo "<td>$rowCount</td>";
            echo "<td>$createTime</td>";
            echo "<td class='$statusClass'>$status</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No tables found in the database.</p>";
    }
} else {
    echo "<p class='error'>Error listing tables: " . mysqli_error($conn) . "</p>";
}

// Appointments table structure check
echo "<h2>Appointments Table Structure</h2>";
$describeQuery = "DESCRIBE appointments";
$describeResult = mysqli_query($conn, $describeQuery);

if ($describeResult) {
    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = mysqli_fetch_assoc($describeResult)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p class='warning'>Appointments table does not exist.</p>";
}

// Appointment data sample
echo "<h2>Appointment Data Sample</h2>";
$sampleQuery = "SELECT * FROM appointments LIMIT 5";
$sampleResult = mysqli_query($conn, $sampleQuery);

if ($sampleResult && mysqli_num_rows($sampleResult) > 0) {
    echo "<table>";
    
    // Get field names
    $fields = mysqli_fetch_fields($sampleResult);
    echo "<tr>";
    foreach ($fields as $field) {
        echo "<th>{$field->name}</th>";
    }
    echo "</tr>";
    
    // Get data
    while ($row = mysqli_fetch_assoc($sampleResult)) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No appointment data found or table does not exist.</p>";
}

// Admin actions
echo "<h2>Admin Actions</h2>";
echo "<div>";
echo "<a href='?action=recreate_appointments' class='button danger' onclick=\"return confirm('Are you sure? This will delete all appointment data.');\">Recreate Appointments Table</a>";
echo "<a href='?action=create_sample_appointments' class='button'>Create Sample Appointments</a>";
echo "<a href='../../HTML/provider-dashboard.html' class='button'>Go to Provider Dashboard</a>";
echo "</div>";

// HTML footer
echo '
</body>
</html>
';
?> 