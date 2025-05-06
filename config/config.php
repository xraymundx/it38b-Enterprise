<?php

$config = [
    'database' => [
        'host' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => 'health_db',
    ],
    'app' => [
        'name' => 'Health Record System',
        'version' => '1.0',
    ],
    // Add other configurations as needed
];

// Establish the database connection
$conn = new mysqli(
    $config['database']['host'],
    $config['database']['username'],
    $config['database']['password'],
    $config['database']['database']
);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Return the connection object
return $conn;