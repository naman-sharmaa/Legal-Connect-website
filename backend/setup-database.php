<?php
// Include database configuration
require_once __DIR__ . '/config/database.php';

// Set content type to HTML for readable output
header("Content-Type: text/html");

echo "<h1>Database Setup</h1>";

// Check if force_recreate parameter is set
$forceRecreate = isset($_GET['force_recreate']) && $_GET['force_recreate'] === 'true';

if ($forceRecreate) {
    echo "<p><strong>Force recreate option detected - dropping tables to recreate them</strong></p>";
    
    // Drop appointments table
    $dropAppointmentsQuery = "DROP TABLE IF EXISTS appointments";
    if (mysqli_query($conn, $dropAppointmentsQuery)) {
        echo "<p>Appointments table dropped successfully.</p>";
    } else {
        echo "<p>Error dropping appointments table: " . mysqli_error($conn) . "</p>";
    }
}

// Create appointments table
$createAppointmentsTable = "CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT NOT NULL,
    provider_name VARCHAR(100) NOT NULL,
    client_id INT NOT NULL,
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

if (mysqli_query($conn, $createAppointmentsTable)) {
    echo "<p>Appointments table created successfully or already exists.</p>";
} else {
    echo "<p>Error creating appointments table: " . mysqli_error($conn) . "</p>";
    exit;
}

// Check if we need to add sample users
$userCountQuery = "SELECT COUNT(*) as count FROM users";
$userCountResult = mysqli_query($conn, $userCountQuery);

if ($userCountResult) {
    $userCountRow = mysqli_fetch_assoc($userCountResult);
    $userCount = $userCountRow['count'];
    
    echo "<p>Current user count: $userCount</p>";
    
    // Add sample users if the table is empty
    if ($userCount == 0) {
        echo "<p>Adding sample users...</p>";
        
        // Create sample provider user
        $providerPassword = password_hash('provider123', PASSWORD_DEFAULT);
        
        $createProviderQuery = "INSERT INTO users (
            first_name, last_name, email, phone, password, user_type
        ) VALUES (
            'Jane', 'Smith', 'provider@example.com', '555-123-4567',
            '$providerPassword', 'provider'
        )";
        
        if (mysqli_query($conn, $createProviderQuery)) {
            $providerId = mysqli_insert_id($conn);
            echo "<p>Created sample provider with ID: $providerId</p>";
            
            // Add provider details
            $createProviderDetailsQuery = "INSERT INTO provider_details (
                user_id, provider_type, license_number, bio, services_offered
            ) VALUES (
                $providerId, 'Criminal Legal Advisor', 'LAW-789012',
                'Experienced criminal legal advisor with over 10 years in the field.',
                'Criminal law consultation, Legal defense, Court representation'
            )";
            
            if (mysqli_query($conn, $createProviderDetailsQuery)) {
                echo "<p>Created provider details successfully.</p>";
            } else {
                echo "<p>Error creating provider details: " . mysqli_error($conn) . "</p>";
            }
        } else {
            echo "<p>Error creating provider user: " . mysqli_error($conn) . "</p>";
        }
        
        // Create sample client user
        $clientPassword = password_hash('client123', PASSWORD_DEFAULT);
        
        $createClientQuery = "INSERT INTO users (
            first_name, last_name, email, phone, password, user_type
        ) VALUES (
            'John', 'Doe', 'client@example.com', '555-987-6543',
            '$clientPassword', 'user'
        )";
        
        if (mysqli_query($conn, $createClientQuery)) {
            $clientId = mysqli_insert_id($conn);
            echo "<p>Created sample client with ID: $clientId</p>";
        } else {
            echo "<p>Error creating client user: " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p>Sample users not needed - users already exist.</p>";
    }
}

// Check current appointments
$appointmentsQuery = "SELECT * FROM appointments LIMIT 10";
$appointmentsResult = mysqli_query($conn, $appointmentsQuery);

echo "<h2>Current Appointments</h2>";

if ($appointmentsResult && mysqli_num_rows($appointmentsResult) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Provider</th><th>Client</th><th>Date</th><th>Time</th><th>Type</th><th>Status</th></tr>";
    
    while ($row = mysqli_fetch_assoc($appointmentsResult)) {
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

// Display table structure
$describeQuery = "DESCRIBE appointments";
$describeResult = mysqli_query($conn, $describeQuery);

echo "<h2>Appointments Table Structure</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($row = mysqli_fetch_assoc($describeResult)) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<p><b>Database setup completed at " . date('Y-m-d H:i:s') . "</b></p>";
echo "<p><a href='../HTML/appointment.html'>Go to Appointment Page</a> | <a href='../HTML/provider-dashboard.html'>Go to Provider Dashboard</a></p>";
?> 