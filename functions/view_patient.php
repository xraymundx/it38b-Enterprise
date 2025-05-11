<?php
require_once '../config/config.php'; // DB connection

// Ensure 'id' is present and valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /it38b-Enterprise/index.php?page=patients&error=Invalid+patient+ID");
    exit();
}

$patientId = intval($_GET['id']);

// Fetch patient
$query = "SELECT patient_id, first_name, last_name, date_of_birth, email, phone FROM patients WHERE patient_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $patientId);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();
$stmt->close();

// Redirect if patient not found
if (!$patient) {
    header("Location: /it38b-Enterprise/index.php?page=patients&error=Patient+not+found");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Patient Details</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #e9ecef;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            box-sizing: border-box;
        }

        .container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 600px;
        }

        h1 {
            color: #343a40;
            margin-bottom: 30px;
            text-align: center;
            font-size: 2.5rem;
            font-weight: 500;
        }

        .patient-details {
            margin-bottom: 30px;
        }

        .detail-row {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #dee2e6;
        }

        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .detail-label {
            font-weight: 600;
            color: #495057;
            width: 150px;
            margin-right: 20px;
        }

        .detail-value {
            color: #212529;
            font-size: 1.1rem;
        }

        .back-link {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 8px;
            transition: background-color 0.3s ease;
            font-weight: 500;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }

        .back-link:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Patient Details</h1>
        <div class="patient-details">
            <div class="detail-row">
                <strong class="detail-label">Patient ID:</strong>
                <span class="detail-value"><?php echo htmlspecialchars($patient['patient_id']); ?></span>
            </div>
            <div class="detail-row">
                <strong class="detail-label">First Name:</strong>
                <span class="detail-value"><?php echo htmlspecialchars($patient['first_name']); ?></span>
            </div>
            <div class="detail-row">
                <strong class="detail-label">Last Name:</strong>
                <span class="detail-value"><?php echo htmlspecialchars($patient['last_name']); ?></span>
            </div>
            <div class="detail-row">
                <strong class="detail-label">Date of Birth:</strong>
                <span
                    class="detail-value"><?php echo htmlspecialchars(date("F j, Y", strtotime($patient['date_of_birth']))); ?></span>
            </div>
            <div class="detail-row">
                <strong class="detail-label">Email:</strong>
                <span class="detail-value"><?php echo htmlspecialchars($patient['email']); ?></span>
            </div>
            <div class="detail-row">
                <strong class="detail-label">Phone:</strong>
                <span class="detail-value"><?php echo htmlspecialchars($patient['phone']); ?></span>
            </div>
        </div>
        <a href="/it38b-Enterprise/index.php?page=patients" class="back-link">Back to Patients</a>
    </div>
</body>

</html>
<?php
$conn->close();
?>