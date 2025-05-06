<?php

// Assuming your Patient model is in 'models/Patient.php'
require_once 'models/Patient.php';

class PatientController
{
    private $patientModel;

    public function __construct()
    {
        $this->patientModel = new Patient();
    }

    public function index()
    {
        $patients = $this->patientModel->getAllPatients();
        // Assuming your view is in 'views/patients/index.php'
        require 'views/patients/index.php';
    }

    public function create()
    {
        // Assuming your view is in 'views/patients/create.php'
        require 'views/patients/create.php';
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $firstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
            $lastName = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
            $dob = filter_input(INPUT_POST, 'dob', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);

            $errors = [];
            if (empty($firstName))
                $errors['first_name'] = 'First name is required.';
            if (empty($lastName))
                $errors['last_name'] = 'Last name is required.';
            // Add more validation

            if (empty($errors)) {
                $data = [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'date_of_birth' => $dob,
                    'email' => $email,
                    'phone' => $phone,
                    'created_at' => date('Y-m-d H:i:s'),
                ];

                $patientId = $this->patientModel->addPatient($data);

                if ($patientId) {
                    header('Location: ?page=patients&action=index&success=Patient added successfully');
                    exit();
                } else {
                    $errorMessage = 'Error adding patient.';
                    require 'views/patients/create.php';
                }
            } else {
                require 'views/patients/create.php'; // You'll need to pass $errors to the view
            }
        } else {
            header('HTTP/1.0 405 Method Not Allowed');
            echo 'Method Not Allowed';
        }
    }

    public function show($id)
    {
        $patient = $this->patientModel->getPatientById($id);
        if ($patient) {
            // Assuming your view is in 'views/patients/show.php'
            require 'views/patients/show.php';
        } else {
            header('Location: ?page=patients&action=index&error=Patient not found');
            exit();
        }
    }

    public function edit($id)
    {
        $patient = $this->patientModel->getPatientById($id);
        if ($patient) {
            // Assuming your view is in 'views/patients/edit.php'
            require 'views/patients/edit.php';
        } else {
            header('Location: ?page=patients&action=index&error=Patient not found');
            exit();
        }
    }

    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $firstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
            $lastName = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
            $dob = filter_input(INPUT_POST, 'dob', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);

            $errors = [];
            if (empty($firstName))
                $errors['first_name'] = 'First name is required.';
            if (empty($lastName))
                $errors['last_name'] = 'Last name is required.';
            // Add more validation

            if (empty($errors)) {
                $data = [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'date_of_birth' => $dob,
                    'email' => $email,
                    'phone' => $phone,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                $updated = $this->patientModel->updatePatient($id, $data);

                if ($updated) {
                    header('Location: ?page=patients&action=index&success=Patient updated successfully');
                    exit();
                } else {
                    $errorMessage = 'Error updating patient.';
                    $patient = $this->patientModel->getPatientById($id);
                    require 'views/patients/edit.php'; // You'll need to pass $errors and $patient
                }
            } else {
                $patient = $this->patientModel->getPatientById($id);
                require 'views/patients/edit.php'; // You'll need to pass $errors and $patient
            }
        } else {
            header('HTTP/1.0 405 Method Not Allowed');
            echo 'Method Not Allowed';
        }
    }

    public function destroy($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $deleted = $this->patientModel->deletePatient($id);

            if ($deleted) {
                header('Location: ?page=patients&action=index&success=Patient deleted successfully');
                exit();
            } else {
                header('Location: ?page=patients&action=index&error=Error deleting patient');
                exit();
            }
        } else {
            header('HTTP/1.0 405 Method Not Allowed');
            echo 'Method Not Allowed';
        }
    }
}