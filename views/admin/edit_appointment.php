<?php

// Include database connection
require_once __DIR__ . '/../../config/config.php';

// Check if user is logged in and is an administrator
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'administrator') {
    header('Location: /login.php');
    exit();
}

// Ensure database connection is established
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection not established properly. Please check your configuration.");
}

// Initialize variables for feedback and appointment data
$errorMessage = '';
$successMessage = '';
$appointmentData = null;
$patients = [];
$doctors = [];

// --- Fetch Appointment Data for Editing ---
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $editAppointmentId = intval($_GET['id']);
    $editAppointmentQuery = "SELECT
                                a.appointment_id,
                                a.patient_id,
                                a.doctor_id,
                                a.appointment_datetime,
                                a.reason_for_visit,
                                a.status
                            FROM appointments a
                            WHERE a.appointment_id = $editAppointmentId";
    $editAppointmentResult = mysqli_query($conn, $editAppointmentQuery);
    $appointmentData = mysqli_fetch_assoc($editAppointmentResult);

    if (!$appointmentData) {
        $errorMessage = "Appointment not found.";
    }
} else {
    $errorMessage = "Invalid or missing Appointment ID.";
}

// --- Fetch Patients and Doctors for Dropdowns ---
$patientsQuery = "SELECT p.patient_id, u.first_name, u.last_name
                    FROM patients p
                    JOIN users u ON p.user_id = u.user_id
                    ORDER BY u.last_name, u.first_name";
$patientsResult = mysqli_query($conn, $patientsQuery);
$patients = mysqli_fetch_all($patientsResult, MYSQLI_ASSOC);

$doctorsQuery = "SELECT d.doctor_id, u.first_name, u.last_name, s.specialization_name
                   FROM doctors d
                   JOIN users u ON d.user_id = u.user_id
                   JOIN specializations s ON d.specialization_id = s.specialization_id
                   ORDER BY u.last_name, u.first_name";
$doctorsResult = mysqli_query($conn, $doctorsQuery);
$doctors = mysqli_fetch_all($doctorsResult, MYSQLI_ASSOC);

// --- Handle Form Submission for Editing Appointment ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_appointment'])) {
    $appointmentId = intval($_POST['appointment_id']);
    $patientId = intval($_POST['patient_id']);
    $doctorId = intval($_POST['doctor_id']);
    $appointmentDatetime = mysqli_real_escape_string($conn, $_POST['appointment_datetime']);
    $reasonForVisit = mysqli_real_escape_string($conn, $_POST['reason_for_visit']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Validate input
    if (empty($patientId) || empty($doctorId) || empty($appointmentDatetime) || empty($status)) {
        $errorMessage = "Patient, Doctor, Appointment Date/Time, and Status are required.";
    } else {
        // Update appointment data
        $updateQuery = "UPDATE appointments
                            SET patient_id = $patientId,
                                doctor_id = $doctorId,
                                appointment_datetime = '$appointmentDatetime',
                                reason_for_visit = '$reasonForVisit',
                                status = '$status',
                                updated_at = NOW()
                            WHERE appointment_id = $appointmentId";

        if (mysqli_query($conn, $updateQuery)) {
            $successMessage = "Appointment updated successfully!";

            // Refetch the updated appointment data
            $editAppointmentResult = mysqli_query($conn, "SELECT
                                                                a.appointment_id,
                                                                a.patient_id,
                                                                a.doctor_id,
                                                                a.appointment_datetime,
                                                                a.reason_for_visit,
                                                                a.status
                                                            FROM appointments a
                                                            WHERE a.appointment_id = $appointmentId");
            $appointmentData = mysqli_fetch_assoc($editAppointmentResult);
        } else {
            $errorMessage = "Error updating appointment: " . mysqli_error($conn);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Appointment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>

<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-semibold mb-6 text-gray-800">Edit Appointment</h1>

        <p class="mb-4"><a href="?page=appointments" class="text-blue-500 hover:underline"><i
                    class="fas fa-arrow-left mr-2"></i> Back to Appointments</a></p>

        <?php if ($errorMessage): ?>
            <div class="bg-red-200 border-red-600 text-red-600 px-4 py-3 rounded-md mb-4" role="alert">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($successMessage): ?>
            <div class="bg-green-200 border-green-600 text-green-600 px-4 py-3 rounded-md mb-4" role="alert">
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($appointmentData): ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <form class="space-y-4" method="post" action="">
                    <input type="hidden" name="edit_appointment" value="1">
                    <input type="hidden" name="appointment_id"
                        value="<?php echo htmlspecialchars($appointmentData['appointment_id']); ?>">

                    <div>
                        <label for="patient_id"
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Patient</label>
                        <select id="patient_id" name="patient_id"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            required>
                            <option value="" disabled>Select Patient</option>
                            <?php foreach ($patients as $patient): ?>
                                <option value="<?php echo htmlspecialchars($patient['patient_id']); ?>"
                                    <?php if ($patient['patient_id'] === $appointmentData['patient_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($patient['first_name']) . ' ' . htmlspecialchars($patient['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="doctor_id"
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Doctor</label>
                        <select id="doctor_id" name="doctor_id"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            required>
                            <option value="" disabled>Select Doctor</option>
                            <?php foreach ($doctors as $doctor): ?>
                                <option value="<?php echo htmlspecialchars($doctor['doctor_id']); ?>"
                                    <?php if ($doctor['doctor_id'] === $appointmentData['doctor_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($doctor['first_name']) . ' ' . htmlspecialchars($doctor['last_name']) . ' (' . htmlspecialchars($doctor['specialization_name']) . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="appointment_datetime"
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Date and Time</label>
                        <input type="datetime-local" name="appointment_datetime" id="appointment_datetime"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($appointmentData['appointment_datetime']))); ?>"
                            required>
                    </div>

                    <div>
                        <label for="reason_for_visit"
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Reason for Visit</label>
                        <textarea id="reason_for_visit" name="reason_for_visit" rows="3"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"><?php echo htmlspecialchars($appointmentData['reason_for_visit']); ?></textarea>
                    </div>

                    <div>
                        <label for="status"
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Status</label>
                        <select id="status" name="status"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            required>
                            <option value="Requested" <?php if ($appointmentData['status'] === 'Requested') echo 'selected'; ?>>Requested</option>
                            <option value="Scheduled" <?php if ($appointmentData['status'] === 'Scheduled') echo 'selected'; ?>>Scheduled</option>
                            <option value="Completed" <?php if ($appointmentData['status'] === 'Completed') echo 'selected'; ?>>Completed</option>
                            <option value="No Show" <?php if ($appointmentData['status'] === 'No Show') echo 'selected'; ?>>No Show</option>
                            <option value="Cancelled" <?php if ($appointmentData['status'] === 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                        </select>
                    </div>

                    <button type="submit"
                        class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        Save Changes
                    </button>
                </form>
            </div>
        <?php elseif (isset($_GET['id'])): ?>
            <p class="text-gray-600">Loading appointment information...</p>
        <?php else: ?>
            <p class="text-gray-600">No appointment selected for editing.</p>
        <?php endif; ?>

    </div>

    <script>
        // Initialize flatpickr for datetime input
        flatpickr("#appointment_datetime", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
        });
    </script>
</body>

</html>