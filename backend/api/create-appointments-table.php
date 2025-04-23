<?php
// Include initialization file
require_once __DIR__ . '/../inc/init.php';

// Set content type to HTML for readable output
header("Content-Type: text/html");

echo "<h1>Creating Appointments Table</h1>";

// SQL to create the appointments table
$sql = "CREATE TABLE IF NOT EXISTS appointments (
    id VARCHAR(50) PRIMARY KEY,
    provider_id VARCHAR(50) NOT NULL,
    provider_name VARCHAR(100) NOT NULL,
    client_id VARCHAR(50) NOT NULL,
    client_name VARCHAR(100) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    appointment_type VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('pending', 'approved', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_provider_id (provider_id),
    INDEX idx_client_id (client_id),
    INDEX idx_status (status),
    INDEX idx_date (appointment_date)
)";

// Execute query
if (mysqli_query($conn, $sql)) {
    echo "<p style='color: green;'>Appointments table created successfully or already exists.</p>";
} else {
    echo "<p style='color: red;'>Error creating appointments table: " . mysqli_error($conn) . "</p>";
}

// Create a sample appointment for testing
echo "<h2>Creating Sample Appointment</h2>";

// Check if we already have appointments
$checkQuery = "SELECT COUNT(*) as count FROM appointments";
$result = mysqli_query($conn, $checkQuery);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $count = $row['count'];
    
    if ($count > 0) {
        echo "<p>There are already $count appointments in the database. No need to add sample data.</p>";
    } else {
        // Insert a sample appointment
        $today = date('Y-m-d');
        $sampleSql = "INSERT INTO appointments (
            id, provider_id, provider_name, client_id, client_name, 
            appointment_date, appointment_time, appointment_type, description, status
        ) VALUES (
            'app_sample_" . uniqid() . "', 
            'provider-123', 
            'Dr. Jane Smith', 
            'client-456', 
            'John Davis', 
            '$today', 
            '14:30:00', 
            'Initial Consultation', 
            'Need assistance with reviewing a business contract for my startup.', 
            'pending'
        )";
        
        if (mysqli_query($conn, $sampleSql)) {
            echo "<p style='color: green;'>Sample appointment created successfully.</p>";
        } else {
            echo "<p style='color: red;'>Error creating sample appointment: " . mysqli_error($conn) . "</p>";
        }
    }
} else {
    echo "<p style='color: red;'>Error checking existing appointments: " . mysqli_error($conn) . "</p>";
}

// Show all appointments
echo "<h2>Current Appointments</h2>";

$query = "SELECT * FROM appointments";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr>";
    echo "<th>ID</th>";
    echo "<th>Provider</th>";
    echo "<th>Client</th>";
    echo "<th>Date</th>";
    echo "<th>Time</th>";
    echo "<th>Type</th>";
    echo "<th>Status</th>";
    echo "</tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['provider_name'] . " (" . $row['provider_id'] . ")</td>";
        echo "<td>" . $row['client_name'] . " (" . $row['client_id'] . ")</td>";
        echo "<td>" . $row['appointment_date'] . "</td>";
        echo "<td>" . $row['appointment_time'] . "</td>";
        echo "<td>" . $row['appointment_type'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No appointments found in the database.</p>";
}

echo "<p><a href='javascript:history.back()'>Go Back</a></p>"; 