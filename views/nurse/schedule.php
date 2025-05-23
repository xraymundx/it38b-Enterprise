<?php
// Include the configuration file
require '../config/config.php';
require_once __DIR__ . '/../../config/config.php';
// Check if user is logged in and is a nurse
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'nurse') {
    header('Location: /login.php');
    exit();
}

// Ensure database connection is established
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection not established properly. Please check your configuration.");
}

// Initialize selectedDate and ensure consistent formatting
$selectedDate = null;
$dayOfWeek = null;
$appointments = []; // Initialize appointments array

// Check if the 'date' parameter is provided via GET
if (isset($_GET['date'])) {
    // Get the selected date from the URL and format it to Y-m-d
    $selectedDate = date('Y-m-d', strtotime($_GET['date']));
    // Get the day of the week for the selected date
    $dayOfWeek = date('l', strtotime($selectedDate));
}

// Fetch doctors with their specializations
$doctorsQuery = "SELECT d.doctor_id, d.user_id, u.first_name, u.last_name, s.specialization_name
                FROM doctors d
                JOIN users u ON d.user_id = u.user_id
                JOIN specializations s ON d.specialization_id = s.specialization_id
                ORDER BY u.last_name, u.first_name";

try {
    $doctorsResult = mysqli_query($conn, $doctorsQuery);
    if (!$doctorsResult) {
        throw new Exception("Error fetching doctors: " . mysqli_error($conn));
    }
    $doctors = mysqli_fetch_all($doctorsResult, MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $doctors = [];
}

// Fetch doctor schedules for the selected day
$doctorSchedules = [];
if ($dayOfWeek) {
    $scheduleQuery = "SELECT ds.*, d.user_id, u.first_name, u.last_name
                     FROM doctor_schedule ds
                     JOIN doctors d ON ds.doctor_id = d.doctor_id
                     JOIN users u ON d.user_id = u.user_id
                     WHERE ds.day_of_week = ? AND ds.is_available = 1";
    try {
        $scheduleStmt = mysqli_prepare($conn, $scheduleQuery);
        if (!$scheduleStmt) {
            throw new Exception("Error preparing schedule query: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($scheduleStmt, "s", $dayOfWeek);
        mysqli_stmt_execute($scheduleStmt);
        $scheduleResult = mysqli_stmt_get_result($scheduleStmt);
        while ($row = mysqli_fetch_assoc($scheduleResult)) {
            $doctorSchedules[$row['doctor_id']] = $row;
        }
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
    }
}

// Fetch doctor availability exceptions for the selected date
$doctorExceptions = [];
if ($selectedDate) {
    $exceptionsQuery = "SELECT dae.*, d.user_id, u.first_name, u.last_name
                       FROM doctor_availability_exceptions dae
                       JOIN doctors d ON dae.doctor_id = d.doctor_id
                       JOIN users u ON d.user_id = u.user_id
                       WHERE dae.exception_date = ?";
    try {
        $exceptionsStmt = mysqli_prepare($conn, $exceptionsQuery);
        if (!$exceptionsStmt) {
            throw new Exception("Error preparing exceptions query: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($exceptionsStmt, "s", $selectedDate);
        mysqli_stmt_execute($exceptionsStmt);
        $exceptionsResult = mysqli_stmt_get_result($exceptionsStmt);
        while ($row = mysqli_fetch_assoc($exceptionsResult)) {
            $doctorExceptions[$row['doctor_id']] = $row;
        }
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Schedule Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://unpkg.com/alpinejs" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8" x-data="scheduleManager()">
        <div class="mb-8">
            <label for="calendar" class="block text-sm font-medium text-gray-700 mb-1">Select Date:</label>
            <input type="text" id="calendar"
                class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Please select a date from the calendar">
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-2xl font-bold mb-4">Select Doctor</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($doctors as $doctor):
                    $schedule = $doctorSchedules[$doctor['doctor_id']] ?? null;
                    $exception = $doctorExceptions[$doctor['doctor_id']] ?? null;
                    $isAvailable = ($schedule && !$exception) || ($exception && $exception['is_available']);
                    ?>
                    <div @click="selectDoctor('<?= $doctor['doctor_id'] ?>')"
                        :class="{'ring-2 ring-blue-500 bg-blue-50': selectedDoctor === '<?= $doctor['doctor_id'] ?>'}"
                        class="p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors duration-200">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-semibold">
                                <?= strtoupper(substr($doctor['first_name'], 0, 1) . substr($doctor['last_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <h3 class="font-semibold text-lg">
                                    Dr. <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?>
                                </h3>
                                <p class="text-gray-600 text-sm">
                                    <?= htmlspecialchars($doctor['specialization_name']) ?>
                                </p>
                                <?php if ($schedule): ?>
                                    <p class="text-sm text-gray-500 mt-1">
                                        Available: <?= date('h:i A', strtotime($schedule['start_time'])) ?> -
                                        <?= date('h:i A', strtotime($schedule['end_time'])) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 mb-8" x-show="selectedDoctor">
            <h2 class="text-2xl font-bold mb-4">Doctor Availability</h2>

            <div class="mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Doctor's Schedule</h3>
                    <div class="flex gap-2">
                        <button @click="markDoctorUnavailable()"
                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-md text-sm transition duration-200">
                            <i class="fas fa-calendar-times mr-1"></i> Mark Unavailable for This Day
                        </button>
                        <button @click="resetScheduleToDefault()" x-show="isExceptionDay"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded-md text-sm transition duration-200">
                            <i class="fas fa-undo mr-1"></i> Reset to Default Schedule
                        </button>
                    </div>
                </div>

                <div x-show="dayNotes" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2h-1V9z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700" x-text="dayNotes"></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-8">
                <h3 class="text-lg font-semibold mb-4">Available Time Slots</h3>
                <div class="grid grid-cols-4 gap-4">
                    <?php
                    $startTime = strtotime('07:00');
                    $endTime = strtotime('18:00');
                    for ($time = $startTime; $time <= $endTime; $time += 3600) {
                        $timeStr = date('h:i A', $time);
                        ?>
                        <button @click="toggleTimeSlot('<?= $timeStr ?>')"
                            :class="{'bg-green-500 text-white': selectedSlots.includes('<?= $timeStr ?>'), 'bg-gray-100 hover:bg-gray-200': !selectedSlots.includes('<?= $timeStr ?>')}"
                            class="flex items-center justify-center gap-2 p-3 rounded-lg transition-colors duration-200">
                            <span><?= $timeStr ?></span>
                            <span class="text-lg" x-text="selectedSlots.includes('<?= $timeStr ?>') ? '−' : '+'"></span>
                        </button>
                    <?php } ?>
                </div>
            </div>

            <div class="mb-6">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-lg font-semibold">Selected Time Slots</h3>
                    <span class="text-sm text-gray-500" x-show="selectedSlots.length > 0">
                        Click a slot to remove it
                    </span>
                </div>
                <div class="flex flex-wrap gap-2">
                    <template x-for="slot in selectedSlots" :key="slot">
                        <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded-full flex items-center gap-2">
                            <span x-text="slot"></span>
                            <button @click="removeTimeSlot(slot)"
                                class="text-blue-600 hover:text-blue-800 hover:bg-blue-200 rounded-full p-1 transition-colors duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </template>
                    <div x-show="selectedSlots.length === 0" class="text-gray-500 italic">
                        No time slots selected
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label for="schedule-notes" class="block text-sm font-medium text-gray-700 mb-1">Notes
                    (Optional)</label>
                <textarea id="schedule-notes" x-model="scheduleNotes"
                    class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Add notes about this schedule (e.g., 'Doctor attending conference')"></textarea>
            </div>

            <div class="flex gap-4">
                <button @click="saveTimeSlots()"
                    class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-colors duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Save Settings
                </button>
                <button @click="clearAllSlots()"
                    class="bg-red-500 text-white px-6 py-2 rounded-lg hover:bg-red-600 transition-colors duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                        </path>
                    </svg>
                    Clear All
                </button>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold" x-text="'Appointments for ' + formatDate(currentDate)"></h2>
                <button @click="showAddModal = true"
                    class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Appointment
                </button>
            </div>

            <div x-show="loading" class="text-center py-4">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto"></div>
            </div>

            <div x-show="!loading">
                <div x-show="appointments.length === 0" class="text-gray-500">
                    No appointments scheduled for this date.
                </div>

                <div x-show="appointments.length > 0" class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="appointment in appointments" :key="appointment.appointment_id">
                                <tr>
                                    <td class="px-6 py-4"
                                        x-text="appointment.patient_first_name + ' ' + appointment.patient_last_name">
                                    </td>
                                    <td class="px-6 py-4"
                                        x-text="'Dr. ' + appointment.doctor_first_name + ' ' + appointment.doctor_last_name">
                                    </td>
                                    <td class="px-6 py-4" x-text="formatTime(appointment.appointment_datetime)"></td>
                                    <td class="px-6 py-4" x-text="appointment.reason_for_visit"></td>
                                    <td class="px-6 py-4">
                                        <span :class="getStatusClass(appointment.status)"
                                            x-text="appointment.status"></span>
                                    </td>
                                    <td class="px-6 py-4" x-text="appointment.notes || ''"></td>
                                    <td class="px-6 py-4">
                                        <div class="flex space-x-2">
                                            <button @click="viewAppointment(appointment.appointment_id)"
                                                class="text-blue-600 hover:text-blue-800">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <template x-if="appointment.status === 'Requested'">
                                                <div class="flex space-x-2">
                                                    <button
                                                        @click="updateAppointmentStatus(appointment.appointment_id, 'Scheduled')"
                                                        class="text-green-600 hover:text-green-800">
                                                        <i class="fas fa-check"></i> Schedule
                                                    </button>
                                                    <button
                                                        @click="updateAppointmentStatus(appointment.appointment_id, 'Cancelled')"
                                                        class="text-red-600 hover:text-red-800">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </div>
                                            </template>
                                            <template x-if="appointment.status === 'Scheduled'">
                                                <div class="flex space-x-2">
                                                    <button
                                                        @click="updateAppointmentStatus(appointment.appointment_id, 'Completed')"
                                                        class="text-green-600 hover:text-green-800">
                                                        <i class="fas fa-check-double"></i> Complete
                                                    </button>
                                                    <button
                                                        @click="updateAppointmentStatus(appointment.appointment_id, 'No Show')"
                                                        class="text-red-600 hover:text-red-800">
                                                        <i class="fas fa-user-times"></i> No Show
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Appointment Modal -->
        <div x-show="showAddModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95">

                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold">Add New Appointment</h3>
                    <button @click="showAddModal = false" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitAppointment" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Doctor</label>
                        <select x-model="newAppointment.doctor_id" required
                            class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select a doctor</option>
                            <?php foreach ($doctors as $doctor): ?>
                                <option value="<?= $doctor['doctor_id'] ?>">
                                    Dr. <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?>
                                    (<?= htmlspecialchars($doctor['specialization_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                            <input type="text" x-model="newAppointment.date"
                                class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                x-ref="appointmentDate">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Time</label>
                            <select x-model="newAppointment.time" required
                                class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select a time</option>
                                <template x-for="slot in availableTimeSlots" :key="slot">
                                    <option :value="slot" x-text="slot"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Patient</label>
                        <select x-model="newAppointment.patient_id" required
                            class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select a patient</option>
                            <template x-for="patient in patients" :key="patient.patient_id">
                                <option :value="patient.patient_id"
                                    x-text="patient.first_name + ' ' + patient.last_name"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Visit</label>
                        <textarea x-model="newAppointment.reason" required
                            class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            rows="3" placeholder="Enter reason for visit"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                        <textarea x-model="newAppointment.notes"
                            class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            rows="2" placeholder="Additional notes"></textarea>
                    </div>

                    <div class="flex justify-end gap-4">
                        <button type="button" @click="showAddModal = false"
                            class="px-4 py-2 border rounded-lg hover:bg-gray-50 transition-colors duration-200">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors duration-200">
                            Schedule Appointment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function scheduleManager() {
            return {
                selectedDoctor: '',
                selectedSlots: [],
                currentDate: "<?= $selectedDate ?>",
                appointments: [],
                loading: false,
                showAddModal: false,
                patients: [],
                availableTimeSlots: [],
                scheduleNotes: '',
                dayNotes: '',
                isExceptionDay: false,
                newAppointment: {
                    doctor_id: '',
                    patient_id: '',
                    date: '',
                    time: '',
                    reason: '',
                    notes: ''
                },

                init() {
                    const initialDate = "<?= $selectedDate ?>";
                    this.currentDate = initialDate || new Date().toISOString().split('T')[0];

                    flatpickr("#calendar", {
                        dateFormat: "Y-m-d",
                        defaultDate: this.currentDate,
                        onChange: (selectedDates) => {
                            if (selectedDates.length > 0) {
                                const selectedDate = selectedDates[0];
                                const year = selectedDate.getFullYear();
                                const month = String(selectedDate.getMonth() + 1).padStart(2, '0');
                                const day = String(selectedDate.getDate()).padStart(2, '0');
                                const formattedDate = `${year}-${month}-${day}`;
                                this.currentDate = formattedDate;
                                this.loadAppointments();
                                if (this.selectedDoctor) {
                                    this.loadDoctorAvailability(this.selectedDoctor);
                                }
                            }
                        }
                    });

                    // Initialize appointment date picker
                    flatpickr(this.$refs.appointmentDate, {
                        dateFormat: "Y-m-d",
                        minDate: "today",
                        onChange: (selectedDates) => {
                            if (selectedDates.length > 0) {
                                const selectedDate = selectedDates[0];
                                const year = selectedDate.getFullYear();
                                const month = String(selectedDate.getMonth() + 1).padStart(2, '0');
                                const day = String(selectedDate.getDate()).padStart(2, '0');
                                this.newAppointment.date = `${year}-${month}-${day}`;
                                if (this.newAppointment.doctor_id) {
                                    this.loadAvailableTimeSlots();
                                }
                            }
                        }
                    });

                    // Load patients
                    this.loadPatients();

                    // Initial load of appointments
                    this.loadAppointments();
                },

                async loadPatients() {
                    try {
                        const response = await fetch('/it38b-Enterprise/api/patients.php');
                        if (response.ok) {
                            const data = await response.json();
                            this.patients = data.patients;
                        }
                    } catch (error) {
                        console.error('Error loading patients:', error);
                        Swal.fire('Error', 'Failed to load patients', 'error');
                    }
                },

                async loadAvailableTimeSlots() {
                    if (!this.newAppointment.doctor_id || !this.newAppointment.date) return;

                    try {
                        const response = await fetch(`/it38b-Enterprise/api/doctor/availability.php?doctor_id=${this.newAppointment.doctor_id}&date=${this.newAppointment.date}`);
                        if (response.ok) {
                            const data = await response.json();
                            this.availableTimeSlots = data.timeSlots || [];
                        }
                    } catch (error) {
                        console.error('Error loading available time slots:', error);
                        Swal.fire('Error', 'Failed to load available time slots', 'error');
                    }
                },

                async submitAppointment() {
                    if (!this.validateAppointment()) return;

                    try {
                        const response = await fetch('/it38b-Enterprise/api/appointments.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(this.newAppointment)
                        });

                        const result = await response.json();
                        if (result.success) {
                            Swal.fire('Success', 'Appointment scheduled successfully!', 'success');
                            this.showAddModal = false;
                            this.resetNewAppointment();
                            this.loadAppointments();
                        } else {
                            throw new Error(result.error || 'Failed to schedule appointment');
                        }
                    } catch (error) {
                        Swal.fire('Error', error.message, 'error');
                    }
                },

                validateAppointment() {
                    if (!this.newAppointment.doctor_id) {
                        Swal.fire('Error', 'Please select a doctor', 'error');
                        return false;
                    }
                    if (!this.newAppointment.patient_id) {
                        Swal.fire('Error', 'Please select a patient', 'error');
                        return false;
                    }
                    if (!this.newAppointment.date) {
                        Swal.fire('Error', 'Please select a date', 'error');
                        return false;
                    }
                    if (!this.newAppointment.time) {
                        Swal.fire('Error', 'Please select a time', 'error');
                        return false;
                    }
                    if (!this.newAppointment.reason) {
                        Swal.fire('Error', 'Please enter a reason for visit', 'error');
                        return false;
                    }
                    return true;
                },

                resetNewAppointment() {
                    this.newAppointment = {
                        doctor_id: '',
                        patient_id: '',
                        date: '',
                        time: '',
                        reason: '',
                        notes: ''
                    };
                    this.availableTimeSlots = [];
                },

                selectDoctor(doctorId) {
                    this.selectedDoctor = doctorId;
                    this.selectedSlots = [];
                    this.scheduleNotes = '';
                    this.dayNotes = '';
                    this.isExceptionDay = false;
                    this.loadDoctorAvailability(doctorId);
                },

                async loadAppointments() {
                    this.loading = true;
                    try {
                        const response = await fetch(`/it38b-Enterprise/api/appointments.php?date=${this.currentDate}`);
                        if (response.ok) {
                            const data = await response.json();
                            this.appointments = data.appointments || [];
                        }
                    } catch (error) {
                        console.error('Error loading appointments:', error);
                        Swal.fire('Error', 'Failed to load appointments', 'error');
                    }
                    this.loading = false;
                },

                async loadDoctorAvailability(doctorId) {
                    try {
                        const response = await fetch(`/it38b-Enterprise/api/doctor/availability.php?doctor_id=${doctorId}&date=${this.currentDate}`);
                        if (response.ok) {
                            const data = await response.json();
                            if (data.success) {
                                this.selectedSlots = data.timeSlots || [];
                                this.isExceptionDay = data.is_exception || false;
                                this.dayNotes = data.notes || '';

                                if (!data.available) {
                                    this.selectedSlots = [];
                                    if (!this.dayNotes && !data.is_exception) {
                                        this.dayNotes = "Doctor is not scheduled to work on this day.";
                                    }
                                }
                            }
                        }
                    } catch (error) {
                        console.error('Error loading doctor availability:', error);
                        Swal.fire('Error', 'Failed to load doctor availability', 'error');
                    }
                },

                toggleTimeSlot(time) {
                    const index = this.selectedSlots.indexOf(time);
                    if (index === -1) {
                        this.selectedSlots.push(time);
                    } else {
                        this.selectedSlots.splice(index, 1);
                    }
                },

                removeTimeSlot(time) {
                    const index = this.selectedSlots.indexOf(time);
                    if (index !== -1) {
                        this.selectedSlots.splice(index, 1);
                    }
                },

                clearAllSlots() {
                    Swal.fire({
                        title: 'Clear All Time Slots?',
                        text: "This will remove all selected time slots. Continue?",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, clear all'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.selectedSlots = [];
                        }
                    });
                },

                markDoctorUnavailable() {
                    Swal.fire({
                        title: 'Mark Doctor Unavailable?',
                        text: "This will mark the doctor as unavailable for the entire day. Any existing appointments will still appear but no new ones can be scheduled. Continue?",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, mark unavailable'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.selectedSlots = [];
                            this.saveTimeSlots();
                        }
                    });
                },

                resetScheduleToDefault() {
                    Swal.fire({
                        title: 'Reset to Default Schedule?',
                        text: "This will remove the exception for this day and revert to the doctor's regular schedule. Continue?",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, reset schedule'
                    }).then(async (result) => {
                        if (result.isConfirmed) {
                            try {
                                const response = await fetch(`/it38b-Enterprise/api/doctor/availability.php?doctor_id=${this.selectedDoctor}&date=${this.currentDate}`, {
                                    method: 'DELETE'
                                });

                                const data = await response.json();
                                if (data.success) {
                                    Swal.fire('Success', data.message, 'success');
                                    this.loadDoctorAvailability(this.selectedDoctor);
                                } else {
                                    throw new Error(data.error || 'Failed to reset schedule');
                                }
                            } catch (error) {
                                console.error('Error resetting schedule:', error);
                                Swal.fire('Error', error.message, 'error');
                            }
                        }
                    });
                },

                async saveTimeSlots() {
                    if (!this.selectedDoctor) {
                        Swal.fire('Error', 'Please select a doctor first', 'error');
                        return;
                    }

                    try {
                        const response = await fetch('/it38b-Enterprise/api/doctor/availability.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                doctor_id: this.selectedDoctor,
                                date: this.currentDate,
                                timeSlots: this.selectedSlots,
                                notes: this.scheduleNotes
                            })
                        });

                        const result = await response.json();
                        if (result.success) {
                            Swal.fire('Success', 'Doctor availability saved successfully!', 'success');
                            this.isExceptionDay = true;
                            this.dayNotes = this.scheduleNotes;
                        } else {
                            throw new Error(result.error || 'Failed to save doctor availability');
                        }
                    } catch (error) {
                        Swal.fire('Error', error.message, 'error');
                    }
                },

                formatDate(dateString) {
                    const date = new Date(dateString);
                    return date.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                },

                formatTime(dateTimeString) {
                    const date = new Date(dateTimeString);
                    return date.toLocaleTimeString('en-US', {
                        hour: 'numeric',
                        minute: '2-digit',
                        hour12: true
                    });
                },

                getStatusClass(status) {
                    const classes = {
                        'Requested': 'bg-yellow-100 text-yellow-800',
                        'Scheduled': 'bg-blue-100 text-blue-800',
                        'Completed': 'bg-green-100 text-green-800',
                        'No Show': 'bg-red-100 text-red-800',
                        'Cancelled': 'bg-gray-100 text-gray-800'
                    };
                    return `px-2 py-1 rounded-full text-xs font-semibold ${classes[status] || 'bg-gray-100 text-gray-800'}`;
                },

                async viewAppointment(id) {
                    try {
                        const response = await fetch(`/it38b-Enterprise/api/appointments.php?id=${id}`);
                        const data = await response.json();

                        if (data.success) {
                            const appointment = data.appointment;
                            Swal.fire({
                                title: 'Appointment Details',
                                html: `
                                    <div class="text-left">
                                        <p><strong>Date & Time:</strong> ${new Date(appointment.appointment_datetime).toLocaleString()}</p>
                                        <p><strong>Patient:</strong> ${appointment.patient_first_name} ${appointment.patient_last_name}</p>
                                        <p><strong>Doctor:</strong> Dr. ${appointment.doctor_first_name} ${appointment.doctor_last_name}</p>
                                        <p><strong>Reason:</strong> ${appointment.reason_for_visit}</p>
                                        <p><strong>Status:</strong> ${appointment.status}</p>
                                        <p><strong>Notes:</strong> ${appointment.notes || 'None'}</p>
                                    </div>
                                `,
                                confirmButtonText: 'Close'
                            });
                        } else {
                            throw new Error(data.error || 'Failed to load appointment details');
                        }
                    } catch (error) {
                        Swal.fire('Error', error.message, 'error');
                    }
                },

                async updateAppointmentStatus(id, newStatus) {
                    try {
                        const result = await Swal.fire({
                            title: 'Confirm Status Update',
                            text: `Are you sure you want to mark this appointment as ${newStatus}?`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, update it',
                            cancelButtonText: 'No, cancel'
                        });

                        if (result.isConfirmed) {
                            const response = await fetch('/it38b-Enterprise/api/appointments.php', {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    appointment_id: id,
                                    status: newStatus
                                })
                            });

                            const data = await response.json();
                            if (data.success) {
                                Swal.fire('Success', 'Appointment status updated successfully', 'success');
                                this.loadAppointments();
                            } else {
                                throw new Error(data.error || 'Failed to update appointment status');
                            }
                        }
                    } catch (error) {
                        Swal.fire('Error', error.message, 'error');
                    }
                }
            };
        }
    </script>
</body>

</html>