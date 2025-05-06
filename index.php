<?php
session_start();
require_once('models/User.php');
require_once('controllers/NurseController.php');
require_once('controllers/AdminController.php');
require_once('controllers/DoctorController.php');
require_once('controllers/PatientController.php');

// Simulate user login for testing purposes (replace with actual authentication logic)
$user = new User('nurse'); // Change 'nurse' to 'admin', 'doctor', or 'patient' for testing
$_SESSION['user'] = $user;

// Check if a user is logged in
if (!isset($_SESSION['user']) || !($_SESSION['user'] instanceof User)) {
    echo "No user logged in.";
    exit();
}

// Get the logged-in user's role
$user = $_SESSION['user'];
$role = $user->getRole(); // Assuming `getRole()` returns 'admin', 'nurse', 'doctor', or 'patient'

// Get the requested page and action from the URL
$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? 'index';
$id = $_GET['id'] ?? null;

// Route the request based on the user's role
switch ($role) {
    case 'admin':
        $adminController = new AdminController();
        switch ($page) {
            case 'users':
                if ($action === 'add') {
                    $adminController->addUser();
                } elseif ($action === 'edit' && $id) {
                    $adminController->editUser($id);
                } elseif ($action === 'delete' && $id) {
                    $adminController->deleteUser($id);
                } else {
                    $adminController->listUsers();
                }
                break;
            default:
                $adminController->dashboard();
                break;
        }
        break;

    case 'nurse':
        $nurseController = new NurseController();
        switch ($page) {
            case 'patients':
                if ($action === 'view' && $id) {
                    $nurseController->viewPatient($id);
                } elseif ($action === 'add') {
                    $nurseController->addPatient();
                } elseif ($action === 'edit' && $id) {
                    $nurseController->editPatient($id);
                } elseif ($action === 'delete' && $id) {
                    $nurseController->deletePatient($id);
                } else {
                    $nurseController->patients();
                }
                break;
            default:
                $nurseController->index();
                break;
        }
        break;

    case 'doctor':
        $doctorController = new DoctorController();
        switch ($page) {
            case 'appointments':
                if ($action === 'view' && $id) {
                    $doctorController->viewAppointment($id);
                } elseif ($action === 'add') {
                    $doctorController->addAppointment();
                } elseif ($action === 'edit' && $id) {
                    $doctorController->editAppointment($id);
                } elseif ($action === 'delete' && $id) {
                    $doctorController->deleteAppointment($id);
                } else {
                    $doctorController->listAppointments();
                }
                break;
            default:
                $doctorController->dashboard();
                break;
        }
        break;

    case 'patient':
        $patientController = new PatientController();
        switch ($page) {
            case 'profile':
                $patientController->viewProfile();
                break;
            case 'appointments':
                $patientController->listAppointments();
                break;
            default:
                $patientController->dashboard();
                break;
        }
        break;

    default:
        echo "Invalid role.";
        exit();
}
?>