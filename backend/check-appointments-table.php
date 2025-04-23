<?php
// Enable detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/config/database.php';

echo "<h1>Appointments Table Check</h1>";

// Check if appointments table exists
$tableCheckQuery = "SHOW TABLES LIKE 'appointments'";
$tableCheckResult = mysqli_query($conn, $tableCheckQuery);

if (mysqli_num_rows($tableCheckResult) == 0) {
    // Table doesn't exist, create it
    echo "<p>Creating appointments table...</p>";
    
    $createTableQuery = "CREATE TABLE IF NOT EXISTS appointments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        provider_id VARCHAR(50) NOT NULL,
        provider_name VARCHAR(100) NOT NULL,
        client_id VARCHAR(50) NOT NULL,
        client_name VARCHAR(100) NOT NULL,
        appointment_date DATE NOT NULL,
        appointment_time TIME NOT NULL,
        appointment_type VARCHAR(50) DEFAULT 'Consultation',
        description TEXT,
        status ENUM('pending', 'approved', 'completed', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_provider_id (provider_id),
        INDEX idx_client_id (client_id),
        INDEX idx_status (status),
        INDEX idx_date (appointment_date)
    )";
    
    if (mysqli_query($conn, $createTableQuery)) {
        echo "<p style='color: green;'>Appointments table created successfully!</p>";
        
        // Insert sample appointments
        echo "<p>Adding sample appointments...</p>";
        
        // Sample data for appointments
        $sampleAppointments = [
            [
                'provider_id' => 'provider-123',
                'provider_name' => 'John Lawyer',
                'client_id' => 'client-789',
                'client_name' => 'Alice Johnson',
                'appointment_date' => date('Y-m-d', strtotime('+1 day')),
                'appointment_time' => '10:00:00',
                'appointment_type' => 'Criminal Consultation',
                'description' => 'Initial consultation about a traffic violation case',
                'status' => 'pending'
            ],
            [
                'provider_id' => 'provider-123',
                'provider_name' => 'John Lawyer',
                'client_id' => 'client-456',
                'client_name' => 'Bob Smith',
                'appointment_date' => date('Y-m-d', strtotime('+2 days')),
                'appointment_time' => '14:30:00',
                'appointment_type' => 'Family Law Consultation',
                'description' => 'Divorce proceedings and child custody discussion',
                'status' => 'approved'
            ],
            [
                'provider_id' => 'provider-123',
                'provider_name' => 'John Lawyer',
                'client_id' => 'client-123',
                'client_name' => 'Carol Davis',
                'appointment_date' => date('Y-m-d', strtotime('-1 day')),
                'appointment_time' => '11:00:00',
                'appointment_type' => 'Property Law',
                'description' => 'Property boundary dispute with neighbor',
                'status' => 'completed'
            ]
        ];
        
        // Insert sample appointments
        foreach ($sampleAppointments as $appointment) {
            $insertQuery = "INSERT INTO appointments (
                provider_id, provider_name, client_id, client_name, 
                appointment_date, appointment_time, appointment_type, description, status
            ) VALUES (
                '{$appointment['provider_id']}', '{$appointment['provider_name']}', 
                '{$appointment['client_id']}', '{$appointment['client_name']}', 
                '{$appointment['appointment_date']}', '{$appointment['appointment_time']}', 
                '{$appointment['appointment_type']}', '{$appointment['description']}', 
                '{$appointment['status']}'
            )";
            
            if (mysqli_query($conn, $insertQuery)) {
                echo "<p>Added appointment for client {$appointment['client_name']}</p>";
            } else {
                echo "<p style='color: red;'>Error adding appointment: " . mysqli_error($conn) . "</p>";
            }
        }
        
        echo "<p style='color: green;'>Sample appointments added successfully!</p>";
    } else {
        echo "<p style='color: red;'>Error creating appointments table: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color: green;'>Appointments table already exists!</p>";
    
    // Count appointments
    $countQuery = "SELECT COUNT(*) as count FROM appointments";
    $countResult = mysqli_query($conn, $countQuery);
    $row = mysqli_fetch_assoc($countResult);
    echo "<p>Found {$row['count']} appointments in the database.</p>";
    
    // List appointments
    echo "<h2>Current Appointments</h2>";
    $query = "SELECT * FROM appointments ORDER BY appointment_date DESC, appointment_time ASC";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
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
}

// Close database connection
mysqli_close($conn);

// Update at least one record to use 'provider-123' as provider_id
$updateQuery = "UPDATE appointments SET provider_id = 'provider-123' WHERE id IN (SELECT id FROM (SELECT id FROM appointments ORDER BY created_at DESC LIMIT 3) as temp)";
if (mysqli_query($conn, $updateQuery)) {
    echo "<p style='color: green;'>Updated provider_id to 'provider-123' for the 3 most recent appointments.</p>";
} else {
    echo "<p style='color: red;'>Error updating provider_id: " . mysqli_error($conn) . "</p>";
}

echo "<p><a href='../HTML/provider-dashboard.html'>Go to Provider Dashboard</a></p>";
?> 