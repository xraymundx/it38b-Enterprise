<?php

class PatientModel {
    private $db;
    private $config;

    public function __construct() {
        $this->config = require 'config/config.php';
        $this->db = new mysqli(
            $this->config['database']['host'],
            $this->config['database']['username'],
            $this->config['database']['password'],
            $this->config['database']['database']
        );

        if ($this->db->connect_error) {
            die("Database connection failed: " . $this->db->connect_error);
        }
    }

    public function getAll() {
        $sql = "SELECT * FROM patients";
        $result = $this->db->query($sql);
        $patients = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $patients[] = $row;
            }
        }
        $result->free();
        return $patients;
    }

    public function getPatient($id) {
        $sql = "SELECT * FROM patients WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $patient = $result->fetch_assoc();
        $stmt->close();
        return $patient;
    }

    public function addPatient($data) {
        $sql = "INSERT INTO patients (name, birth_date, medical_history) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sss", $data['name'], $data['birth_date'], $data['medical_history']);
        if ($stmt->execute()) {
            $patientId = $this->db->insert_id;
            $stmt->close();
            return $patientId;
        } else {
            $stmt->close();
            return false;
        }
    }
}