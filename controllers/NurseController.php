<?php

class NurseController {
    public function __construct() {
        // Check if user is logged in and has a valid role
        session_start();
        if (!isset($_SESSION['user'])) {
            header('Location: login.php');  // Redirect to login if not authenticated
            exit();
        }
    }

    public function index() {
        // Load Nurse Dashboard
        require_once('views/nurse/index.php');
    }

    public function patients() {
        // Load Patients View
        require_once('views/nurse/patients.php');
    }

    public function appointments() {
        // Load Appointments View
        require_once('views/nurse/appointments.php');
    }

    public function medicalRecords() {
        // Load Medical Records View
        require_once('views/nurse/medical_records.php');
    }

    public function billingRecords() {
        // Load Billing Records View
        require_once('views/nurse/billing_records.php');
    }
}
?>
