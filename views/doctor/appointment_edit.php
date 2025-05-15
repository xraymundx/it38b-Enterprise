<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../functions/view_appointment.php';
require_once __DIR__ . '/../../functions/edit_appointment.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $appointmentId = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
    $patientId = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
    $doctorId = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
    $date = isset($_POST['date']) ? $_POST['date'] : '';
    $time = isset($_POST['time']) ? $_POST['time'] : '';
    $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'Scheduled';

    // Validate data
    $errors = [];
    if ($appointmentId <= 0)
        $errors[] = "Invalid appointment ID";
    if ($patientId <= 0)
        $errors[] = "Please select a patient";
    if ($doctorId <= 0)
        $errors[] = "Please select a doctor";
    if (empty($date))
        $errors[] = "Please select a date";
    if (empty($time))
        $errors[] = "Please select a time";
    if (empty($reason))
        $errors[] = "Reason for visit is required";

    if (empty($errors)) {
        // Prepare data for update function
        $data = [
            'appointment_id' => $appointmentId,
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'date' => $date,
            'time' => $time,
            'reason' => $reason,
            'notes' => $notes,
            'status' => $status
        ];

        // Call update function
        $result = update_appointment($data);

        if ($result['success']) {
            // Redirect to view page on success
            header("Location: /it38b-Enterprise/views/nurse/appointment_view.php?id={$appointmentId}&success=Appointment+updated+successfully");
            exit();
        } else {
            // Display error message
            $errorMessage = $result['error'];
        }
    } else {
        $errorMessage = implode("<br>", $errors);
    }
}

// Check if ID is provided and valid
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $appointmentId = intval($_GET['id']);

    // Fetch appointment data
    $appointment = get_appointment_by_id($appointmentId);

    // Check if appointment was found
    if (!$appointment) {
        header("Location: /it38b-Enterprise/views/nurse/appointments_all.php?error=Appointment+not+found");
        exit();
    }

    // Parse appointment date and time
    $appointmentDateTime = new DateTime($appointment['appointment_datetime']);
    $date = $appointmentDateTime->format('Y-m-d');
    $time = $appointmentDateTime->format('H:i');
} else {
    header("Location: /it38b-Enterprise/views/nurse/appointments_all.php?error=Invalid+appointment+ID");
    exit();
}

// Get error or success message from query params
$error = isset($_GET['error']) ? $_GET['error'] : '';
$success = isset($_GET['success']) ? $_GET['success'] : '';
if (isset($errorMessage))
    $error = $errorMessage;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Appointment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Edit Appointment</h1>
                <a href="/it38b-Enterprise/views/nurse/appointment_view.php?id=<?php echo $appointmentId; ?>"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Details
                </a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6"
                    role="alert">
                    <strong class="font-bold">Success!</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="space-y-6">
                <input type="hidden" name="appointment_id" value="<?php echo $appointmentId; ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Patient Selection -->
                    <div>
                        <label for="patient_id" class="block text-sm font-medium text-gray-700 mb-1">Patient</label>
                        <select name="patient_id" id="patient_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Patient</option>
                            <?php
                            // Fetch all patients
                            $patientQuery = "SELECT p.patient_id, u.first_name, u.last_name 
                                            FROM patients p
                                            JOIN users u ON p.user_id = u.user_id
                                            ORDER BY u.last_name, u.first_name";
                            $patientResult = mysqli_query($conn, $patientQuery);
                            while ($patient = mysqli_fetch_assoc($patientResult)) {
                                $selected = ($patient['patient_id'] == $appointment['patient_id']) ? 'selected' : '';
                                echo "<option value='" . $patient['patient_id'] . "' $selected>" .
                                    htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Doctor Selection -->
                    <div>
                        <label for="doctor_id" class="block text-sm font-medium text-gray-700 mb-1">Doctor</label>
                        <select name="doctor_id" id="doctor_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Doctor</option>
                            <?php
                            // Fetch all doctors
                            $doctorQuery = "SELECT d.doctor_id, u.first_name, u.last_name, s.specialization_name
                                           FROM doctors d
                                           JOIN users u ON d.user_id = u.user_id
                                           JOIN specializations s ON d.specialization_id = s.specialization_id
                                           ORDER BY u.last_name, u.first_name";
                            $doctorResult = mysqli_query($conn, $doctorQuery);
                            while ($doctor = mysqli_fetch_assoc($doctorResult)) {
                                $selected = ($doctor['doctor_id'] == $appointment['doctor_id']) ? 'selected' : '';
                                echo "<option value='" . $doctor['doctor_id'] . "' $selected>Dr. " .
                                    htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) .
                                    " (" . htmlspecialchars($doctor['specialization_name']) . ")</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Date -->
                    <div>
                        <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                        <input type="date" name="date" id="date" value="<?php echo htmlspecialchars($date); ?>" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Time -->
                    <div>
                        <label for="time" class="block text-sm font-medium text-gray-700 mb-1">Time</label>
                        <input type="time" name="time" id="time" value="<?php echo htmlspecialchars($time); ?>" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" id="status" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <?php
                            $statuses = ['Requested', 'Scheduled', 'Completed', 'No Show', 'Cancelled'];
                            foreach ($statuses as $statusOption) {
                                $selected = ($statusOption == $appointment['status']) ? 'selected' : '';
                                echo "<option value='$statusOption' $selected>$statusOption</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- Reason for Visit -->
                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">Reason for Visit</label>
                    <textarea name="reason" id="reason" rows="3" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($appointment['reason_for_visit']); ?></textarea>
                </div>

                <!-- Notes -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" id="notes" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($appointment['notes']); ?></textarea>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-between pt-4 border-t">
                    <a href="/it38b-Enterprise/views/nurse/appointment_view.php?id=<?php echo $appointmentId; ?>"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Update Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>