<?php
class PatientModel
{
    private $db;

    public function __construct()
    {
        global $conn; // Assuming your database connection is in $conn
        $this->db = $conn;
    }

    public function getTotalPatients()
    {
        $query = "SELECT COUNT(patient_id) AS total FROM patients";
        $result = $this->db->query($query);
        $row = $result->fetch_assoc();
        return $row['total'];
    }

    public function getPaginatedPatients($startIndex, $recordsPerPage)
    {
        $query = "SELECT patient_id, first_name, last_name, date_of_birth, email, phone FROM patients ORDER BY last_name, first_name LIMIT ?, ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $startIndex, $recordsPerPage);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // You would add other methods here for adding, editing, deleting patients
}
?>