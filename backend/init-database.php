<?php
// Enable detailed error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include initialization file
require_once __DIR__ . '/inc/init.php';

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html");

echo "<h1>Database Initialization</h1>";

// Check for force recreate parameter
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
    
    // Drop other related tables if needed
    // ...
}

// Create users table if it doesn't exist
$createUsersTableQuery = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(50),
    password VARCHAR(255) NOT NULL,
    user_type ENUM('user', 'provider', 'admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (email),
    INDEX (user_type)
)";

if (mysqli_query($conn, $createUsersTableQuery)) {
    echo "<p>Users table created or already exists.</p>";
} else {
    echo "<p>Error creating users table: " . mysqli_error($conn) . "</p>";
    exit;
}

// Create provider_details table if it doesn't exist
$createProviderDetailsTableQuery = "CREATE TABLE IF NOT EXISTS provider_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    provider_type VARCHAR(100) NOT NULL,
    license_number VARCHAR(100) NOT NULL,
    bio TEXT,
    services_offered TEXT,
    available_hours TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $createProviderDetailsTableQuery)) {
    echo "<p>Provider details table created or already exists.</p>";
} else {
    echo "<p>Error creating provider details table: " . mysqli_error($conn) . "</p>";
    exit;
}

// Create sessions table if it doesn't exist
$createSessionsTableQuery = "CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $createSessionsTableQuery)) {
    echo "<p>Sessions table created or already exists.</p>";
} else {
    echo "<p>Error creating sessions table: " . mysqli_error($conn) . "</p>";
    exit;
}

// Create appointments table if it doesn't exist
$createTableQuery = "CREATE TABLE IF NOT EXISTS appointments (
    id VARCHAR(255) PRIMARY KEY,
    provider_id VARCHAR(255) NOT NULL,
    provider_name VARCHAR(255) NOT NULL,
    client_id VARCHAR(255) NOT NULL,
    client_name VARCHAR(255) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    appointment_type VARCHAR(100) NOT NULL,
    description TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (provider_id),
    INDEX (client_id),
    INDEX (status),
    INDEX (appointment_date)
)";

if (mysqli_query($conn, $createTableQuery)) {
    echo "<p>Appointments table created or already exists.</p>";
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
        
        // Sample data - create a provider
        $providerPassword = password_hash('provider123', PASSWORD_DEFAULT); // In a real app, use a stronger password
        
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
        
        // Get the first provider user ID for appointments
        $providerQuery = "SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) AS provider_name 
                         FROM users u 
                         JOIN provider_details pd ON u.id = pd.user_id 
                         LIMIT 1";
        $providerResult = mysqli_query($conn, $providerQuery);
        
        if ($providerResult && mysqli_num_rows($providerResult) > 0) {
            $providerData = mysqli_fetch_assoc($providerResult);
            $providerId = $providerData['id'];
            $providerName = $providerData['provider_name'];
            echo "<p>Found existing provider: ID $providerId, Name: $providerName</p>";
        } else {
            echo "<p>No providers found in the database.</p>";
            $providerId = null;
        }
        
        // Get the first client user ID for appointments
        $clientQuery = "SELECT id, CONCAT(first_name, ' ', last_name) AS client_name 
                      FROM users 
                      WHERE user_type = 'user' 
                      LIMIT 1";
        $clientResult = mysqli_query($conn, $clientQuery);
        
        if ($clientResult && mysqli_num_rows($clientResult) > 0) {
            $clientData = mysqli_fetch_assoc($clientResult);
            $clientId = $clientData['id'];
            $clientName = $clientData['client_name'];
            echo "<p>Found existing client: ID $clientId, Name: $clientName</p>";
        } else {
            echo "<p>No clients found in the database.</p>";
            $clientId = null;
        }
    }
}

// Check if we need to add sample appointments
$countQuery = "SELECT COUNT(*) as count FROM appointments";
$countResult = mysqli_query($conn, $countQuery);

