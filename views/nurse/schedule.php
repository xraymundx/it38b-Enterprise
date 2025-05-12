<?php

require('../config/config.php');

// Check if user is logged in and is a nurse
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'nurse') {
    header('Location: /login.php');
    exit();
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

    // List of days the clinic is closed (e.g., Sunday)
    $clinicClosedDays = ['Sunday'];

    // Check if the selected date is a clinic closed day
    if (in_array($dayOfWeek, $clinicClosedDays)) {
        echo "<p class='text-center text-gray-500 mt-8'>No clinic schedule for the selected date.</p>";
    } else {
        // Query to fetch appointments for the selected date
        $query = "SELECT a.*, p.first_name, p.last_name, d.first_name as doctor_first_name, d.last_name as doctor_last_name
                  FROM appointments a
                  JOIN patients p ON a.patient_id = p.patient_id
                  JOIN doctors d ON a.doctor_id = d.doctor_id
                  WHERE DATE(a.appointment_datetime) = ?
                  ORDER BY a.appointment_datetime";

        // Prepare and execute the SQL query
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $selectedDate); // Use $selectedDate directly
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        // Fetch appointments as an associative array
        $appointments = mysqli_fetch_all($result, MYSQLI_ASSOC);

        // You can now use the $appointments variable to display the fetched data
        // Example:
        // foreach ($appointments as $appointment) {
        //     echo "Patient: " . $appointment['first_name'] . " " . $appointment['last_name'];
        // }
    }
} else {
    echo "No date selected.";
}

$doctorsQuery = "SELECT d.doctor_id, d.user_id, u.first_name, u.last_name, s.specialization_name
                  FROM doctors d
                  JOIN users u ON d.user_id = u.user_id
                  JOIN specializations s ON d.specialization_id = s.specialization_id
                  ORDER BY u.last_name, u.first_name";
$doctorsResult = mysqli_query($conn, $doctorsQuery);
$doctors = mysqli_fetch_all($doctorsResult, MYSQLI_ASSOC);

// Ensure $dayOfWeek is not null before running the query
$doctorSchedules = [];
if ($dayOfWeek) {
    $scheduleQuery = "SELECT ds.*, d.user_id, u.first_name, u.last_name
                      FROM doctor_schedule ds
                      JOIN doctors d ON ds.doctor_id = d.doctor_id
                      JOIN users u ON d.user_id = u.user_id
                      WHERE ds.day_of_week = ? AND ds.is_available = 1";
    $scheduleStmt = mysqli_prepare($conn, $scheduleQuery);
    mysqli_stmt_bind_param($scheduleStmt, "s", $dayOfWeek); // Use $dayOfWeek based on $selectedDate
    mysqli_stmt_execute($scheduleStmt);
    $scheduleResult = mysqli_stmt_get_result($scheduleStmt);
    while ($row = mysqli_fetch_assoc($scheduleResult)) {
        $doctorSchedules[$row['doctor_id']] = $row;
    }
}

