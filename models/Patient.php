<?php

// Assuming your database connection is in 'config/config.php'
require_once 'config/config.php';

class Patient
{
    private $conn;

    public function __construct()
    {
        global $conn; // Assuming $conn is your database connection object
        $this->conn = $conn;
    }

    public function getAllPatients()
    {
        $sql = "SELECT id, first_name, last_name, date_of_birth, email, phone FROM patients ORDER BY last_name, first_name";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getPatientById($id)
    {
        $sql = "SELECT id, first_name, last_name, date_of_birth, email, phone FROM patients WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function addPatient(array $data)
    {
        $sql = "INSERT INTO patients (first_name, last_name, date_of_birth, email, phone, created_at) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssss", $data['first_name'], $data['last_name'], $data['date_of_birth'], $data['email'], $data['phone'], $data['created_at']);
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        } else {
            return false;
        }
    }

    public function updatePatient($id, array $data)
    {
        $sql = "UPDATE patients SET first_name = ?, last_name = ?, date_of_birth = ?, email = ?, phone = ?, updated_at = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssssi", $data['first_name'], $data['last_name'], $data['date_of_birth'], $data['email'], $data['phone'], $data['updated_at'], $id);
        return $stmt->execute();
    }

    public function deletePatient($id)
    {
        $sql = "DELETE FROM patients WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}