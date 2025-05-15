<?php
// Start the session (if not already started)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    // Redirect to login page or unauthorized page
    header("Location: /login.php"); // Adjust path as needed
    exit();
}

// Include database connection
require_once __DIR__ . '/../../config/config.php';

// --- Fetch Data for Dashboard Widgets ---

// Get the doctor's ID
$doctorId = $_SESSION['doctor_id'] ?? null;
if (!$doctorId) {
    // Handle case where doctor_id is not set in the session
    die("Error: Doctor ID not found in session.");
}

// Initialize variables to avoid undefined variable warnings
$totalPatients = 0;
$todaysAppointments = 0;
$pendingRequests = 0;
$todaysAppointmentsList = [];
$nextPatient = null;
$patientHistory = [];
$reasonsData = [];
$genderData = [];
$monthlyAppointmentsData = [];
$reasonLabels = json_encode([]);
$reasonCounts = json_encode([]);
$genderLabels = json_encode([]);
$genderCounts = json_encode([]);
$months = json_encode([]);
$appointmentCounts = json_encode([]);

// 1. Total Patients (patients who have appointments with this doctor)
$totalPatientsQuery = "SELECT COUNT(DISTINCT p.patient_id) AS total_patients
                            FROM appointments a
                            JOIN patients p ON a.patient_id = p.patient_id
                            WHERE a.doctor_id = ?";
$stmtTotalPatients = mysqli_prepare($conn, $totalPatientsQuery);
if ($stmtTotalPatients) {
    mysqli_stmt_bind_param($stmtTotalPatients, "i", $doctorId);
    mysqli_stmt_execute($stmtTotalPatients);
    $totalPatientsResult = mysqli_stmt_get_result($stmtTotalPatients);
    if ($totalPatientsResult) {
        $totalPatients = mysqli_fetch_assoc($totalPatientsResult)['total_patients'] ?? 0;
        mysqli_free_result($totalPatientsResult);
    }
    mysqli_stmt_close($stmtTotalPatients);
} else {
    error_log("Error preparing statement: " . mysqli_error($conn));
}


// 2. Today's Appointments
$today = date("Y-m-d");
$todaysAppointmentsQuery = "SELECT COUNT(*) AS todays_appointments
                                FROM appointments
                                WHERE doctor_id = ?
                                AND DATE(appointment_datetime) = ?";
$stmtTodaysAppointments = mysqli_prepare($conn, $todaysAppointmentsQuery);
if ($stmtTodaysAppointments) {
    mysqli_stmt_bind_param($stmtTodaysAppointments, "is", $doctorId, $today);
    mysqli_stmt_execute($stmtTodaysAppointments);
    $todaysAppointmentsResult = mysqli_stmt_get_result($stmtTodaysAppointments);
    if ($todaysAppointmentsResult) {
        $todaysAppointments = mysqli_fetch_assoc($todaysAppointmentsResult)['todays_appointments'] ?? 0;
        mysqli_free_result($todaysAppointmentsResult);
    }
    mysqli_stmt_close($stmtTodaysAppointments);
} else {
    error_log("Error preparing statement: " . mysqli_error($conn));
}


// 3. Pending Appointment Requests (Assuming a status of 'Requested')
$pendingRequestsQuery = "SELECT COUNT(*) AS pending_requests
                                FROM appointments
                                WHERE doctor_id = ?
                                AND status = 'Requested'";
$stmtPendingRequests = mysqli_prepare($conn, $pendingRequestsQuery);
if ($stmtPendingRequests) {
    mysqli_stmt_bind_param($stmtPendingRequests, "i", $doctorId);
    mysqli_stmt_execute($stmtPendingRequests);
    $pendingRequestsResult = mysqli_stmt_get_result($stmtPendingRequests);
    if ($pendingRequestsResult) {
        $pendingRequests = mysqli_fetch_assoc($pendingRequestsResult)['pending_requests'] ?? 0;
        mysqli_free_result($pendingRequestsResult);
    }
    mysqli_stmt_close($stmtPendingRequests);
} else {
    error_log("Error preparing statement: " . mysqli_error($conn));
}


