<?php
require_once __DIR__ . '/../config/config.php';


// Ensure 'id' is present and valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /it38b-Enterprise/index.php?page=patients&error=Invalid+patient+ID");
    exit();
}

$patientId = intval($_GET['id']);

// Fetch patient with all related information
$query = "SELECT p.patient_id, u.first_name, u.last_name, p.date_of_birth, u.email, u.phone_number,
          pd.description, pd.address, pd.gender, pd.medical_record_number, pd.insurance_provider,
          pd.insurance_policy_number, pd.emergency_contact_name, pd.emergency_contact_phone, pd.notes
          FROM patients p
          JOIN users u ON p.user_id = u.user_id
          LEFT JOIN patient_descriptions pd ON p.patient_id = pd.patient_id
          WHERE p.patient_id = ?";

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
            padding: 20px;
            box-sizing: border-box;
        }

        .container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 800px;
            margin: 0 auto;
        }

        h1 {
            color: #343a40;
            margin-bottom: 30px;
            text-align: center;
            font-size: 2.5rem;
            font-weight: 500;
        }

        .patient-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .detail-row {
            margin-bottom: 15px;
        }

        .detail-label {
            font-weight: 600;
            color: #495057;
            display: block;
            margin-bottom: 5px;
        }

        .detail-value {
            color: #212529;
        }

        .back-link {
            display: inline-block;
            margin-top: 30px;
            padding: 10px 20px;
            background-color: #6c5dd3;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background-color 0.3s ease;
        }

        .back-link:hover {
            background-color: #5649a8;
        }

        .section-title {
            grid-column: 1 / -1;
            color: #6c5dd3;
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 1.2em;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 5px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Patient Details</h1>
        <div class="patient-details">
            <h3 class="section-title">Basic Information</h3>
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
                <strong class="detail-label">Gender:</strong>
                <span class="detail-value"><?php echo htmlspecialchars($patient['gender'] ?? 'Not specified'); ?></span>
            </div>

            <h3 class="section-title">Contact Information</h3>
            <div class="detail-row">
                <strong class="detail-label">Email:</strong>
                <span class="detail-value"><?php echo htmlspecialchars($patient['email']); ?></span>
            </div>
            <div class="detail-row">
                <strong class="detail-label">Phone:</strong>
                <span class="detail-value"><?php echo htmlspecialchars($patient['phone_number']); ?></span>
            </div>
            <div class="detail-row">
                <strong class="detail-label">Address:</strong>
                <span
                    class="detail-value"><?php echo htmlspecialchars($patient['address'] ?? 'Not specified'); ?></span>
            </div>

            <h3 class="section-title">Medical Information</h3>
            <div class="detail-row">
                <strong class="detail-label">Medical Record Number:</strong>
                <span
                    class="detail-value"><?php echo htmlspecialchars($patient['medical_record_number'] ?? 'Not assigned'); ?></span>
            </div>
            <div class="detail-row">
                <strong class="detail-label">Description:</strong>
                <span
                    class="detail-value"><?php echo htmlspecialchars($patient['description'] ?? 'No description available'); ?></span>
            </div>

            <h3 class="section-title">Insurance Information</h3>
            <div class="detail-row">
                <strong class="detail-label">Insurance Provider:</strong>
                <span
                    class="detail-value"><?php echo htmlspecialchars($patient['insurance_provider'] ?? 'Not specified'); ?></span>
            </div>
            <div class="detail-row">
                <strong class="detail-label">Insurance Policy Number:</strong>
                <span
                    class="detail-value"><?php echo htmlspecialchars($patient['insurance_policy_number'] ?? 'Not specified'); ?></span>
            </div>

            <h3 class="section-title">Emergency Contact</h3>
            <div class="detail-row">
                <strong class="detail-label">Emergency Contact Name:</strong>
                <span
                    class="detail-value"><?php echo htmlspecialchars($patient['emergency_contact_name'] ?? 'Not specified'); ?></span>
            </div>
            <div class="detail-row">
                <strong class="detail-label">Emergency Contact Phone:</strong>
                <span
                    class="detail-value"><?php echo htmlspecialchars($patient['emergency_contact_phone'] ?? 'Not specified'); ?></span>
            </div>

            <h3 class="section-title">Additional Notes</h3>
            <div class="detail-row" style="grid-column: 1 / -1;">
                <strong class="detail-label">Notes:</strong>
                <span
                    class="detail-value"><?php echo nl2br(htmlspecialchars($patient['notes'] ?? 'No additional notes')); ?></span>
            </div>
        </div>
        <a href="/it38b-Enterprise/index.php?page=patients" class="back-link">Back to Patients</a>
    </div>
</body>

</html>
<?php
$conn->close();
?>