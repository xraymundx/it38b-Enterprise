<?php
require_once __DIR__ . '/../config/config.php';


// Ensure 'id' is present and valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /it38b-Enterprise/views/nurse/patients.php?error=Invalid+patient+ID");
    exit();
}

$patientId = intval($_GET['id']);

// Fetch patient with all related information
$query = "SELECT p.patient_id, p.date_of_birth, p.gender, u.first_name, u.last_name, u.email, u.phone_number,
          pd.description, pd.address, pd.medical_record_number, pd.insurance_provider,
          pd.insurance_policy_number, pd.emergency_contact_name, pd.emergency_contact_phone, pd.notes,
          u.profile_image
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
    header("Location: /it38b-Enterprise/views/nurse/patients.php?error=Patient+not+found");
    exit();
}

// Get patient appointment history
$appointments_query = "SELECT a.appointment_id, a.appointment_datetime, a.reason_for_visit, a.status,
                      CONCAT(u.first_name, ' ', u.last_name) as doctor_name, s.specialization_name
                      FROM appointments a
                      JOIN doctors d ON a.doctor_id = d.doctor_id
                      JOIN users u ON d.user_id = u.user_id
                      JOIN specializations s ON d.specialization_id = s.specialization_id
                      WHERE a.patient_id = ?
                      ORDER BY a.appointment_datetime DESC";

$appointments_stmt = $conn->prepare($appointments_query);
$appointments_stmt->bind_param("i", $patientId);
$appointments_stmt->execute();
$appointments_result = $appointments_stmt->get_result();
$appointments = [];
while ($row = $appointments_result->fetch_assoc()) {
    $appointments[] = $row;
}
$appointments_stmt->close();

// Get patient medical records
$medical_records_query = "SELECT mr.record_id, mr.record_datetime, mr.diagnosis, mr.treatment,
                         CONCAT(u.first_name, ' ', u.last_name) as doctor_name
                         FROM medicalrecords mr
                         JOIN doctors d ON mr.doctor_id = d.doctor_id
                         JOIN users u ON d.user_id = u.user_id
                         WHERE mr.patient_id = ?
                         ORDER BY mr.record_datetime DESC";

$medical_records_stmt = $conn->prepare($medical_records_query);
$medical_records_stmt->bind_param("i", $patientId);
$medical_records_stmt->execute();
$medical_records_result = $medical_records_stmt->get_result();
$medical_records = [];
while ($row = $medical_records_result->fetch_assoc()) {
    $medical_records[] = $row;
}
$medical_records_stmt->close();

// Get patient billing records
$billing_records_query = "SELECT b.bill_id, b.billing_date, b.description, b.amount, b.payment_status, b.payment_method
                         FROM billingrecords b
                         WHERE b.patient_id = ?
                         ORDER BY b.billing_date DESC";

$billing_records_stmt = $conn->prepare($billing_records_query);
$billing_records_stmt->bind_param("i", $patientId);
$billing_records_stmt->execute();
$billing_records_result = $billing_records_stmt->get_result();
$billing_records = [];
while ($row = $billing_records_result->fetch_assoc()) {
    $billing_records[] = $row;
}
$billing_records_stmt->close();

