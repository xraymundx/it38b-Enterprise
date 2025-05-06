<?php
require_once '../config/config.php'; // DB connection

// Ensure 'id' is present and valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /it38b-Enterprise/index.php?page=patients&error=Invalid+patient+ID");
    exit();
}

$patientId = intval($_GET['id']);

// Fetch patient
$query = "SELECT id, first_name, last_name, date_of_birth, email, phone FROM patients WHERE id = ?";
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
    <title>View Patient</title>
    <style>
        body {
            font-family: sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
        }

        .patient-details div {
            margin-bottom: 15px;
        }

        .patient-details strong {
            display: inline-block;
            width: 120px;
            font-weight: bold;
            color: #555;
        }

        .back-link {
            display: block;
            margin-top: 20px;
            color: #6c5dd3;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Patient Details</h1>
        <div class="patient-details">
            <div><strong>Patient ID:</strong> <?= htmlspecialchars($patient['id']) ?></div>
            <div><strong>First Name:</strong> <?= htmlspecialchars($patient['first_name']) ?></div>
            <div><strong>Last Name:</strong> <?= htmlspecialchars($patient['last_name']) ?></div>
            <div><strong>Date of Birth:</strong> <?= htmlspecialchars($patient['date_of_birth']) ?></div>
            <div><strong>Email:</strong> <?= htmlspecialchars($patient['email']) ?></div>
            <div><strong>Phone:</strong> <?= htmlspecialchars($patient['phone']) ?></div>
        </div>
        <a href="/it38b-Enterprise/index.php?page=patients" class="back-link">Back to Patients List</a>

    </div>
</body>

</html>
<?php $conn->close(); ?>