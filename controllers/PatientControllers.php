<?php

require 'models/PatientModel.php';

class PatientController {
    private $patientModel;

    public function __construct() {
        $this->patientModel = new PatientModel();
    }

    public function listPatients() {
        if (hasPermission($GLOBALS['currentUserRole'], 'view_patient')) {
            $patients = $this->patientModel->getAll();
            require 'views/patient_list.php';
        } else {
            echo "Access denied. You do not have permission to view patients.";
        }
    }

    public function addPatientForm() {
        if (hasPermission($GLOBALS['currentUserRole'], 'add_patient')) {
            require 'views/patient_add.php';
        } else {
            echo "Access denied. You do not have permission to add patients.";
        }
    }

    public function storePatient($data) {
        if (hasPermission($GLOBALS['currentUserRole'], 'add_patient')) {
            $patientId = $this->patientModel->addPatient($data);
            if ($patientId) {
                header('Location: index.php?action=list');
                exit();
            } else {
                echo "Error adding patient.";
            }
        } else {
            echo "Access denied. You do not have permission to add patients.";
        }
    }

    public function viewPatientDetails($id) {
        if (hasPermission($GLOBALS['currentUserRole'], 'view_patient')) {
            $patient = $this->patientModel->getPatient($id);
            if ($patient) {
                require 'views/patient_details.php';
            } else {
                echo "Patient not found.";
            }
        } else {
            echo "Access denied. You do not have permission to view patient details.";
        }
    }
}