if ($countResult) {
    $countRow = mysqli_fetch_assoc($countResult);
    $appointmentCount = $countRow['count'];
    
    echo "<p>Current appointment count: $appointmentCount</p>";
    
    // Add sample data if the table is empty and we have valid user IDs
    if ($appointmentCount == 0 && isset($providerId) && isset($clientId)) {
        echo "<p>Adding sample appointments...</p>";
        
        // Get provider name if not already set
        if (!isset($providerName)) {
            $providerQuery = "SELECT CONCAT(first_name, ' ', last_name) AS provider_name 
                             FROM users WHERE id = $providerId";
            $providerResult = mysqli_query($conn, $providerQuery);
            if ($providerResult && mysqli_num_rows($providerResult) > 0) {
                $providerData = mysqli_fetch_assoc($providerResult);
                $providerName = $providerData['provider_name'];
            } else {
                $providerName = "Unknown Provider";
            }
        }
        
        // Get client name if not already set
        if (!isset($clientName)) {
            $clientQuery = "SELECT CONCAT(first_name, ' ', last_name) AS client_name 
                           FROM users WHERE id = $clientId";
            $clientResult = mysqli_query($conn, $clientQuery);
            if ($clientResult && mysqli_num_rows($clientResult) > 0) {
                $clientData = mysqli_fetch_assoc($clientResult);
                $clientName = $clientData['client_name'];
            } else {
                $clientName = "Unknown Client";
            }
        }
        
        // Sample data
        $sampleAppointments = [
            [
                'id' => 'app_' . uniqid(),
                'provider_id' => $providerId,
                'provider_name' => $providerName,
                'client_id' => $clientId,
                'client_name' => $clientName,
                'date' => date('Y-m-d', strtotime('+2 days')),
                'time' => '14:30:00',
                'type' => 'Initial Consultation',
                'description' => 'Need legal advice for a corporate matter.',
                'status' => 'pending'
            ],
            [
                'id' => 'app_' . uniqid(),
                'provider_id' => $providerId,
                'provider_name' => $providerName,
                'client_id' => $clientId,
                'client_name' => $clientName,
                'date' => date('Y-m-d', strtotime('+1 day')),
                'time' => '10:15:00',
                'type' => 'Follow-up Meeting',
                'description' => 'Discussing progress on my family law case.',
                'status' => 'approved'
            ],
            [
                'id' => 'app_' . uniqid(),
                'provider_id' => $providerId,
                'provider_name' => $providerName,
                'client_id' => $clientId,
                'client_name' => $clientName,
                'date' => date('Y-m-d', strtotime('-1 day')),
                'time' => '15:00:00',
                'type' => 'Urgent Consultation',
                'description' => 'Need immediate help with an immigration issue.',
                'status' => 'completed'
            ]
        ];
        
        // Insert sample appointments
        $insertCount = 0;
        foreach ($sampleAppointments as $appointment) {
            $query = "INSERT INTO appointments (
                id, provider_id, provider_name, client_id, client_name, 
                appointment_date, appointment_time, appointment_type, description, status
              ) VALUES (
                '{$appointment['id']}', 
                '{$appointment['provider_id']}', 
                '{$appointment['provider_name']}', 
                '{$appointment['client_id']}', 
                '{$appointment['client_name']}', 
                '{$appointment['date']}', 
                '{$appointment['time']}', 
                '{$appointment['type']}', 
                '{$appointment['description']}', 
                '{$appointment['status']}'
              )";
            
            echo "<p>Executing query: " . htmlspecialchars($query) . "</p>";
            
            if (mysqli_query($conn, $query)) {
                $insertCount++;
                echo "<p>Successfully inserted appointment ID: {$appointment['id']}</p>";
            } else {
                echo "<p>Error inserting sample appointment: " . mysqli_error($conn) . "</p>";
            }
        }
        
        echo "<p>Added $insertCount sample appointments.</p>";
    } else {
        echo "<p>Sample data not needed - appointments already exist.</p>";
    }
    
    // Display current appointments
    $listQuery = "SELECT id, provider_name, client_name, appointment_date, appointment_time, status FROM appointments ORDER BY appointment_date, appointment_time LIMIT 10";
    $listResult = mysqli_query($conn, $listQuery);
    
    if ($listResult && mysqli_num_rows($listResult) > 0) {
        echo "<h2>Current Appointments</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Provider</th><th>Client</th><th>Date/Time</th><th>Status</th></tr>";
        
        while ($row = mysqli_fetch_assoc($listResult)) {
            echo "<tr>";
            echo "<td>" . substr($row['id'], 0, 10) . "...</td>";
            echo "<td>" . htmlspecialchars($row['provider_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['client_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['appointment_date'] . ' ' . $row['appointment_time']) . "</td>";
            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
}

echo "<p><b>Database initialization completed at " . date('Y-m-d H:i:s') . "</b></p>";
?> 