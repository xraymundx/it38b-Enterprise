<?php

require_once 'models/Patient.php';

class NurseController
{
    private $patientModel;

    public function __construct()
    {
        session_start();
        if (!isset($_SESSION['user'])) {
            header('Location: login.php');
            exit();
        }
        $this->patientModel = new Patient();
    }

    public function index()
    {
        require_once('views/nurse/dashboard.php');
    }

    public function patients()
    {
        $patients = $this->patientModel->getAllPatients();
        require_once('views/nurse/patients.php');
    }

    public function viewPatient($id)
    {
        $patient = $this->patientModel->getPatientById($id);
        if ($patient) {
            require_once('views/nurse/view_patient.php');
        } else {
            header('Location: ?page=patients&error=Patient not found');
        }
    }

    public function addPatient()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'first_name' => filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING),
                'last_name' => filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING),
                'date_of_birth' => filter_input(INPUT_POST, 'dob', FILTER_SANITIZE_STRING),
                'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
                'phone' => filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING),
                'created_at' => date('Y-m-d H:i:s'),
            ];

            if ($this->patientModel->addPatient($data)) {
                header('Location: ?page=patients&success=Patient added successfully');
            } else {
                header('Location: ?page=patients&error=Failed to add patient');
            }
        } else {
            require_once('views/nurse/add_patient.php');
        }
    }

    public function editPatient($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'first_name' => filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING),
                'last_name' => filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING),
                'date_of_birth' => filter_input(INPUT_POST, 'dob', FILTER_SANITIZE_STRING),
                'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
                'phone' => filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($this->patientModel->updatePatient($id, $data)) {
                header('Location: ?page=patients&success=Patient updated successfully');
            } else {
                header('Location: ?page=patients&error=Failed to update patient');
            }
        } else {
            $patient = $this->patientModel->getPatientById($id);
            if ($patient) {
                require_once('views/nurse/edit_patient.php');
            } else {
                header('Location: ?page=patients&error=Patient not found');
            }
        }
    }

    public function deletePatient($id)
    {
        if ($this->patientModel->deletePatient($id)) {
            header('Location: ?page=patients&success=Patient deleted successfully');
        } else {
            header('Location: ?page=patients&error=Failed to delete patient');
        }
    }
}