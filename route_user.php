<?php
session_start();

// Include the database configuration
require_once 'config/config.php';

// Include the User model
require_once 'models/User.php';

// Include the Role model
require_once 'models/Role.php';

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    try {
        $user = User::fetchUser($conn, $userId);

        if ($user) {
            $_SESSION['user'] = $user;
            $roleName = $user->getRole(); // Get the role name from the User object

            error_log("User ID: " . $userId);
            error_log("Role Name: " . $roleName);

            // Define roles and permissions (if needed)
            $roles = [
                'nurse' => [],  // Define these based on your actual permissions
                'patient' => [],
                'doctor' => [],
                'administrator' => [],
            ];
            $permissions = isset($roles[$roleName]) ? $roles[$roleName] : [];

            // Redirect based on the role
            switch ($roleName) {
                case 'nurse':
                    include('views/nurse/index.php');
                    break;
                case 'patient':
                    include('views/patient/index.php');
                    break;
                case 'doctor':
                    include('views/doctor/index.php');
                    break;
                case 'administrator':
                    include('views/admin/index.php');
                    break;
                default:
                    echo "Invalid user role: " . $roleName; // Debugging message
                    break;
            }
        } else {
            echo "User not found.";
        }
    } catch (mysqli_sql_exception $e) {
        echo "Database error: " . $e->getMessage();
    }
} else {
    header('Location: login.php');
    exit;
}
?>