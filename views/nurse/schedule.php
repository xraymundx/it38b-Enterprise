<?php

require 'config/config.php';


// Check if nurse is logged in (adjust based on your authentication)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nurse') {
    header("Location: login.php"); // Redirect if not a nurse
    exit();
}

$nurse_id = $_SESSION['user_id']; // Get the nurse's ID

// Initialize selected date (for initial page load)
$selected_date = date('Y-m-d');
if (isset($_GET['date'])) {
    $selected_date = $_GET['date'];
}

// --- Time Availability Selector Logic (for doctors) ---
$availability_slots = [];
$default_start_hour = 7;
$default_end_hour = 18;
$selected_doctor_id = isset($_GET['doctor_id']) ? (int) $_GET['doctor_id'] : null; // To select a specific doctor

// Function to load saved doctor availability for a specific date and doctor
function loadDoctorAvailability($conn, $doctor_id, $date)
{
    if (!$doctor_id)
        return [];
    $stmt = $conn->prepare("SELECT start_time FROM doctor_availability WHERE doctor_id = ? AND availability_date = ? ORDER BY start_time ASC");
    $stmt->bind_param("is", $doctor_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $availability = [];
    while ($row = $result->fetch_assoc()) {
        $availability[] = date('H:00', strtotime($row['start_time']));
    }
    $stmt->close();
    return $availability;
}

// Load saved availability for the selected doctor or default to all times (for initial load)
if ($selected_doctor_id && isset($_GET['date'])) {
    $saved_availability = loadDoctorAvailability($conn, $selected_doctor_id, $_GET['date']);
    if (!empty($saved_availability)) {
        $availability_slots = $saved_availability;
    } else {
        for ($i = $default_start_hour; $i <= $default_end_hour; $i++) {
            $availability_slots[] = sprintf('%02d:00', $i);
        }
    }
} else {
    for ($i = $default_start_hour; $i <= $default_end_hour; $i++) {
        $availability_slots[] = sprintf('%02d:00', $i);
    }
}

if (isset($_POST['save_availability']) && isset($_POST['doctor_id_to_save']) && isset($_POST['selected_date'])) {
    $doctor_id_to_save = (int) $_POST['doctor_id_to_save'];
    $selected_date_to_save = $_POST['selected_date'];
    $new_availability = $_POST['availability'] ?? [];

    // Clear existing doctor availability for the selected date and doctor
    $stmt_delete = $conn->prepare("DELETE FROM doctor_availability WHERE doctor_id = ? AND availability_date = ?");
    $stmt_delete->bind_param("is", $doctor_id_to_save, $selected_date_to_save);
    $stmt_delete->execute();
    $stmt_delete->close();

    // Insert the new doctor availability
    if (!empty($new_availability)) {
        $stmt_insert = $conn->prepare("INSERT INTO doctor_availability (doctor_id, availability_date, start_time) VALUES (?, ?, ?)");
        foreach ($new_availability as $time) {
            $start_time = $time . ':00';
            $stmt_insert->bind_param("iss", $doctor_id_to_save, $selected_date_to_save, $start_time);
            $stmt_insert->execute();
        }
        $stmt_insert->close();
        $availability_slots = $new_availability; // Update displayed slots (for the current request)
        $success_message = "Doctor availability saved successfully for doctor ID " . $doctor_id_to_save . " on " . date('Y-m-d', strtotime($selected_date_to_save));
    } else {
        $availability_slots = []; // Clear displayed slots (for the current request)
        $success_message = "Doctor availability cleared for doctor ID " . $doctor_id_to_save . " on " . date('Y-m-d', strtotime($selected_date_to_save));
    }
}

// --- Appointments List Logic (for initial page load) ---
$appointments = [];
$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

$stmt_count = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE DATE(appointment_datetime) = ?");
$stmt_count->bind_param("s", $selected_date);
$stmt_count->execute();
$total_items = $stmt_count->get_result()->fetch_row()[0];
$stmt_count->close();

$total_pages = ceil($total_items / $items_per_page);

$stmt_appointments = $conn->prepare("SELECT p.first_name AS patient_name, TIME(a.appointment_datetime) AS appointment_time, a.reason_for_visit, a.status, a.notes, a.created_at, a.updated_at
                                    FROM appointments a
                                    JOIN patients p ON a.patient_id = p.patient_id
                                    WHERE DATE(a.appointment_datetime) = ?
                                    ORDER BY a.appointment_datetime
                                    LIMIT ?, ?");
$stmt_appointments->bind_param("sii", $selected_date, $offset, $items_per_page);
$stmt_appointments->execute();
$result_appointments = $stmt_appointments->get_result();
$appointments = $result_appointments->fetch_all(MYSQLI_ASSOC);
$stmt_appointments->close();

// --- Fetch all doctors for the dropdown ---
$doctors = [];
$stmt_doctors_list = $conn->prepare("SELECT d.doctor_id, u.first_name, u.last_name, sp.specialization_name
                                    FROM doctors d
                                    JOIN users u ON d.user_id = u.user_id
                                    LEFT JOIN specializations sp ON d.specialization_id = sp.specialization_id
                                    ORDER BY u.first_name, u.last_name");
$stmt_doctors_list->execute();
$result_doctors_list = $stmt_doctors_list->get_result();
$doctors = $result_doctors_list->fetch_all(MYSQLI_ASSOC);
$stmt_doctors_list->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Schedule</title>
    <style>
        /* Basic Calendar Styling */
        .calendar-container {
            margin-bottom: 20px;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            margin-bottom: 5px;
        }

        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
        }

        .calendar-day {
            padding: 10px;
            text-align: center;
            cursor: pointer;
        }

        .calendar-day.selected {
            background-color: #007bff;
            color: white;
            border-radius: 5px;
        }

        .calendar-nav button {
            padding: 5px 10px;
            cursor: pointer;
        }

        /* Doctor Selection Styling */
        .doctor-selection {
            margin-bottom: 15px;
        }

        /* Time Availability Selector Styling */
        .availability-container {
            margin-bottom: 20px;
            border: 1px solid #ccc;
            padding: 15px;
            border-radius: 5px;
        }

        .availability-actions button {
            padding: 8px 12px;
            margin-right: 5px;
            cursor: pointer;
        }

        .add-times button {
            padding: 6px 10px;
            margin-right: 5px;
            margin-bottom: 5px;
            cursor: pointer;
        }

        .selected-times {
            margin-top: 10px;
        }

        .selected-time-slot {
            display: inline-block;
            background-color: #f0f0f0;
            color: #333;
            padding: 5px 10px;
            margin-right: 5px;
            margin-bottom: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .remove-time {
            cursor: pointer;
            margin-left: 5px;
            color: red;
            font-weight: bold;
        }

        /* Appointments List Styling */
        .appointments-list {
            border: 1px solid #ccc;
            padding: 15px;
            border-radius: 5px;
        }

        .appointment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .appointment-table th,
        .appointment-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .pagination {
            margin-top: 10px;
        }

        .pagination a,
        .pagination span {
            padding: 5px 10px;
            margin-right: 5px;
            border: 1px solid #ddd;
            border-radius: 3px;
            text-decoration: none;
            color: #333;
        }

        .pagination .current {
            background-color: #007bff;
            color: white;
        }

        .success-message {
            color: green;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <h1>Nurse Schedule</h1>

    <div id="success-message-container">
        <?php if (isset($success_message)): ?>
            <p class="success-message"><?= $success_message ?></p>
        <?php endif; ?>
    </div>

    <div class="calendar-container">
        <h2>Select Date</h2>
        <div class="calendar-header">
            <button onclick="changeMonth('prev')">&lt; Prev</button>
            <h3 id="current-month"><?= date('F Y', strtotime($selected_date)) ?></h3>
            <button onclick="changeMonth('next')">Next &gt;</button>
        </div>
        <div class="calendar-weekdays">
            <div>Sun</div>
            <div>Mon</div>
            <div>Tue</div>
            <div>Wed</div>
            <div>Thu</div>
            <div>Fri</div>
            <div>Sat</div>
        </div>
        <div class="calendar-days" id="calendar-days">
        </div>
        <input type="hidden" id="selected-date" value="<?= $selected_date ?>">
    </div>

    <div class="doctor-selection">
        <h2>Select Doctor</h2>
        <select id="doctor-select">
            <option value="">-- Select a Doctor --</option>
            <?php foreach ($doctors as $doctor): ?>
                <option value="<?= $doctor['doctor_id'] ?>" <?= ($selected_doctor_id === $doctor['doctor_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name'] . ($doctor['specialization_name'] ? ' (' . $doctor['specialization_name'] . ')' : '')) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div id="availability-container">
        <?php if ($selected_doctor_id): ?>
            <h2>Set Availability for Doctor ID <span id="selected-doctor-id-display"><?= $selected_doctor_id ?></span> on
                <span id="selected-date-display"><?= date('Y-m-d', strtotime($selected_date)) ?></span>
            </h2>
            <form id="availability-form" method="post">
                <input type="hidden" name="doctor_id_to_save" value="<?= $selected_doctor_id ?>">
                <input type="hidden" name="selected_date" id="selected-date-form" value="<?= $selected_date ?>">
                <!-- Ensure this exists -->
                <div class="add-times">
                    <button type="button" onclick="addAllTimes()">Add All +</button>
                    <?php for ($i = $default_start_hour; $i <= $default_end_hour; $i++): ?>
                        <?php $hour_format = sprintf('%02d:00', $i); ?>
                        <button type="button" class="add-time-btn" data-time="<?= $hour_format ?>">
                            <?= date('g:00 A', strtotime($hour_format)) ?> +
                        </button>
                    <?php endfor; ?>
                </div>
                <div class="selected-times" id="selected-times-display">
                    <?php foreach ($availability_slots as $slot): ?>
                        <span class="selected-time-slot" data-time="<?= $slot ?>">
                            <?= date('g:00 A', strtotime($slot)) ?>
                            <span class="remove-time" onclick="removeTime('<?= $slot ?>')">X</span>
                            <input type="hidden" name="availability[]" value="<?= $slot ?>">
                        </span>
                    <?php endforeach; ?>
                </div>
                <div class="availability-actions">
                    <button type="button" onclick="saveAvailability()">Save Settings</button>
                    <button type="button" onclick="clearAllTimes()">Clear All X</button>
                </div>
            </form>
        <?php else: ?>
            <p>Please select a doctor to view and set their availability.</p>
        <?php endif; ?>
    </div>

    <div id="appointments-list-container">
        <h2>Appointments for <?= date('Y-m-d', strtotime($selected_date)) ?></h2>
        <?php if (empty($appointments)): ?>
            <p>No appointments scheduled for this date.</p>
        <?php else: ?>
            <table class="appointment-table">
                <thead>
                    <tr>
                        <th>Patient Name</th>
                        <th>Time</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Created At</th>
                        <th>Updated At</th>
                    </tr>
                </thead>
                <tbody id="appointments-table-body">
                    <?php foreach ($appointments as $appointment): ?>
                        <tr>
                            <td><?= htmlspecialchars($appointment['patient_name']) ?></td>
                            <td><?= date('g:i A', strtotime($appointment['appointment_time'])) ?></td>
                            <td><?= htmlspecialchars($appointment['reason_for_visit']) ?></td>
                            <td><?= htmlspecialchars($appointment['status']) ?></td>
                            <td><?= htmlspecialchars($appointment['notes']) ?></td>
                            <td><?= date('Y-m-d H:i:s', strtotime($appointment['created_at'])) ?></td>
                            <td><?= date('Y-m-d H:i:s', strtotime($appointment['updated_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
                <div class="pagination" id="appointments-pagination">
                    <?php if ($current_page > 1): ?>
                        <a href="#" onclick="loadAppointments('<?= $selected_date ?>', <?= $current_page - 1 ?>)">Previous</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i === $current_page): ?>
                            <span class="current"><?= $i ?></span>
                        <?php else: ?>
                            <a href="#" onclick="loadAppointments('<?= $selected_date ?>', <?= $i ?>)"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="#" onclick="loadAppointments('<?= $selected_date ?>', <?= $current_page + 1 ?>)">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <script>
        const calendarDaysContainer = document.getElementById('calendar-days');
        const currentMonthDisplay = document.getElementById('current-month');
        const selectedDateInput = document.getElementById('selected-date');
        const selectedDateDisplay = document.getElementById('selected-date-display');
        const selectedTimesDisplay = document.getElementById('selected-times-display');
        const addTimeButtons = document.querySelectorAll('.add-time-btn');
        const doctorSelect = document.getElementById('doctor-select');
        const availabilityContainer = document.getElementById('availability-container');
        const availabilityForm = document.getElementById('availability-form');
        const selectedDateForm = document.getElementById('selected-date-form');
        const appointmentsListContainer = document.getElementById('appointments-list-container');
        const successMessageContainer = document.getElementById('success-message-container');
        const appointmentsTableBody = document.getElementById('appointments-table-body');
        const appointmentsPagination = document.getElementById('appointments-pagination');

        let currentDate = new Date(selectedDateInput.value);
        let currentDoctorId = doctorSelect.value;

        function generateCalendar(date) {
            calendarDaysContainer.innerHTML = '';
            const year = date.getFullYear();
            const month = date.getMonth();
            const firstDayOfMonth = new Date(year, month, 1);
            const lastDayOfMonth = new Date(year, month + 1, 0);
            const daysInMonth = lastDayOfMonth.getDate();
            const dayOfWeekOfFirstDay = firstDayOfMonth.getDay();

            currentMonthDisplay.textContent = `${new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(date)}`;

            // Add empty cells for the days before the first day of the month
            for (let i = 0; i < dayOfWeekOfFirstDay; i++) {
                const emptyCell = document.createElement('div');
                calendarDaysContainer.appendChild(emptyCell);
            }

            // Add the days of the month
            for (let i = 1; i <= daysInMonth; i++) {
                const dayCell = document.createElement('div');
                dayCell.classList.add('calendar-day');
                dayCell.textContent = i;
                const currentDateFormatted = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;

                if (currentDateFormatted === selectedDateInput.value) {
                    dayCell.classList.add('selected');
                }

                dayCell.addEventListener('click', () => {
                    selectedDateInput.value = currentDateFormatted;
                    selectedDateForm.value = currentDateFormatted;
                    loadAvailability(currentDoctorId, currentDateFormatted);
                    loadAppointments(currentDateFormatted, 1);
                    // Update selected class
                    document.querySelectorAll('.calendar-day').forEach(day => day.classList.remove('selected'));
                    dayCell.classList.add('selected');
                });
                calendarDaysContainer.appendChild(dayCell);
            }
        }

        function changeMonth(direction) {
            if (direction === 'prev') {
                currentDate.setMonth(currentDate.getMonth() - 1);
            } else {
                currentDate.setMonth(currentDate.getMonth() + 1);
            }
            generateCalendar(currentDate);
        }

        // JavaScript for Time Availability Selector
        function addTime(time) {
            const selectedTimesContainer = document.getElementById('selected-times-display');
            const timeExists = selectedTimesContainer.querySelector(`[data-time="${time}"]`);

            if (!timeExists) {
                const slot = document.createElement('span');
                slot.classList.add('selected-time-slot');
                slot.dataset.time = time;
                slot.innerHTML = `${formatTime(time)} <span class="remove-time" onclick="removeTime('${time}')">X</span><input type="hidden" name="availability[]" value="${time}">`;
                selectedTimesContainer.appendChild(slot);
            }
        }

        addTimeButtons.forEach(button => {
            button.addEventListener('click', function () {
                addTime(this.dataset.time);
            });
        });

        function removeTime(time) {
            const selectedTimesContainer = document.getElementById('selected-times-display');
            const timeToRemove = selectedTimesContainer.querySelector(`[data-time="${time}"]`);
            if (timeToRemove) {
                selectedTimesContainer.removeChild(timeToRemove);
            }
        }

        function addAllTimes() {
            const defaultStartHour = 7;
            const defaultEndHour = 18;
            for (let i = defaultStartHour; i <= defaultEndHour; i++) {
                const hourFormat = `${String(i).padStart(2, '0')}:00`;
                addTime(hourFormat);
            }
        }

        function clearAllTimes() {
            const selectedTimesContainer = document.getElementById('selected-times-display');
            selectedTimesContainer.innerHTML = '';
        }

        function formatTime(time) {
            const [hours, minutes] = time.split(':');
            const hour = parseInt(hours);
            const period = hour < 12 ? 'AM' : 'PM';
            const displayHour = hour === 0 || hour === 12 ? 12 : hour % 12;
            return `${displayHour}:${minutes} ${period}`;
        }

        doctorSelect.addEventListener('change', function () {
            currentDoctorId = this.value;
            loadAvailability(currentDoctorId, selectedDateInput.value);
        });

        function loadAvailability(doctorId, date) {
            if (!doctorId) {
                availabilityContainer.innerHTML = '<p>Please select a doctor to view and set their availability.</p>';
                return;
            }
            fetch(`load_availability.php?doctor_id=${doctorId}&date=${date}`)
                .then(response => response.text())
                .then(data => {
                    availabilityContainer.innerHTML = data;
                    // Re-attach event listeners for dynamically loaded elements if needed
                    attachAvailabilityEventListeners();
                });
        }

        function attachAvailabilityEventListeners() {
            const addTimeButtonsDynamic = document.querySelectorAll('#availability-container .add-time-btn');
            const removeTimeButtonsDynamic = document.querySelectorAll('#availability-container .remove-time');
            const clearAllButtonDynamic = document.querySelector('#availability-container #clear-all-times');
            const addAllButtonDynamic = document.querySelector('#availability-container #add-all-times');

            addTimeButtonsDynamic.forEach(button => {
                button.addEventListener('click', function () {
                    addTime(this.dataset.time);
                });
            });

            removeTimeButtonsDynamic.forEach(button => {
                button.addEventListener('click', function () {
                    removeTime(this.dataset.time);
                });
            });

            if (clearAllButtonDynamic) {
                clearAllButtonDynamic.addEventListener('click', clearAllTimes);
            }

            if (addAllButtonDynamic) {
                addAllButtonDynamic.addEventListener('click', addAllTimes);
            }
        }

        function saveAvailability() {
            const formData = new FormData(availabilityForm);
            fetch('save_availability.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.text())
                .then(data => {
                    successMessageContainer.innerHTML = data;
                    loadAvailability(currentDoctorId, selectedDateInput.value); // Reload availability
                });
        }

        function loadAppointments(date, page) {
            fetch(`load_appointments.php?date=${date}&page=${page}`)
                .then(response => response.text())
                .then(data => {
                    appointmentsListContainer.innerHTML = data;
                });
        }

        // Initialize calendar and load data on page load
        generateCalendar(currentDate); // <---- UNCOMMENT THIS LINE
        loadAvailability(currentDoctorId, selectedDateInput.value);
        loadAppointments(selectedDateInput.value, 1);
    </script>
</body>

</html>