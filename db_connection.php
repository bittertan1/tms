<?php
// Database connection function
function getDBConnection() {
    // Database configuration
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "tms";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        // Log the error
        error_log("Database Connection Failed: " . $conn->connect_error);
        
        // Throw an exception or handle the error appropriately
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Set character set to utf8mb4 for full Unicode support
    $conn->set_charset("utf8mb4");

    return $conn;
}

// Optional: Error handling wrapper for database queries
function safeQuery($conn, $query, $params = [], $types = '') {
    try {
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt;
    } catch (Exception $e) {
        // Log the error
        error_log("Database Query Error: " . $e->getMessage());
        
        // Optionally, you can choose to rethrow or handle differently
        throw $e;
    }
}