// 4. Today's Appointments List
$todaysAppointmentsListQuery = "SELECT a.appointment_id, u.first_name AS patient_first_name, u.last_name AS patient_last_name,
                                            DATE_FORMAT(a.appointment_datetime, '%h:%i %p') AS appointment_time, a.reason_for_visit, a.status, p.patient_id
                                   FROM appointments a
                                   JOIN patients p ON a.patient_id = p.patient_id
                                   JOIN users u ON p.user_id = u.user_id
                                   WHERE a.doctor_id = ?
                                   AND DATE(a.appointment_datetime) = ?
                                   ORDER BY a.appointment_datetime ASC";
$stmtTodaysAppointmentsList = mysqli_prepare($conn, $todaysAppointmentsListQuery);
if ($stmtTodaysAppointmentsList) {
    mysqli_stmt_bind_param($stmtTodaysAppointmentsList, "is", $doctorId, $today);
    mysqli_stmt_execute($stmtTodaysAppointmentsList);
    $todaysAppointmentsListResult = mysqli_stmt_get_result($stmtTodaysAppointmentsList);
    if ($todaysAppointmentsListResult) {
        $todaysAppointmentsList = mysqli_fetch_all($todaysAppointmentsListResult, MYSQLI_ASSOC);
        mysqli_free_result($todaysAppointmentsListResult);
    }
    mysqli_stmt_close($stmtTodaysAppointmentsList);
} else {
    error_log("Error preparing statement: " . mysqli_error($conn));
}


// 5. Next Patient Details
$nextAppointmentQuery = "SELECT a.appointment_id, p.patient_id, u.first_name, u.last_name, p.date_of_birth, p.gender,
                                            pd.medical_record_number, u.phone_number, /* Changed from pd.phone_number to u.phone_number */
                                            DATE_FORMAT(a.appointment_datetime, '%Y-%m-%d') AS next_appointment_date,
                                            DATE_FORMAT(a.appointment_datetime, '%h:%i %p') AS next_appointment_time
                               FROM appointments a
                               JOIN patients p ON a.patient_id = p.patient_id
                               JOIN users u ON p.user_id = u.user_id
                               LEFT JOIN patient_descriptions pd ON p.patient_id = pd.patient_id
                               WHERE a.doctor_id = ?
                               AND a.appointment_datetime >= NOW()
                               ORDER BY a.appointment_datetime ASC
                               LIMIT 1";
$stmtNextAppointment = mysqli_prepare($conn, $nextAppointmentQuery);
if ($stmtNextAppointment) {
    mysqli_stmt_bind_param($stmtNextAppointment, "i", $doctorId);
    mysqli_stmt_execute($stmtNextAppointment);
    $nextAppointmentResult = mysqli_stmt_get_result($stmtNextAppointment);
    if ($nextAppointmentResult) {
        $nextPatient = mysqli_fetch_assoc($nextAppointmentResult);
        mysqli_free_result($nextAppointmentResult);
    }
    mysqli_stmt_close($stmtNextAppointment);
} else {
    error_log("Error preparing statement: " . mysqli_error($conn));
}


// 6. Patient History Snippet (Example - adjust based on your tables and what you consider 'history')
if ($nextPatient && isset($nextPatient['patient_id'])) {
    $historyQuery = "SELECT mr.diagnosis
                                FROM medicalrecords mr
                                WHERE mr.patient_id = ?
                                ORDER BY mr.record_datetime DESC
                                LIMIT 3";
    $stmtHistory = mysqli_prepare($conn, $historyQuery);
    if ($stmtHistory) {
        mysqli_stmt_bind_param($stmtHistory, "i", $nextPatient['patient_id']);
        mysqli_stmt_execute($stmtHistory);
        $historyResult = mysqli_stmt_get_result($stmtHistory);
        if ($historyResult) {
            $patientHistory = mysqli_fetch_all($historyResult, MYSQLI_ASSOC);
            mysqli_free_result($historyResult);
        }
        mysqli_stmt_close($stmtHistory);
    } else {
        error_log("Error preparing statement: " . mysqli_error($conn));
    }
}


// --- Fetch Data for Charts ---

