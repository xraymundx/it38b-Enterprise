<?php
session_start();

// Include the roles configuration and the User class
require_once('config/roles.php');
require_once('models/user.php');

// If user is not logged in, redirect to guest page
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: /it38b-Enterprise/guest.php');
    exit;
}

// Simulate the nurse user by hardcoding user ID and role for testing
$userId = isset($_SESSION['userId']) ? $_SESSION['userId'] : 1; // Default to 1 for testing, simulate nurse

// Simulate getting the user from the database based on userId
// In a real scenario, this would be fetched from the database
$user = User::fetchUser($userId);

// Store the user in the session
$_SESSION['user'] = $user;

// If the session has a user, proceed with role-based routing
if (isset($_SESSION['user'])) {
    $user = $_SESSION['user'];

    // Get the user's role
    $role = $user->getRole();

    // Simulate permissions for the logged-in user
    $permissions = isset($roles[$role]) ? $roles[$role] : [];

    // Route based on the role
    switch ($role) {
        case 'nurse':
            include('views/nurse/index.php');
            break;
        case 'doctor':
            include('views/doctor/index.php');
            break;
        case 'administrator':
            include('views/admin/index.php');
            break;
        case 'guest':
            header('Location: /it38b-Enterprise/guest.php');
            exit;
        default:
            echo "Invalid user role.";
            break;
    }
} else {
    // Redirect to guest page if no user in session
    header('Location: /it38b-Enterprise/guest.php');
    exit;
}
?>