$doctorExceptions = [];
if ($selectedDate) {
    $exceptionsQuery = "SELECT dae.*, d.user_id, u.first_name, u.last_name
                        FROM doctor_availability_exceptions dae
                        JOIN doctors d ON dae.doctor_id = d.doctor_id
                        JOIN users u ON d.user_id = u.user_id
                        WHERE dae.exception_date = ?";
    $exceptionsStmt = mysqli_prepare($conn, $exceptionsQuery);
    mysqli_stmt_bind_param($exceptionsStmt, "s", $selectedDate); // Use $selectedDate directly
    mysqli_stmt_execute($exceptionsStmt);
    $exceptionsResult = mysqli_stmt_get_result($exceptionsStmt);
    while ($row = mysqli_fetch_assoc($exceptionsResult)) {
        $doctorExceptions[$row['doctor_id']] = $row;
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
</head>

<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8" x-data="scheduleManager()">
        <div class="mb-8">
            <label for="calendar" class="block text-sm font-medium text-gray-700 mb-1">Select Date:</label>
            <input type="text" id="calendar" class="w-full p-2 border rounded"
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
                <h3 class="text-lg font-semibold mb-4">Selected Time Slots</h3>
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="appointment in appointments" :key="appointment.appointment_id">
                                <tr>
                                    <td class="px-6 py-4" x-text="appointment.first_name + ' ' + appointment.last_name">
                                    </td>
                                    <td class="px-6 py-4"
                                        x-text="'Dr. ' + appointment.doctor_first_name + ' ' + appointment.doctor_last_name">
                                    </td>
                                    <td class="px-6 py-4" x-text="formatTime(appointment.appointment_datetime)"></td>
                                    <td class="px-6 py-4" x-text="appointment.reason_for_visit"></td>
                                    <td class="px-6 py-4">
                                        <span :class="getStatusClass(appointment.status)"
                                            x-text="capitalizeFirst(appointment.status)"></span>
                                    </td>
                                    <td class="px-6 py-4" x-text="appointment.notes || ''"></td>
                                    <td class="px-6 py-4" x-text="formatDate(appointment.created_at)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

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
                appointments: <?= json_encode($appointments) ?>,
                loading: false,
                showAddModal: false,
                patients: [],
                availableTimeSlots: [],
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
                    console.log('Initial currentDate:', this.currentDate);
                    flatpickr("#calendar", {
                        dateFormat: "Y-m-d",
                        defaultDate: this.currentDate,
                        onChange: (selectedDates) => {
                            console.log('Selected dates from Flatpickr:', selectedDates);
                            if (selectedDates.length > 0) {
                                const selectedDate = selectedDates[0];
                                const year = selectedDate.getFullYear();
                                const month = String(selectedDate.getMonth() + 1).padStart(2, '0'); // Month is 0-indexed
                                const day = String(selectedDate.getDate()).padStart(2, '0');
                                const formattedDate = `${year}-${month}-${day}`;
                                console.log('Formatted date:', formattedDate);
                                this.currentDate = formattedDate;
                                console.log('Updated currentDate:', this.currentDate);
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

                    // Initial load of appointments based on the initial currentDate
                    this.loadAppointments();
                },

                async loadPatients() {
                    try {
                        const response = await fetch('../api/patients.php');
                        if (response.ok) {
                            const data = await response.json();
                            this.patients = data.patients;
                        }
                    } catch (error) {
                        console.error('Error loading patients:', error);
                    }
                },

                async loadAvailableTimeSlots() {
                    if (!this.newAppointment.doctor_id || !this.newAppointment.date) return;

                    try {
                        const response = await fetch(`../api/doctor/availability.php?doctor_id=${this.newAppointment.doctor_id}&date=${this.newAppointment.date}`);
                        if (response.ok) {
                            const data = await response.json();
                            this.availableTimeSlots = data.timeSlots || [];
                        }
                    } catch (error) {
                        console.error('Error loading available time slots:', error);
                    }
                },

                async submitAppointment() {
                    if (!this.validateAppointment()) return;

                    try {
                        const response = await fetch('../api/appointments.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(this.newAppointment)
                        });

                        if (response.ok) {
                            this.showAddModal = false;
                            this.resetNewAppointment();
                            this.loadAppointments();
                            alert('Appointment scheduled successfully!');
                        } else {
                            throw new Error('Failed to schedule appointment');
                        }
                    } catch (error) {
                        alert('Error scheduling appointment: ' + error.message);
                    }
                },

                validateAppointment() {
                    if (!this.newAppointment.doctor_id) {
                        alert('Please select a doctor');
                        return false;
                    }
                    if (!this.newAppointment.patient_id) {
                        alert('Please select a patient');
                        return false;
                    }
                    if (!this.newAppointment.date) {
                        alert('Please select a date');
                        return false;
                    }
                    if (!this.newAppointment.time) {
                        alert('Please select a time');
                        return false;
                    }
                    if (!this.newAppointment.reason) {
                        alert('Please enter a reason for visit');
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
                    this.selectedSlots = []; // Clear selected slots when changing doctor
                    this.loadDoctorAvailability(doctorId);
                },

                async loadAppointments() {
                    this.loading = true;
                    try {
                        const response = await fetch(`../api/appointments.php?date=${this.currentDate}`);
                        if (response.ok) {
                            const data = await response.json();
                            this.appointments = data.appointments;
                        }
                    } catch (error) {
                        console.error('Error loading appointments:', error);
                    }
                    this.loading = false;
                },

                async loadDoctorAvailability(doctorId) {
                    try {
                        const response = await fetch(`../api/doctor/availability.php?doctor_id=${doctorId}&date=${this.currentDate}`);
                        if (response.ok) {
                            const data = await response.json();
                            this.selectedSlots = data.timeSlots || [];
                        }
                    } catch (error) {
                        console.error('Error loading doctor availability:', error);
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
                    this.selectedSlots = [];
                },

                async saveTimeSlots() {
                    if (!this.selectedDoctor) {
                        alert('Please select a doctor first');
                        return;
                    }

                    try {
                        const response = await fetch('../api/doctor/availability.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                doctor_id: this.selectedDoctor,
                                date: this.currentDate,
                                timeSlots: this.selectedSlots
                            })
                        });

                        if (response.ok) {
                            alert('Doctor availability saved successfully!');
                        } else {
                            throw new Error('Failed to save doctor availability');
                        }
                    } catch (error) {
                        alert('Error saving doctor availability: ' + error.message);
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

                capitalizeFirst(string) {
                    return string.charAt(0).toUpperCase() + string.slice(1);
                },

                getStatusClass(status) {
                    const classes = {
                        'scheduled': 'bg-blue-100 text-blue-800',
                        'completed': 'bg-green-100 text-green-800',
                        'cancelled': 'bg-red-100 text-red-800'
                    };
                    return `px-2 py-1 rounded-full text-xs ${classes[status] || 'bg-gray-100 text-gray-800'}`;
                }
            };
        }
    </script>
</body>

</html>