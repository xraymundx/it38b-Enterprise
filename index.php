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

$user = User::fetchUser($userId);

// Store the user in the session
$_SESSION['user'] = $user;

// If the session has a user, proceed with role-based routing
if (isset($_SESSION['user'])) {
    $user = $_SESSION['user'];


    $role = $user->getRole();


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