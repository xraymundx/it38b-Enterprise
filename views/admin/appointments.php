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

// Fetch all appointments with related details
$appointmentsQuery = "SELECT
                            a.appointment_id,
                            a.appointment_datetime,
                            a.status,
                            a.reason_for_visit,
                            p.patient_id,
                            u_p.first_name AS patient_first_name,
                            u_p.last_name AS patient_last_name,
                            d.doctor_id,
                            u_d.first_name AS doctor_first_name,
                            u_d.last_name AS doctor_last_name
                        FROM appointments a
                        JOIN patients p ON a.patient_id = p.patient_id
                        JOIN users u_p ON p.user_id = u_p.user_id
                        JOIN doctors d ON a.doctor_id = d.doctor_id
                        JOIN users u_d ON d.user_id = u_d.user_id
                        ORDER BY a.appointment_datetime DESC";
$appointmentsResult = mysqli_query($conn, $appointmentsQuery);
$appointments = mysqli_fetch_all($appointmentsResult, MYSQLI_ASSOC);

// Fetch all patients for the dropdown
$patientsQuery = "SELECT p.patient_id, u.first_name, u.last_name
                    FROM patients p
                    JOIN users u ON p.user_id = u.user_id
                    ORDER BY u.last_name, u.first_name";
$patientsResult = mysqli_query($conn, $patientsQuery);
$patients = mysqli_fetch_all($patientsResult, MYSQLI_ASSOC);

// Fetch all doctors for the dropdown
$doctorsQuery = "SELECT d.doctor_id, u.first_name, u.last_name, s.specialization_name
                   FROM doctors d
                   JOIN users u ON d.user_id = u.user_id
                   JOIN specializations s ON d.specialization_id = s.specialization_id
                   ORDER BY u.last_name, u.first_name";
$doctorsResult = mysqli_query($conn, $doctorsQuery);
$doctors = mysqli_fetch_all($doctorsResult, MYSQLI_ASSOC);

// Handle form submission for adding a new appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_appointment'])) {
    $patientId = intval($_POST['patient_id']);
    $doctorId = intval($_POST['doctor_id']);
    $appointmentDatetime = mysqli_real_escape_string($conn, $_POST['appointment_datetime']);
    $reasonForVisit = mysqli_real_escape_string($conn, $_POST['reason_for_visit']);

    if (empty($patientId) || empty($doctorId) || empty($appointmentDatetime)) {
        $errorMessage = "Patient, Doctor, and Appointment Date/Time are required.";
    } else {
        $insertQuery = "INSERT INTO appointments (patient_id, doctor_id, appointment_datetime, reason_for_visit)
                        VALUES ($patientId, $doctorId, '$appointmentDatetime', '$reasonForVisit')";
        if (mysqli_query($conn, $insertQuery)) {
            $successMessage = "Appointment added successfully!";
            // Refresh appointments list
            $appointmentsResult = mysqli_query($conn, $appointmentsQuery);
            $appointments = mysqli_fetch_all($appointmentsResult, MYSQLI_ASSOC);
        } else {
            $errorMessage = "Error adding appointment: " . mysqli_error($conn);
        }
    }
}

