<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    header('Location: /login.php'); // Adjust the path as needed
    exit();
}

require_once __DIR__ . '/../../config/config.php';

// Fetch the doctor's ID from the session
$doctorId = $_SESSION['doctor_id'] ?? null;
if (!$doctorId) {
    // Handle the case where doctor_id is not set
    die("Error: Doctor ID not found in session.");
}

// Fetch all appointments for the logged-in doctor
$query = "SELECT
            a.appointment_id,
            p.patient_id,
            u.first_name AS patient_first_name,
            u.last_name AS patient_last_name,
            DATE_FORMAT(a.appointment_datetime, '%M %e, %Y at %h:%i %p') AS appointment_datetime_formatted,
            a.reason_for_visit,
            a.status
          FROM appointments a
          JOIN patients p ON a.patient_id = p.patient_id
          JOIN users u ON p.user_id = u.user_id
          WHERE a.doctor_id = ?
          ORDER BY a.appointment_datetime ASC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $doctorId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$appointments = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>

<div class="container mx-auto p-6 bg-white shadow-md rounded-md">
    <h2 class="text-2xl font-semibold mb-6 text-gray-800">
        <i class="fas fa-calendar-alt mr-2"></i> Your Appointments
    </h2>

    <?php if (!empty($appointments)): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full leading-normal">
                <thead class="bg-gray-100">
                    <tr>
                        <th
                            class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            #
                        </th>
                        <th
                            class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Patient Name
                        </th>
                        <th
                            class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Date & Time
                        </th>
                        <th
                            class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Reason for Visit
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
                    <?php foreach ($appointments as $appointment): ?>
                        <tr>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <p class="text-gray-900 whitespace-no-wrap">
                                    <?php echo htmlspecialchars($appointment['appointment_id']); ?>
                                </p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <div class="flex items-center">
                                    <div class="ml-2">
                                        <p class="text-gray-900 whitespace-no-wrap">
                                            <?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <p class="text-gray-900 whitespace-no-wrap">
                                    <?php echo htmlspecialchars($appointment['appointment_datetime_formatted']); ?>
                                </p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <p class="text-gray-900 whitespace-no-wrap">
                                    <?php echo htmlspecialchars($appointment['reason_for_visit']); ?>
                                </p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <span class="relative inline-block px-3 py-1 font-semibold text-<?php
                                switch (strtolower($appointment['status'])) {
                                    case 'requested':
                                        echo 'yellow';
                                        break;
                                    case 'scheduled':
                                        echo 'blue';
                                        break;
                                    case 'completed':
                                        echo 'green';
                                        break;
                                    case 'cancelled':
                                    case 'no-show':
                                        echo 'red';
                                        break;
                                    default:
                                        echo 'gray';
                                        break;
                                }
                                ?>-600 leading-tight">
                                    <span class="absolute inset-0 bg-<?php
                                    switch (strtolower($appointment['status'])) {
                                        case 'requested':
                                            echo 'yellow';
                                            break;
                                        case 'scheduled':
                                            echo 'blue';
                                            break;
                                        case 'completed':
                                            echo 'green';
                                            break;
                                        case 'cancelled':
                                        case 'no-show':
                                            echo 'red';
                                            break;
                                        default:
                                            echo 'gray';
                                            break;
                                    }
                                    ?>-200 opacity-50 rounded-full"></span>
                                    <span
                                        class="relative"><?php echo htmlspecialchars(ucfirst($appointment['status'])); ?></span>
                                </span>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <a href="?page=view_appointment&id=<?php echo htmlspecialchars($appointment['appointment_id']); ?>"
                                    class="text-blue-500 hover:underline mr-3">
                                    <i class="fas fa-eye mr-1"></i> View
                                </a>
                                <a href="?page=edit_appointment&id=<?php echo htmlspecialchars($appointment['appointment_id']); ?>"
                                    class="text-green-500 hover:underline mr-3">
                                    <i class="fas fa-edit mr-1"></i> Edit
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-md">
            <p><i class="fas fa-exclamation-triangle mr-2"></i> No appointments found for you.</p>
        </div>
    <?php endif; ?>

    <div class="mt-6">
        <a href="?page=create_appointment"
            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-full shadow-md hover:shadow-lg transition duration-300 ease-in-out">
            <i class="fas fa-plus mr-2"></i> Create New Appointment
        </a>
    </div>
</div>