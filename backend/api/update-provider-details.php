<?php
// Include initialization file
require_once __DIR__ . '/../inc/init.php';

// Set provider ID for Varun
$providerId = 2;

// Update provider details with better information
$bio = "Experienced advocate with expertise in civil and corporate law. Providing legal consultation and representation since 2015.";
$services_offered = "Civil Cases, Corporate Law, Legal Documentation, Contract Review";

// Update the provider_details table
$query = "UPDATE provider_details SET 
            bio = '$bio', 
            services_offered = '$services_offered' 
          WHERE user_id = '$providerId'";

// Execute the query
if (mysqli_query($conn, $query)) {
    echo "Provider details updated successfully.";
} else {
    echo "Error updating provider details: " . mysqli_error($conn);
} 