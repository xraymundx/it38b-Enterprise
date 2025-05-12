<?php
session_start();

require '../config/config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: /login.php');
    exit;
}

switch ($_SESSION['role']) {
    case 'nurse':
        include('../views/nurse/index.php');
        break;
    case 'patient':
        include('../views/patient/index.php');

        break;
    case 'doctor':
        include('../views/doctor/index.php');
        break;
    case 'administrator':
        include('../views/admin/index.php');
        break;
    default:
        echo "Unknown role.";
        break;
}