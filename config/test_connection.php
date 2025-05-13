<?php
// Set error reporting to show all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the config file but don't capture the returned value yet
include_once __DIR__ . '/config.php';

// Get the database configuration
$config = [
    'database' => [
        'host' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => 'health_db',
    ]
];

// Try to connect to the database directly
echo "<h1>Testing Database Connection</h1>";

try {
    // Create a new connection
    $test_conn = new mysqli(
        $config['database']['host'],
        $config['database']['username'],
        $config['database']['password'],
        $config['database']['database']
    );

    // Check for connection errors
    if ($test_conn->connect_error) {
        echo "<p style='color:red'>Connection failed: " . $test_conn->connect_error . "</p>";
    } else {
        echo "<p style='color:green'>Direct connection successful!</p>";

        // Check if we can query the database
        $result = $test_conn->query("SHOW TABLES");
        if ($result) {
            echo "<p>Tables in the database:</p>";
            echo "<ul>";
            while ($row = $result->fetch_array()) {
                echo "<li>" . $row[0] . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color:red'>Failed to query database: " . $test_conn->error . "</p>";
        }

        $test_conn->close();
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Exception: " . $e->getMessage() . "</p>";
}

// Now test the connection from config.php
echo "<h2>Testing connection from config.php</h2>";

try {
    require __DIR__ . '/config.php';

    if (is_bool($conn)) {
        echo "<p style='color:red'>config.php returned a boolean value (" . ($conn ? 'true' : 'false') . ") instead of a connection object!</p>";
    } elseif (is_object($conn) && $conn instanceof mysqli) {
        echo "<p style='color:green'>config.php returned a valid mysqli connection object!</p>";

        // Check if we can query using this connection
        $result = $conn->query("SHOW TABLES");
        if ($result) {
            echo "<p>Connection works for queries!</p>";
            $conn->close();
        } else {
            echo "<p style='color:red'>Connection object exists but cannot query: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color:red'>config.php returned: " . gettype($conn) . " - " . print_r($conn, true) . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Exception when including config.php: " . $e->getMessage() . "</p>";
}