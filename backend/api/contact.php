<?php
// Include initialization file
require_once __DIR__ . '/../inc/init.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(['error' => 'Method not allowed'], 405);
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST; // Fallback to $_POST if JSON is not available
}

// Validate required fields
$required_fields = ['name', 'email', 'subject', 'message'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        send_json_response(['error' => ucfirst($field) . ' is required'], 400);
    }
}

// Sanitize input
$name = sanitize_input($data['name']);
$email = sanitize_input($data['email']);
$subject = sanitize_input($data['subject']);
$message = sanitize_input($data['message']);

// Insert into database
$query = "INSERT INTO contacts (name, email, subject, message) 
          VALUES ('$name', '$email', '$subject', '$message')";

if (mysqli_query($conn, $query)) {
    // Optional: Send email notification to admin
    // mail('admin@legalconnect.com', 'New Contact Form Submission', $message, "From: $email");
    
    send_json_response(['success' => true, 'message' => 'Your message has been sent successfully. We will get back to you soon.']);
} else {
    send_json_response(['error' => 'Failed to send message: ' . mysqli_error($conn)], 500);
} 