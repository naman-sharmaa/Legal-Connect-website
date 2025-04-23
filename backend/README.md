# LegalConnect Backend

This is the PHP backend for the LegalConnect platform. It provides API endpoints for user authentication, registration, profile management, and contact functionality.

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache or Nginx web server
- XAMPP, WAMP, MAMP, or similar local development environment

## Setup Instructions

1. **Database Setup**:
   - Create a MySQL database named `legalconnect` 
   - Import the schema from `backend/config/schema.sql`

2. **Configuration**:
   - Update database connection details in `backend/config/database.php` if needed
   - Default configuration:
     - Host: localhost
     - Username: root
     - Password: (empty)
     - Database: legalconnect

3. **Web Server Configuration**:
   - Ensure your web server is properly configured to serve PHP files
   - The project root should be the directory containing both `backend` and `HTML` folders

## API Endpoints

### Authentication
- **POST /backend/api/register.php** - Register a new user or provider
- **POST /backend/api/login.php** - User login
- **POST /backend/api/logout.php** - User logout
- **GET /backend/api/check-auth.php** - Check authentication status

### User Profile
- **GET /backend/api/profile.php** - Get user profile information

### Contact
- **POST /backend/api/contact.php** - Submit contact form

## Security Notes

For a production environment, consider:
- Enabling HTTPS
- Implementing rate limiting
- Adding more robust input validation
- Using prepared statements for all database queries
- Setting proper CORS headers
- Implementing token expiration and refresh

## Troubleshooting

1. **Database Connection Issues**:
   - Verify database credentials
   - Ensure MySQL service is running
   - Check if `legalconnect` database exists

2. **Permission Issues**:
   - Ensure web server has read/write permissions for the backend directory

3. **API Returns 500 Error**:
   - Check PHP error logs
   - Verify database tables are correctly created
   - Ensure all required PHP extensions are enabled (mysqli, json) 