// Format profile image path if it exists
$profile_image = null;
if (!empty($patient['profile_image'])) {
    $profile_image = $patient['profile_image'];
    if (strpos($profile_image, '/it38b-Enterprise/') !== 0) {
        $profile_image = '/it38b-Enterprise' . $profile_image;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Patient Details</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-light: #e0e7ff;
            --secondary-color: #6c5dd3;
            --accent-color: #8b5cf6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --text-dark: #111827;
            --text-medium: #4b5563;
            --text-light: #6b7280;
            --background-light: #f3f4f6;
            --border-color: #e5e7eb;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-light);
            margin: 0;
            padding: 20px;
            color: var(--text-dark);
            line-height: 1.6;
        }

        .container {
            background-color: white;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            width: 95%;
            max-width: 1200px;
            margin: 0 auto;
            overflow: hidden;
        }

        .header {
            padding: 30px 40px;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            gap: 30px;
            position: relative;
        }

        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            background-color: white;
            object-fit: cover;
            flex-shrink: 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .profile-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            background-color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: var(--primary-color);
            flex-shrink: 0;
        }

        .header-content {
            flex-grow: 1;
        }

        h1 {
            margin: 0 0 5px 0;
            font-size: 2.4rem;
            font-weight: 700;
        }

        .patient-id {
            font-size: 1rem;
            opacity: 0.9;
            font-weight: 500;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 30px;
            font-size: 0.9rem;
            margin-top: 10px;
            font-weight: 500;
        }

        .content {
            padding: 30px 40px;
        }

        .section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .section-title {
            color: var(--text-dark);
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title .material-icons-outlined {
            font-size: 1.4rem;
            color: var(--secondary-color);
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .detail-item {
            margin-bottom: 8px;
        }

        .detail-label {
            font-weight: 500;
            color: var(--text-light);
            display: block;
            font-size: 0.9rem;
            margin-bottom: 4px;
        }

        .detail-value {
            color: var(--text-dark);
            font-weight: 500;
        }

        .empty-value {
            color: var(--text-light);
            font-style: italic;
        }

        .notes {
            grid-column: 1 / -1;
            background-color: var(--background-light);
            padding: 15px;
            border-radius: 8px;
            white-space: pre-line;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: var(--secondary-color);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .back-link:hover {
            background-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
        }

        /* History Section Styles */
        .history-section {
            margin-top: 40px;
        }

        .tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .tab {
            padding: 12px 25px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            color: var(--text-light);
            transition: all 0.3s ease;
        }

        .tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        .tab:hover:not(.active) {
            color: var(--text-dark);
            background-color: var(--background-light);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Table Styles */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .data-table th {
            background-color: var(--background-light);
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: var(--text-medium);
            border-bottom: 2px solid var(--border-color);
            font-size: 0.9rem;
        }

        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-dark);
            font-size: 0.95rem;
        }

        .data-table tr:hover {
            background-color: rgba(243, 244, 246, 0.5);
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-scheduled {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        .status-completed {
            background-color: #ecfdf5;
            color: var(--success-color);
        }

        .status-cancelled {
            background-color: #fef2f2;
            color: var(--danger-color);
        }

        .status-requested {
            background-color: #fffbeb;
            color: var(--warning-color);
        }

        .status-paid {
            background-color: #ecfdf5;
            color: var(--success-color);
        }

        .status-pending {
            background-color: #fffbeb;
            color: var(--warning-color);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-light);
            font-style: italic;
        }

        .amount {
            font-weight: 600;
        }

        .view-link {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .view-link:hover {
            text-decoration: underline;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .tabs {
                overflow-x: auto;
                white-space: nowrap;
                padding-bottom: 5px;
            }

            .data-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <?php if ($profile_image): ?>
                <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Patient Photo" class="profile-image">
            <?php else: ?>
                <div class="profile-placeholder">
                    <?php echo strtoupper(substr($patient['first_name'], 0, 1)); ?>
                </div>
            <?php endif; ?>

            <div class="header-content">
                <h1><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></h1>
                <div class="patient-id">Patient ID: <?php echo htmlspecialchars($patient['patient_id']); ?></div>
                <div class="badge">
                    <?php echo htmlspecialchars($patient['gender'] ?? 'Not specified'); ?> •
                    <?php
                    $dob = new DateTime($patient['date_of_birth']);
                    $now = new DateTime();
                    $age = $now->diff($dob)->y;
                    echo $age . ' years old';
                    ?>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="section">
                <h2 class="section-title">
                    <span class="material-icons-outlined">contact_mail</span>
                    Contact Information
                </h2>
                <div class="details-grid">
                    <div class="detail-item">
                        <span class="detail-label">Email</span>
                        <span class="detail-value"><?php echo htmlspecialchars($patient['email']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Phone</span>
                        <span class="detail-value">
                            <?php echo htmlspecialchars($patient['phone_number'] ?? '-'); ?>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Address</span>
                        <span class="detail-value">
                            <?php echo htmlspecialchars($patient['address'] ?? '-'); ?>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Date of Birth</span>
                        <span class="detail-value">
                            <?php echo htmlspecialchars(date("F j, Y", strtotime($patient['date_of_birth']))); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <span class="material-icons-outlined">medical_services</span>
                    Medical Information
                </h2>
                <div class="details-grid">
                    <div class="detail-item">
                        <span class="detail-label">Medical Record Number</span>
                        <span class="detail-value">
                            <?php echo !empty($patient['medical_record_number']) ?
                                htmlspecialchars($patient['medical_record_number']) :
                                '<span class="empty-value">Not assigned</span>';
                            ?>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Insurance Provider</span>
                        <span class="detail-value">
                            <?php echo !empty($patient['insurance_provider']) ?
                                htmlspecialchars($patient['insurance_provider']) :
                                '<span class="empty-value">Not specified</span>';
                            ?>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Insurance Policy Number</span>
                        <span class="detail-value">
                            <?php echo !empty($patient['insurance_policy_number']) ?
                                htmlspecialchars($patient['insurance_policy_number']) :
                                '<span class="empty-value">Not specified</span>';
                            ?>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Description</span>
                        <span class="detail-value">
                            <?php echo !empty($patient['description']) ?
                                htmlspecialchars($patient['description']) :
                                '<span class="empty-value">No description available</span>';
                            ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <span class="material-icons-outlined">emergency</span>
                    Emergency Contact
                </h2>
                <div class="details-grid">
                    <div class="detail-item">
                        <span class="detail-label">Name</span>
                        <span class="detail-value">
                            <?php echo !empty($patient['emergency_contact_name']) ?
                                htmlspecialchars($patient['emergency_contact_name']) :
                                '<span class="empty-value">Not specified</span>';
                            ?>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Phone</span>
                        <span class="detail-value">
                            <?php echo !empty($patient['emergency_contact_phone']) ?
                                htmlspecialchars($patient['emergency_contact_phone']) :
                                '<span class="empty-value">Not specified</span>';
                            ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php if (!empty($patient['notes'])): ?>
                <div class="section">
                    <h2 class="section-title">
                        <span class="material-icons-outlined">notes</span>
                        Additional Notes
                    </h2>
                    <div class="notes">
                        <?php echo nl2br(htmlspecialchars($patient['notes'])); ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="history-section">
                <h2 class="section-title">
                    <span class="material-icons-outlined">history</span>
                    Patient History
                </h2>

                <div class="tabs">
                    <button class="tab active" data-target="appointments">Appointments</button>
                    <button class="tab" data-target="medical-records">Medical Records</button>
                    <button class="tab" data-target="billing">Billing Records</button>
                </div>

                <div id="appointments" class="tab-content active">
                    <?php if (count($appointments) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Doctor</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y h:i A', strtotime($appointment['appointment_datetime'])); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($appointment['doctor_name']); ?>
                                            <div style="font-size: 0.8rem; color: var(--text-light);">
                                                <?php echo htmlspecialchars($appointment['specialization_name']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($appointment['reason_for_visit']); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = '';
                                            switch ($appointment['status']) {
                                                case 'Scheduled':
                                                    $statusClass = 'status-scheduled';
                                                    break;
                                                case 'Completed':
                                                    $statusClass = 'status-completed';
                                                    break;
                                                case 'Cancelled':
                                                    $statusClass = 'status-cancelled';
                                                    break;
                                                case 'No Show':
                                                    $statusClass = 'status-cancelled';
                                                    break;
                                                case 'Requested':
                                                    $statusClass = 'status-requested';
                                                    break;
                                                default:
                                                    $statusClass = '';
                                            }
                                            ?>
                                            <span class="status-badge <?php echo $statusClass; ?>">
                                                <?php echo htmlspecialchars($appointment['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="/it38b-Enterprise/views/nurse/appointment_view.php?id=<?php echo $appointment['appointment_id']; ?>"
                                                class="view-link">View Details</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">No appointment history available</div>
                    <?php endif; ?>
                </div>

                <div id="medical-records" class="tab-content">
                    <?php if (count($medical_records) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Doctor</th>
                                    <th>Diagnosis</th>
                                    <th>Treatment</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($medical_records as $record): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($record['record_datetime'])); ?></td>
                                        <td><?php echo htmlspecialchars($record['doctor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($record['diagnosis']); ?></td>
                                        <td><?php echo htmlspecialchars($record['treatment']); ?></td>
                                        <td>
                                            <a href="/it38b-Enterprise/views/nurse/medical_record_view.php?id=<?php echo $record['record_id']; ?>"
                                                class="view-link">View Details</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">No medical records available</div>
                    <?php endif; ?>
                </div>

                <div id="billing" class="tab-content">
                    <?php if (count($billing_records) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Payment Method</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($billing_records as $bill): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($bill['billing_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($bill['description']); ?></td>
                                        <td class="amount">$<?php echo number_format($bill['amount'], 2); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = '';
                                            switch ($bill['payment_status']) {
                                                case 'Paid':
                                                    $statusClass = 'status-paid';
                                                    break;
                                                case 'Pending':
                                                    $statusClass = 'status-pending';
                                                    break;
                                                case 'Cancelled':
                                                    $statusClass = 'status-cancelled';
                                                    break;
                                                default:
                                                    $statusClass = '';
                                            }
                                            ?>
                                            <span class="status-badge <?php echo $statusClass; ?>">
                                                <?php echo htmlspecialchars($bill['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($bill['payment_method'] ?? 'N/A'); ?></td>
                                        <td>
                                            <a href="/it38b-Enterprise/views/nurse/billing_view.php?id=<?php echo $bill['bill_id']; ?>"
                                                class="view-link">View Details</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">No billing records available</div>
                    <?php endif; ?>
                </div>
            </div>

            <a href="/it38b-Enterprise/routes/dashboard_router.php?page=patients" class="back-link">
                <span class="material-icons-outlined">arrow_back</span> Back to Patients
            </a>
        </div>
    </div>

    <script>
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function () {
            const tabs = document.querySelectorAll('.tab');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // Remove active class from all tabs and content
                    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

                    // Add active class to clicked tab and corresponding content
                    tab.classList.add('active');
                    const target = tab.dataset.target;
                    document.getElementById(target).classList.add('active');
                });
            });
        });
    </script>
</body>

</html>
<?php
$conn->close();
?>