// 1. Appointment Reasons Demographics
$reasonsQuery = "SELECT reason_for_visit AS reason, COUNT(*) AS count FROM appointments WHERE doctor_id = ? GROUP BY reason_for_visit";
$reasonsStmt = mysqli_prepare($conn, $reasonsQuery);
if ($reasonsStmt) {
    mysqli_stmt_bind_param($reasonsStmt, "i", $doctorId);
    mysqli_stmt_execute($reasonsStmt);
    $reasonsResult = mysqli_stmt_get_result($reasonsStmt);
    if ($reasonsResult) {
        $reasonsData = mysqli_fetch_all($reasonsResult, MYSQLI_ASSOC);
        $reasonLabels = json_encode(array_column($reasonsData, 'reason'));
        $reasonCounts = json_encode(array_column($reasonsData, 'count'));
        mysqli_free_result($reasonsResult);
    }
    mysqli_stmt_close($reasonsStmt);
} else {
    error_log("Error preparing statement: " . mysqli_error($conn));
}


// 2. Patient Demographics (Gender)
$genderQuery = "SELECT p.gender, COUNT(DISTINCT a.patient_id) AS count
                    FROM appointments a
                    JOIN patients p ON a.patient_id = p.patient_id
                    WHERE a.doctor_id = ? AND p.gender IS NOT NULL
                    GROUP BY p.gender";
$genderStmt = mysqli_prepare($conn, $genderQuery);
if ($genderStmt) {
    mysqli_stmt_bind_param($genderStmt, "i", $doctorId);
    mysqli_stmt_execute($genderStmt);
    $genderResult = mysqli_stmt_get_result($genderStmt);
    if ($genderResult) {
        $genderData = mysqli_fetch_all($genderResult, MYSQLI_ASSOC);
        $genderLabels = json_encode(array_column($genderData, 'gender'));
        $genderCounts = json_encode(array_column($genderData, 'count'));
        mysqli_free_result($genderResult);
    }
    mysqli_stmt_close($genderStmt);
} else {
    error_log("Error preparing statement: " . mysqli_error($conn));
}


// 3. Appointment Trends Over Time
$monthlyAppointmentsQuery = "SELECT
                                    DATE_FORMAT(appointment_datetime, '%Y-%m') AS month,
                                    COUNT(*) AS appointment_count
                                FROM appointments
                                WHERE doctor_id = ?
                                GROUP BY month
                                ORDER BY month";
$monthlyAppointmentsStmt = mysqli_prepare($conn, $monthlyAppointmentsQuery);
if ($monthlyAppointmentsStmt) {
    mysqli_stmt_bind_param($monthlyAppointmentsStmt, "i", $doctorId);
    mysqli_stmt_execute($monthlyAppointmentsStmt);
    $monthlyAppointmentsResult = mysqli_stmt_get_result($monthlyAppointmentsStmt);
    if ($monthlyAppointmentsResult) {
        $monthlyAppointmentsData = mysqli_fetch_all($monthlyAppointmentsResult, MYSQLI_ASSOC);
        $months = json_encode(array_column($monthlyAppointmentsData, 'month'));
        $appointmentCounts = json_encode(array_column($monthlyAppointmentsData, 'appointment_count'));
        mysqli_free_result($monthlyAppointmentsResult);
    }
    mysqli_stmt_close($monthlyAppointmentsStmt);
} else {
    error_log("Error preparing statement: " . mysqli_error($conn));
}