// Handle deletion of an appointment
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);
    $deleteQuery = "DELETE FROM appointments WHERE appointment_id = $deleteId";
    if (mysqli_query($conn, $deleteQuery)) {
        $successMessage = "Appointment deleted successfully!";
        // Refresh appointments list
        $appointmentsResult = mysqli_query($conn, $appointmentsQuery);
        $appointments = mysqli_fetch_all($appointmentsResult, MYSQLI_ASSOC);
    } else {
        $errorMessage = "Error deleting appointment: " . mysqli_error($conn);
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments</title>
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
        <h1 class="text-3xl font-semibold mb-6 text-gray-800">Manage Appointments</h1>

        <?php if (isset($successMessage)): ?>
            <div class="bg-green-200 border-green-600 text-green-600 px-4 py-3 rounded-md mb-4" role="alert">
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($errorMessage)): ?>
            <div class="bg-red-200 border-red-600 text-red-600 px-4 py-3 rounded-md mb-4" role="alert">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <div class="mb-6">
            <button data-modal-target="add-appointment-modal" data-modal-toggle="add-appointment-modal"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Add New Appointment
            </button>
        </div>

        <div class="overflow-x-auto bg-white rounded-lg shadow-md">
            <table class="min-w-full leading-normal">
                <thead class="bg-gray-100">
                    <tr>
                        <th
                            class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            ID
                        </th>
                        <th
                            class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Patient
                        </th>
                        <th
                            class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Doctor
                        </th>
                        <th
                            class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Date & Time
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
                    <?php foreach ($appointments as $appointment): ?>
                        <tr>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <?php echo htmlspecialchars($appointment['appointment_id']); ?>
                            </td>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <?php echo htmlspecialchars($appointment['patient_first_name']) . ' ' . htmlspecialchars($appointment['patient_last_name']); ?>
                            </td>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <?php echo htmlspecialchars($appointment['doctor_first_name']) . ' ' . htmlspecialchars($appointment['doctor_last_name']); ?>
                            </td>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <?php echo htmlspecialchars(date('F j, Y h:i A', strtotime($appointment['appointment_datetime']))); ?>
                            </td>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <?php echo htmlspecialchars($appointment['reason_for_visit']); ?>
                            </td>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <span class="inline-block py-1 px-2 rounded-full text-xs font-semibold <?php
                                switch ($appointment['status']) {
                                    case 'Requested':
                                        echo 'bg-yellow-200 text-yellow-700';
                                        break;
                                    case 'Scheduled':
                                        echo 'bg-blue-200 text-blue-700';
                                        break;
                                    case 'Completed':
                                        echo 'bg-green-200 text-green-700';
                                        break;
                                    case 'No Show':
                                        echo 'bg-gray-300 text-gray-700';
                                        break;
                                    case 'Cancelled':
                                        echo 'bg-red-200 text-red-700';
                                        break;
                                    default:
                                        echo 'bg-gray-200 text-gray-700';
                                        break;
                                }
                                ?>">
                                    <?php echo htmlspecialchars($appointment['status']); ?>
                                </span>
                            </td>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <a href="?page=edit_appointment&id=<?php echo $appointment['appointment_id']; ?>"
                                    class="text-indigo-600 hover:text-indigo-900 mr-2"><i class="fas fa-edit"></i> Edit</a>
                                <a href="?page=appointments&delete_id=<?php echo $appointment['appointment_id']; ?>"
                                    class="text-red-600 hover:text-red-900"
                                    onclick="return confirm('Are you sure you want to delete this appointment?');"><i
                                        class="fas fa-trash-alt"></i> Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="add-appointment-modal" tabindex="-1" aria-hidden="true"
        class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Add New Appointment
                    </h3>
                    <button type="button"
                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white"
                        data-modal-hide="add-appointment-modal">
                        <span class="material-symbols-outlined">close</span>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <div class="p-4 md:p-5">
                    <form class="space-y-4" method="post" action="">
                        <input type="hidden" name="add_appointment" value="1">
                        <div>
                            <label for="patient_id"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Patient</label>
                            <select id="patient_id" name="patient_id"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                required>
                                <option value="" selected>Select Patient</option>
                                <?php foreach ($patients as $patient): ?>
                                    <option value="<?php echo htmlspecialchars($patient['patient_id']); ?>">
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
                                <option value="" selected>Select Doctor</option>
                                <?php foreach ($doctors as $doctor): ?>
                                    <option value="<?php echo htmlspecialchars($doctor['doctor_id']); ?>">
                                        <?php echo htmlspecialchars($doctor['first_name']) . ' ' . htmlspecialchars($doctor['last_name']) . ' (' . htmlspecialchars($doctor['specialization_name']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="appointment_datetime"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Date and
                                Time</label>
                            <input type="datetime-local" name="appointment_datetime" id="appointment_datetime"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                placeholder="Select date and time" required>
                        </div>
                        <div>
                            <label for="reason_for_visit"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Reason for
                                Visit</label>
                            <textarea id="reason_for_visit" name="reason_for_visit" rows="3"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                placeholder="Briefly describe the reason for the appointment"></textarea>
                        </div>
                        <button type="submit"
                            class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                            Add Appointment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Flowbite modals
        import { Modal } from 'flowbite';

        const $modalElement = document.getElementById('add-appointment-modal');
        const modal = new Modal($modalElement);

        // Initialize flatpickr for datetime input
        flatpickr("#appointment_datetime", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
        });
    </script>
</body>

</html>