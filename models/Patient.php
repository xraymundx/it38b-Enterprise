<?php

// Assuming your database connection is in 'config/config.php'
require_once __DIR__ . '/../config/config.php';

class Patient
{
    private $conn;

    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
    }

    public function getAllPatients()
    {
        $sql = "SELECT p.patient_id, u.first_name, u.last_name, p.date_of_birth, u.email, u.phone_number, 
                pd.medical_record_number, pd.insurance_provider, pd.insurance_policy_number
                FROM patients p
                JOIN users u ON p.user_id = u.user_id
                LEFT JOIN patient_descriptions pd ON p.patient_id = pd.patient_id
                ORDER BY u.last_name, u.first_name";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getPatientById($id)
    {
        $sql = "SELECT p.patient_id, u.first_name, u.last_name, p.date_of_birth, u.email, u.phone_number,
                pd.description, pd.address, pd.gender, pd.medical_record_number, pd.insurance_provider,
                pd.insurance_policy_number, pd.emergency_contact_name, pd.emergency_contact_phone, pd.notes
                FROM patients p
                JOIN users u ON p.user_id = u.user_id
                LEFT JOIN patient_descriptions pd ON p.patient_id = pd.patient_id
                WHERE p.patient_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function addPatient(array $data)
    {
        $this->conn->begin_transaction();
        try {
            // First insert into users table
            $userSql = "INSERT INTO users (username, email, phone_number, password_hash, first_name, last_name, role_id) 
                       VALUES (?, ?, ?, ?, ?, ?, ?)";
            $userStmt = $this->conn->prepare($userSql);
            $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $role_id = 4; // Assuming 4 is the role_id for patients
            $userStmt->bind_param(
                "ssssssi",
                $data['username'],
                $data['email'],
                $data['phone'],
                $password_hash,
                $data['first_name'],
                $data['last_name'],
                $role_id
            );
            $userStmt->execute();
            $user_id = $this->conn->insert_id;

            // Then insert into patients table
            $patientSql = "INSERT INTO patients (user_id, date_of_birth, gender) VALUES (?, ?, ?)";
            $patientStmt = $this->conn->prepare($patientSql);
            $patientStmt->bind_param("iss", $user_id, $data['date_of_birth'], $data['gender']);
            $patientStmt->execute();
            $patient_id = $this->conn->insert_id;

            // Finally insert into patient_descriptions table
            $descSql = "INSERT INTO patient_descriptions (patient_id, description, address, medical_record_number, 
                       insurance_provider, insurance_policy_number, emergency_contact_name, emergency_contact_phone, notes) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $descStmt = $this->conn->prepare($descSql);
            $descStmt->bind_param(
                "issssssss",
                $patient_id,
                $data['description'] ?? null,
                $data['address'] ?? null,
                $data['medical_record_number'] ?? null,
                $data['insurance_provider'] ?? null,
                $data['insurance_policy_number'] ?? null,
                $data['emergency_contact_name'] ?? null,
                $data['emergency_contact_phone'] ?? null,
                $data['notes'] ?? null
            );
            $descStmt->execute();

            $this->conn->commit();
            return $patient_id;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }

    public function updatePatient($id, array $data)
    {
        $this->conn->begin_transaction();
        try {
            // Update users table
            $userSql = "UPDATE users u 
                       JOIN patients p ON u.user_id = p.user_id 
                       SET u.first_name = ?, u.last_name = ?, u.email = ?, u.phone_number = ? 
                       WHERE p.patient_id = ?";
            $userStmt = $this->conn->prepare($userSql);
            $userStmt->bind_param(
                "ssssi",
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                $data['phone'],
                $id
            );
            $userStmt->execute();

            // Update patients table
            $patientSql = "UPDATE patients SET date_of_birth = ?, gender = ? WHERE patient_id = ?";
            $patientStmt = $this->conn->prepare($patientSql);
            $patientStmt->bind_param("ssi", $data['date_of_birth'], $data['gender'], $id);
            $patientStmt->execute();

            // Update patient_descriptions table
            $descSql = "UPDATE patient_descriptions 
                       SET description = ?, address = ?, medical_record_number = ?, 
                           insurance_provider = ?, insurance_policy_number = ?, 
                           emergency_contact_name = ?, emergency_contact_phone = ?, notes = ? 
                       WHERE patient_id = ?";
            $descStmt = $this->conn->prepare($descSql);
            $descStmt->bind_param(
                "ssssssssi",
                $data['description'] ?? null,
                $data['address'] ?? null,
                $data['medical_record_number'] ?? null,
                $data['insurance_provider'] ?? null,
                $data['insurance_policy_number'] ?? null,
                $data['emergency_contact_name'] ?? null,
                $data['emergency_contact_phone'] ?? null,
                $data['notes'] ?? null,
                $id
            );
            $descStmt->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }

    public function deletePatient($id)
    {
        // The ON DELETE CASCADE in the foreign keys will handle the deletion of related records
        $sql = "DELETE FROM patients WHERE patient_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}