<?php
// Enable detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/config/database.php';

// Set header for better readability
header('Content-Type: text/html; charset=utf-8');

echo "<html><head><title>Debug Appointments Table</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    tr:nth-child(even) { background-color: #f9f9f9; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style></head><body>";

echo "<h1>Debug Appointments Table</h1>";

// Check if database connection is successful
echo "<h2>Database Connection</h2>";
if ($conn) {
    echo "<p class='success'>Database connection successful</p>";
} else {
    echo "<p class='error'>Database connection failed: " . mysqli_connect_error() . "</p>";
    exit;
}

// Check if appointments table exists
echo "<h2>Table Structure</h2>";
$tableCheckQuery = "SHOW TABLES LIKE 'appointments'";
$tableCheckResult = mysqli_query($conn, $tableCheckQuery);

if (!$tableCheckResult) {
    echo "<p class='error'>Error checking for table: " . mysqli_error($conn) . "</p>";
    exit;
}

if (mysqli_num_rows($tableCheckResult) == 0) {
    echo "<p class='warning'>Appointments table does not exist.</p>";
    exit;
} else {
    echo "<p class='success'>Appointments table exists!</p>";
}

// Get table structure
echo "<h3>Column Structure</h3>";
$columnsQuery = "SHOW COLUMNS FROM appointments";
$columnsResult = mysqli_query($conn, $columnsQuery);

if (!$columnsResult) {
    echo "<p class='error'>Error fetching columns: " . mysqli_error($conn) . "</p>";
} else {
    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($column = mysqli_fetch_assoc($columnsResult)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// Count appointments
echo "<h2>Appointment Data</h2>";
$countQuery = "SELECT COUNT(*) as count FROM appointments";
$countResult = mysqli_query($conn, $countQuery);

if (!$countResult) {
    echo "<p class='error'>Error counting appointments: " . mysqli_error($conn) . "</p>";
} else {
    $countRow = mysqli_fetch_assoc($countResult);
    echo "<p>Total appointments in database: <strong>" . $countRow['count'] . "</strong></p>";
}

// Check appointments for test provider
$testProviderAppointmentsQuery = "SELECT * FROM appointments WHERE provider_id = 'provider-123' OR provider_id = '1'";
$testProviderResult = mysqli_query($conn, $testProviderAppointmentsQuery);

if (!$testProviderResult) {
    echo "<p class='error'>Error fetching test provider appointments: " . mysqli_error($conn) . "</p>";
} else {
    $appointmentCount = mysqli_num_rows($testProviderResult);
    echo "<p>Found <strong>" . $appointmentCount . "</strong> appointments for test provider.</p>";
    
    if ($appointmentCount > 0) {
        echo "<h3>Test Provider Appointments</h3>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Provider ID</th><th>Provider Name</th><th>Client Name</th>";
        echo "<th>Date</th><th>Time</th><th>Type</th><th>Status</th></tr>";
        
        while ($row = mysqli_fetch_assoc($testProviderResult)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['provider_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['provider_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['client_name']) . "</td>";
            
            // Handle different column naming conventions
            $date = isset($row['appointment_date']) ? $row['appointment_date'] : 
                  (isset($row['date']) ? $row['date'] : 'N/A');
            $time = isset($row['appointment_time']) ? $row['appointment_time'] : 
                  (isset($row['time']) ? $row['time'] : 'N/A');
            $type = isset($row['appointment_type']) ? $row['appointment_type'] : 
                  (isset($row['type']) ? $row['type'] : 'N/A');
            
            echo "<td>" . htmlspecialchars($date) . "</td>";
            echo "<td>" . htmlspecialchars($time) . "</td>";
            echo "<td>" . htmlspecialchars($type) . "</td>";
            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
}

// Display SQL queries that would fix common issues
echo "<h2>Potential Fixes</h2>";

echo "<h3>Create Appointments Table with Full Column Names</h3>";
echo "<pre>
CREATE TABLE IF NOT EXISTS appointments (
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
)
</pre>";

echo "<h3>Create Appointments Table with Short Column Names</h3>";
echo "<pre>
CREATE TABLE IF NOT EXISTS appointments (
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
)
</pre>";

echo "<h3>Rename Columns from Short to Full Names</h3>";
echo "<pre>
ALTER TABLE appointments 
CHANGE COLUMN date appointment_date DATE NOT NULL,
CHANGE COLUMN time appointment_time TIME NOT NULL,
CHANGE COLUMN type appointment_type VARCHAR(100) NOT NULL;
</pre>";

echo "<h3>Rename Columns from Full to Short Names</h3>";
echo "<pre>
ALTER TABLE appointments 
CHANGE COLUMN appointment_date date DATE NOT NULL,
CHANGE COLUMN appointment_time time TIME NOT NULL,
CHANGE COLUMN appointment_type type VARCHAR(100) NOT NULL;
</pre>";

echo "<p><a href='../HTML/provider-dashboard.html' style='display: inline-block; padding: 10px 15px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>Go to Provider Dashboard</a></p>";

echo "</body></html>";

// Close connection
mysqli_close($conn);
?> 