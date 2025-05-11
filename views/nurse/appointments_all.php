<?php
// Include database connection
require_once 'config/config.php'; // Adjust the path as needed

// Number of records to display per page
$recordsPerPage = 10;

// Get the current page number from the URL
$page = isset($_GET['page_num']) ? intval($_GET['page_num']) : 1;

// Calculate the starting record for the current page
$startIndex = ($page - 1) * $recordsPerPage;

// Fetch the total number of appointments
$totalAppointmentsQuery = "SELECT COUNT(appointment_id) AS total FROM Appointments";
$totalResult = $conn->query($totalAppointmentsQuery);
$totalRowCount = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRowCount / $recordsPerPage);

// Fetch pending appointments for the current page, including patient and doctor names
$query = "SELECT
            a.appointment_id,
            a.appointment_datetime,
            a.reason_for_visit,
            p.first_name AS patient_first_name,
            p.last_name AS patient_last_name,
            d.first_name AS doctor_first_name,
            d.last_name AS doctor_last_name
          FROM Appointments a
          JOIN Patients p ON a.patient_id = p.patient_id
          JOIN Doctors d ON a.doctor_id = d.doctor_id
          WHERE a.status = 'Pending'
          ORDER BY a.appointment_datetime
          LIMIT ?, ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $startIndex, $recordsPerPage);
$stmt->execute();
$result = $stmt->get_result();
$appointments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Function to generate pagination links
function generatePaginationLinks($currentPage, $totalPages, $basePage = 'appointments_pending')
{
    $links = '';
    $range = 2; // Number of page links to show before and after the current page

    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == 1 || $i == $totalPages || ($i >= $currentPage - $range && $i <= $currentPage + $range)) {
            if ($i == $currentPage) {
                $links .= '<span class="active">' . $i . '</span>';
            } else {
                $links .= '<button onclick="window.location.href=\'?page=' . $basePage . '&page_num=' . $i . '\'">' . $i . '</button>';
            }
        } elseif (($i == $currentPage - $range - 1 && $currentPage - $range > 2) || ($i == $currentPage + $range + 1 && $currentPage + $range < $totalPages - 1)) {
            $links .= '<span>...</span>';
        }
    }
    return $links;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Appointments</title>
    <style>
        body {
            font-family: sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
        }

        .appointments-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .search-bar input[type="text"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .appointments-list {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            padding: 20px;
        }

        .appointments-table {
            width: 100%;
            border-collapse: collapse;
        }

        .appointments-table th,
        .appointments-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .appointments-table th {
            background-color: #f9f9f9;
            color: #555;
            font-weight: bold;
        }

        .appointments-table tbody tr:last-child td {
            border-bottom: none;
        }

        .appointment-actions a {
            margin-right: 10px;
            text-decoration: none;
            color: #6c5dd3;
            font-size: 0.9em;
        }

        .appointment-actions a:hover {
            text-decoration: underline;
        }

        .pagination {
            margin-top: 20px;
            display: flex;
            justify-content: center;
        }

        .pagination button,
        .pagination span {
            padding: 8px 12px;
            margin: 0 5px;
            border: 1px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9em;
            background-color: #fff;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        .pagination span.active {
            background-color: #6c5dd3;
            color: white;
            border-color: #6c5dd3;
        }

        .pagination button:hover {
            background-color: #f0f0f0;
            border-color: #bbb;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .appointments-table {
                width: 100%;
            }

            .appointments-table thead {
                display: none;
            }

            .appointments-table tr {
                display: block;
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 6px;
            }

            .appointments-table td {
                display: block;
                text-align: right;
                padding-left: 50%;
                position: relative;
                border-bottom: none;
            }

            .appointments-table td:before {
                content: attr(data-label);
                position: absolute;
                left: 10px;
                text-align: left;
                font-weight: bold;
            }

            .appointments-actions {
                flex-direction: column;
                gap: 10px;
                align-items: stretch;
            }

            .search-bar {
                width: 100%;
            }

            .search-bar input[type="text"] {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Pending Appointments</h1>

        <div class="appointments-actions">
            <div class="search-bar">
                <input type="text" placeholder="Search pending appointments...">
            </div>
        </div>

        <div class="appointments-list">
            <table class="appointments-table">
                <thead>
                    <tr>
                        <th>Appointment ID</th>
                        <th>Date & Time</th>
                        <th>Patient Name</th>
                        <th>Doctor Name</th>
                        <th>Reason for Visit</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($appointments)): ?>
                        <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td data-label="Appointment ID"><?php echo htmlspecialchars($appointment['appointment_id']); ?>
                                </td>
                                <td data-label="Date & Time">
                                    <?php echo htmlspecialchars($appointment['appointment_datetime']); ?>
                                </td>
                                <td data-label="Patient Name">
                                    <?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?>
                                </td>
                                <td data-label="Doctor Name">
                                    <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?>
                                </td>
                                <td data-label="Reason for Visit">
                                    <?php echo htmlspecialchars($appointment['reason_for_visit']); ?>
                                </td>
                                <td class="appointment-actions" data-label="Actions">
                                    <a
                                        href="functions/view_appointment.php?id=<?php echo htmlspecialchars($appointment['appointment_id']); ?>"><span
                                            class="material-icons">visibility</span> View</a>
                                    <a
                                        href="functions/edit_appointment.php?id=<?php echo htmlspecialchars($appointment['appointment_id']); ?>"><span
                                            class="material-icons">edit</span> Edit</a>
                                    <a href="functions/confirm_appointment.php?id=<?php echo htmlspecialchars($appointment['appointment_id']); ?>"
                                        onclick="return confirm('Confirm this appointment?')"><span
                                            class="material-icons">check_circle</span> Confirm</a>
                                    <a href="functions/cancel_appointment.php?id=<?php echo htmlspecialchars($appointment['appointment_id']); ?>"
                                        onclick="return confirm('Cancel this appointment?')"><span
                                            class="material-icons">cancel</span> Cancel</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No pending appointments found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php echo generatePaginationLinks($page, $totalPages, 'appointments_pending'); ?>
            </div>
        <?php endif; ?>
    </div>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</body>

</html>
<?php
$conn->close();
?>