// Close the database connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor's Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-card {
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }

        .appointment-status {
            border-radius: 0.25rem;
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .status-requested {
            background-color: #fef08a;
            color: #a16207;
        }

        .status-scheduled {
            background-color: #f0f9ff;
            color: #0369a1;
        }

        .status-completed {
            background-color: #ecfdf5;
            color: #047857;
        }

        .status-no-show {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        .status-cancelled {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        .chart-container {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body class="bg-gray-100 font-sans antialiased">
    <div class="container mx-auto py-6">
        <h1 class="text-3xl font-semibold mb-6 text-gray-800">Doctor's Dashboard</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            <div class="dashboard-card bg-white p-6">
                <div class="flex items-center">
                    <div class="bg-indigo-100 text-indigo-500 rounded-full p-3 mr-4">
                        <i class="fas fa-users fa-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-700">Total Patients</h3>
                        <p class="text-2xl font-semibold text-indigo-600">
                            <?php echo htmlspecialchars($totalPatients); ?>
                        </p>
                        <p class="text-sm text-gray-500">Active Patients</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-card bg-white p-6">
                <div class="flex items-center">
                    <div class="bg-blue-100 text-blue-500 rounded-full p-3 mr-4">
                        <i class="far fa-calendar-check fa-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-700">Today's Appointments</h3>
                        <p class="text-2xl font-semibold text-blue-600">
                            <?php echo htmlspecialchars($todaysAppointments); ?>
                        </p>
                        <p class="text-sm text-gray-500"><?php echo date("d-M-Y"); ?></p>
                    </div>
                </div>
            </div>

            <div class="dashboard-card bg-white p-6">
                <div class="flex items-center">
                    <div class="bg-yellow-100 text-yellow-500 rounded-full p-3 mr-4">
                        <i class="fas fa-exclamation-triangle fa-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-700">Pending Requests</h3>
                        <p class="text-2xl font-semibold text-yellow-600">
                            <?php echo htmlspecialchars($pendingRequests); ?>
                        </p>
                        <p class="text-sm text-gray-500">New Appointment Requests</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dashboard-card p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">Today's Appointments</h2>
            <?php if (!empty($todaysAppointmentsList)): ?>
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr>
                            <thclass="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600
                                uppercase tracking-wider">
                                Patient
                                </thclass=>
                                <th
                                    class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Time
                                </th>
                                <th
                                    class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Reason
                                </th>
                                <th
                                    class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Status
                                </th>
                                <th
                                    class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Actions
                                </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($todaysAppointmentsList as $appointment): ?>
                            <tr>
                                <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                    <?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?>
                                </td>
                                <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                    <?php echo htmlspecialchars($appointment['appointment_time']); ?>
                                </td>
                                <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                    <?php echo htmlspecialchars($appointment['reason_for_visit']); ?>
                                </td>
                                <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                    <span
                                        class="appointment-status status-<?php echo strtolower(htmlspecialchars($appointment['status'])); ?>">
                                        <?php echo htmlspecialchars(ucfirst($appointment['status'])); ?>
                                    </span>
                                </td>
                                <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                    <a href="/appointments/view.php?id=<?php echo htmlspecialchars($appointment['appointment_id']); ?>"
                                        class="text-indigo-600 hover:text-indigo-900 mr-2">View</a>
                                    <a href="/medicalrecords/create.php?appointment_id=<?php echo htmlspecialchars($appointment['appointment_id']); ?>&patient_id=<?php echo htmlspecialchars($appointment['patient_id']); ?>"
                                        class="text-green-600 hover:text-green-900">Record</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-gray-600">No appointments scheduled for today.</p>
            <?php endif; ?>
            <div class="mt-4">
                <a href="/appointments/today.php" class="text-blue-500 hover:underline">See All Today's Appointments</a>
            </div>
        </div>

        <?php if ($nextPatient): ?>
            <div class="bg-white dashboard-card p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">Next Patient Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="font-semibold text-gray-700">
                            <?php echo htmlspecialchars($nextPatient['first_name'] . ' ' . $nextPatient['last_name']); ?>
                        </p>
                        <p class="text-gray-600">Patient ID: <?php echo htmlspecialchars($nextPatient['patient_id']); ?></p>
                        <?php if (!empty($nextPatient['medical_record_number'])): ?>
                            <p class="text-gray-600">MRN: <?php echo htmlspecialchars($nextPatient['medical_record_number']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="text-gray-600">Appointment:
                            <?php echo htmlspecialchars($nextPatient['next_appointment_date'] . ' at ' . $nextPatient['next_appointment_time']); ?>
                        </p>
                        <?php if (!empty($nextPatient['date_of_birth'])): ?>
                            <p class="text-gray-600">D.O.B:
                                <?php echo date("d F Y", strtotime($nextPatient['date_of_birth'])); ?>
                            </p>
                        <?php endif; ?>
                        <?php if (!empty($nextPatient['gender'])): ?>
                            <p class="text-gray-600">Sex: <?php echo htmlspecialchars(ucfirst($nextPatient['gender'])); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($nextPatient['phone_number'])): ?>
                            <p class="text-gray-600">Phone: <?php echo htmlspecialchars($nextPatient['phone_number']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($patientHistory)): ?>
                    <div class="mt-4">
                        <h3 class="font-semibold text-gray-700 mb-2">Recent Diagnoses</h3>
                        <div>
                            <?php foreach ($patientHistory as $condition): ?>
                                <span
                                    class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-0.5 text-indigo-800 text-sm mr-2">
                                    <?php echo htmlspecialchars($condition['diagnosis']); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="mt-4 flex space-x-2">
                    <?php if (!empty($nextPatient['phone_number'])): ?>
                        <a href="tel:<?php echo htmlspecialchars($nextPatient['phone_number']); ?>"
                            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm"><i
                                class="fas fa-phone"></i> Call</a>
                    <?php endif; ?>
                    <a href="/patients/view.php?id=<?php echo htmlspecialchars($nextPatient['patient_id']); ?>"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm"><i
                            class="far fa-file-alt"></i> Records</a>
                    <a href="/medicalrecords/create.php?patient_id=<?php echo htmlspecialchars($nextPatient['patient_id']); ?>"
                        class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded text-sm"><i
                            class="fas fa-notes-medical"></i> New Record</a>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white dashboard-card p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">Next Patient Details</h2>
                <p class="text-gray-600">No upcoming appointments scheduled.</p>
            </div>
        <?php endif; ?>

        <div class="bg-white dashboard-card p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">Quick Actions</h2>
            <div class="flex space-x-4">
                <a href="/appointments/create.php"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm"><i
                        class="far fa-calendar-plus"></i> Schedule Appointment</a>
                <a href="/patients/index.php"
                    class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm"><i
                        class="fas fa-user-injured"></i> View Patients</a>
                <a href="/medicalrecords/index.php"
                    class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm"><i
                        class="fas fa-file-medical"></i> Medical Records</a>
            </div>
        </div>

        <div class="bg-white dashboard-card p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">Notifications</h2>
            <p class="text-gray-600">No new notifications.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php if (!empty($reasonsData)): ?>
                <div class="chart-container">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800">Appointment Reasons</h2>
                    <canvas id="reasonsChart"></canvas>
                </div>
                <script>
                    const reasonsChartCtx = document.getElementById('reasonsChart').getContext('2d');
                    const reasonsChart = new Chart(reasonsChartCtx, {
                        type: 'bar',
                        data: {
                            labels: <?php echo $reasonLabels; ?>,
                            datasets: [{
                                label: 'Number of Appointments',
                                data: <?php echo $reasonCounts; ?>,
                                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Number of Appointments'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Reason for Visit'
                                    }
                                }
                            }
                        }
                    });
                </script>
            <?php endif; ?>

            <?php if (!empty($genderData)): ?>
                <div class="chart-container">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800">Patient Gender Demographics</h2>
                    <canvas id="genderChart"></canvas>
                </div>
                <script>
                    const genderChartCtx = document.getElementById('genderChart').getContext('2d');
                    const genderChart = new Chart(genderChartCtx, {
                        type: 'pie',
                        data: {
                            labels: <?php echo $genderLabels; ?>,
                            datasets: [{
                                label: 'Number of Patients',
                                data: <?php echo $genderCounts; ?>,
                                backgroundColor: [
                                    'rgba(255, 99, 132, 0.7)',
                                    'rgba(54, 162, 235, 0.7)',
                                    'rgba(255, 206, 86, 0.7)',
                                    'rgba(75, 192, 192, 0.7)',
                                    'rgba(153, 102, 255, 0.7)',
                                    'rgba(255, 159, 64, 0.7)'
                                ],
                                borderColor: 'rgba(255, 255, 255, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    });
                </script>
            <?php endif; ?>

            <?php if (!empty($monthlyAppointmentsData)): ?>
                <div class="chart-container">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800">Monthly Appointment Trends</h2>
                    <canvas id="monthlyAppointmentsChart"></canvas>
                </div>
                <script>
                    const monthlyAppointmentsChartCtx = document.getElementById('monthlyAppointmentsChart').getContext('2d');
                    const monthlyAppointmentsChart = new Chart(monthlyAppointmentsChartCtx, {
                        type: 'line',
                        data: {
                            labels: <?php echo $months; ?>,
                            datasets: [{
                                label: 'Number of Appointments',
                                data: <?php echo $appointmentCounts; ?>,
                                fill: false,
                                borderColor: 'rgba(75, 192, 192, 1)',
                                tension: 0.1
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Number of Appointments'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Month'
                                    }
                                }
                            }
                        }
                    });
                </script>
            <?php endif; ?>
        </div>

    </div>
</body>

</html>