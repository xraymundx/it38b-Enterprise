<?php

require 'config/roles.php'; // Include the roles and permissions

$request = $_GET['action'] ?? 'list';
$patientId = $_GET['id'] ?? null;

require 'controllers/PatientController.php';

$controller = new PatientController();

switch ($request) {
    case 'add':
        $controller->addPatientForm();
        break;
    case 'store':
        $controller->storePatient($_POST);
        break;
    case 'view':
        if ($patientId !== null) {
            $controller->viewPatientDetails($patientId);
        } else {
            echo "Invalid request.";
        }
        break;
    case 'list':
    default:
        $controller->listPatients();
        break;
}