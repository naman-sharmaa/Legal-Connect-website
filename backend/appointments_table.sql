-- Drop existing table if needed
DROP TABLE IF EXISTS appointments;

-- Create appointments table
CREATE TABLE IF NOT EXISTS appointments (
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
);

-- Add foreign key constraints if users table exists
-- ALTER TABLE `appointments` 
--   ADD CONSTRAINT `appointments_provider_fk` FOREIGN KEY (`provider_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
--   ADD CONSTRAINT `appointments_client_fk` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE; 