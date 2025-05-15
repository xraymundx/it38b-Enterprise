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
// Include database connection
require_once __DIR__ . '/../../config/config.php';

// Fetch the doctor's ID from the session
$doctorId = $_SESSION['doctor_id'] ?? null;
if (!$doctorId) {
    // Handle the case where doctor_id is not set
    die("Error: Doctor ID not found in session.");
}

// Fetch all patients to populate the dropdown
$queryPatients = "SELECT patient_id, u.first_name, u.last_name
                   FROM patients p
                   JOIN users u ON p.user_id = u.user_id
                   ORDER BY u.last_name, u.first_name";
$resultPatients = mysqli_query($conn, $queryPatients);
$patients = mysqli_fetch_all($resultPatients, MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientId = $_POST['patient_id'] ?? null;
    $appointmentDate = $_POST['appointment_date'] ?? null;
    $appointmentTime = $_POST['appointment_time'] ?? null; // This will be the 24-hour format
    $reasonForVisit = $_POST['reason_for_visit'] ?? null;

    if ($patientId && $appointmentDate && $appointmentTime && $reasonForVisit) {
        $appointmentDatetime = $appointmentDate . ' ' . $appointmentTime;

        $queryInsert = "INSERT INTO appointments (doctor_id, patient_id, appointment_datetime, reason_for_visit, status)
                        VALUES (?, ?, ?, ?, 'Scheduled')"; // Default status is 'Scheduled' upon creation

        $stmtInsert = mysqli_prepare($conn, $queryInsert);
        mysqli_stmt_bind_param($stmtInsert, "iiiss", $doctorId, $patientId, $appointmentDatetime, $reasonForVisit, 'Scheduled');

        if (mysqli_stmt_execute($stmtInsert)) {
            // Appointment created successfully
            $_SESSION['success_message'] = "Appointment created successfully!";
            header('Location: ?page=appointments'); // Redirect back to the appointments list
            exit();
        } else {
            // Error during insertion
            $error_message = "Error creating appointment: " . mysqli_error($conn);
        }

        mysqli_stmt_close($stmtInsert);
    } else {
        $error_message = "Please fill in all the required fields.";
    }
}

mysqli_close($conn);
?>

<div class="container mx-auto p-6 bg-white shadow-md rounded-md">
    <h2 class="text-2xl font-semibold mb-6 text-gray-800">
        <i class="fas fa-calendar-plus mr-2"></i> Schedule Appointment
    </h2>

    <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-md">
            <p><i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($error_message); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" class="space-y-4" x-data="scheduleAppointment()">
        <div>
            <label for="patient_id" class="block text-gray-700 text-sm font-bold mb-2">
                Patient:
            </label>
            <select id="patient_id" name="patient_id"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <option value="">Select a Patient</option>
                <?php foreach ($patients as $patient): ?>
                    <option value="<?php echo htmlspecialchars($patient['patient_id']); ?>">
                        <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="appointment_date" class="block text-gray-700 text-sm font-bold mb-2">
                Date:
            </label>
            <input type="date" id="appointment_date" name="appointment_date" x-model="formData.appointment_date"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                @change="loadAvailableTimes">
            <p class="text-gray-500 text-xs italic">Please select the date for the appointment.</p>
        </div>

        <div>
            <label for="appointment_time" class="block text-gray-700 text-sm font-bold mb-2">
                Time:
            </label>
            <select id="appointment_time" name="appointment_time" x-model="formData.appointment_time"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <option value="">Select a Time</option>
                <template x-for="timeSlot in availableTimes" :key="timeSlot">
                    <option :value="timeSlot" x-text="timeSlot"></option>
                </template>
                <option x-show="availableTimes.length === 0 && formData.appointment_date" disabled>
                    No available slots for this date.
                </option>
            </select>
            <p class="text-gray-500 text-xs italic">Please select the time for the appointment.</p>
        </div>

        <div>
            <label for="reason_for_visit" class="block text-gray-700 text-sm font-bold mb-2">
                Reason for Visit:
            </label>
            <textarea id="reason_for_visit" name="reason_for_visit" rows="3"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
        </div>

        <div class="flex items-center justify-end">
            <button type="submit"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                <i class="fas fa-save mr-2"></i> Schedule Appointment
            </button>
            <a href="?page=appointments"
                class="inline-block bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded ml-2 focus:outline-none focus:shadow-outline">
                <i class="fas fa-times mr-2"></i> Cancel
            </a>
        </div>
    </form>
</div>

<script>
    function scheduleAppointment() {
        return {
            formData: {
                patient_id: '',
                appointment_date: '',
                appointment_time: '', // Will store 24-hour format
                reason_for_visit: '',
            },
            availableTimes: [],

            loadAvailableTimes() {
                if (!this.formData.appointment_date) {
                    this.availableTimes = [];
                    return;
                }

                const doctorId = <?php echo $doctorId; ?>;
                const selectedDate = this.formData.appointment_date;
                const availabilityUrl = `/it38b-enterprise/api/doctor/availability.php?doctor_id=${doctorId}&date=${selectedDate}`;

                fetch(availabilityUrl)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.timeSlots) {
                            this.availableTimes = data.timeSlots; // Expecting an array of 24-hour format times
                        } else {
                            this.availableTimes = [];
                            if (this.formData.appointment_date) {
                                alert('No available time slots for this date.');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching availability:', error);
                        alert('Failed to load available time slots.');
                        this.availableTimes = [];
                    });
            },

            init() {
                // Optionally load initial times if a date is pre-selected
            }
        };
    }
</script>