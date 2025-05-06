<?php

class NurseController
{
    public function __construct()
    {
        session_start();
        if (!isset($_SESSION['user'])) {
            header('Location: login.php');
            exit();
        }
    }

    public function index()
    {
        require_once('views/nurse/dashboard.php');
    }

    public function patients()
    {
        require_once('views/nurse/patients.php');
    }

    public function appointments()
    {
        require_once('views/nurse/appointments.php');
    }

    public function medicalRecords()
    {
        require_once('views/nurse/medical_records.php');
    }

    public function billingRecords()
    {
        require_once('views/nurse/billing_records.php');
    }
}
?>