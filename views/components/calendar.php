<?php
require_once __DIR__ . '/../../config/config.php';

// Get current month's appointments
$currentDate = new DateTime();
$date = $currentDate->format('Y-m-d');

$query = "SELECT a.*, 
                 pu.first_name as patient_first_name, 
                 pu.last_name as patient_last_name,
                 du.first_name as doctor_first_name, 
                 du.last_name as doctor_last_name
          FROM appointments a 
          JOIN patients p ON a.patient_id = p.patient_id 
          JOIN users pu ON p.user_id = pu.user_id
          JOIN doctors d ON a.doctor_id = d.doctor_id
          JOIN users du ON d.user_id = du.user_id
          WHERE DATE(a.appointment_datetime) = ?
          ORDER BY a.appointment_datetime";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$appointments = [];
while ($row = mysqli_fetch_assoc($result)) {
    $appointments[] = $row;
}

// Get total appointments count
$statsQuery = "SELECT COUNT(*) as total FROM appointments";
$statsResult = mysqli_query($conn, $statsQuery);
$stats = mysqli_fetch_assoc($statsResult);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Interactive Calendar</title>
    <style>
        body {
            font-family: sans-serif;
            display: flex;
            justify-content: center;
            padding: 0;
            background-color: transparent;
            margin: 0;
            height: 100%;
        }

        .dashboard-calendar {
            width: 100%;
            border: 1px solid #333;
            height: 100%;
            border-radius: 10px;
            box-shadow: 0 0 0 rgba(0, 0, 0, 0.1);
            overflow: hidden;
            background-color: #fff;
            display: flex;
            flex-direction: column;
            padding: 15px;
            /* Added padding around the calendar content */
            box-sizing: border-box;
            /* Ensures padding is included in the element's total width and height */
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 0 15px 0;
            /* Adjusted header padding */
            background-color: #f9f9f9;
            border-bottom: 1px solid #eee;
            flex-shrink: 0;
        }

        .calendar-header button {
            background: none;
            border: none;
            font-size: 1.2em;
            color: #555;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .calendar-header button:hover {
            background-color: #eee;
        }

        .calendar-header h2 {
            margin: 0;
            font-size: 1em;
            color: #333;
            font-weight: 600;
        }

        .calendar-grid {
            width: 100%;
            border-collapse: collapse;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .calendar-grid thead {
            flex-shrink: 0;
            width: 100%;
            display: table;
        }

        .calendar-grid tbody {
            display: block;
            width: 100%;
            flex-grow: 1;
            overflow-y: auto;
        }

        .calendar-grid tr {
            display: flex;
            width: 100%;
        }

        .calendar-grid th,
        .calendar-grid td {
            width: 14.28%;
            padding: 10px;
            text-align: center;
            font-size: 0.9em;
            box-sizing: border-box;
            flex-grow: 1;
            flex-basis: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .calendar-grid th {
            background-color: #f2f2f2;
            color: #333;
        }

        .calendar-grid td {
            border: 1px solid #f0f0f0;
            color: #444;
            height: auto;
            vertical-align: middle;
        }

        .calendar-day {
            cursor: pointer;
            transition: background-color 0.2s ease;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .calendar-day:hover {
            background-color: #f0f0f0;
        }

        .calendar-day.selected {
            background-color: rgb(0, 103, 200);
            color: white;
            font-weight: bold;
        }

        .calendar-footer {
            padding: 15px 0 0 0;
            /* Adjusted footer padding */
            background-color: #f9f9f9;
            border-top: 1px solid #eee;
            display: flex;
            flex-direction: column;
            gap: 10px;
            flex-shrink: 0;
        }

        .total-appointments {
            font-size: 0.9em;
            color: #777;
        }

        .appointment-filters {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .appointment-filters button {
            background-color: #e9ecef;
            color: #555;
            border: none;
            padding: 6px 10px;
            border-radius: 5px;
            font-size: 0.8em;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .appointment-filters button.active {
            background-color: #007bff;
            color: white;
        }

        .appointment-filters button:hover {
            background-color: #d3d9df;
        }

        .appointments-box {
            background-color: #f1f1f1;
            border-radius: 8px;
            padding: 10px;
            max-height: 150px;
            overflow-y: auto;
            margin-top: 10px;
        }

        .appointment-item {
            font-size: 0.85em;
            color: #444;
            padding: 4px 0;
            border-bottom: 1px solid #ddd;
        }

        .appointment-item:last-child {
            border-bottom: none;
        }
    </style>
</head>

<body>
    <div class="dashboard-calendar">
        <div class="calendar-header">
            <button class="prev-month">&lt;</button>
            <h2 class="current-month"></h2>
            <button class="next-month">&gt;</button>
            <button class="go-today">Today</button>
        </div>
        <table class="calendar-grid">
            <thead>
                <tr>
                    <th>Mo</th>
                    <th>Tu</th>
                    <th>We</th>
                    <th>Th</th>
                    <th>Fr</th>
                    <th>Sa</th>
                    <th>Su</th>
                </tr>
            </thead>
            <tbody id="calendar-body"></tbody>
        </table>
        <div class="calendar-footer">
            <div class="total-appointments">
                Total Appointments: <span id="total-appointments"><?php echo $stats['total'] ?? 0; ?></span>
            </div>
            <div class="appointment-filters">
                <button class="filter active" data-filter="all">All</button>
                <button class="filter" data-filter="pending">Pending</button>
                <button class="filter" data-filter="confirmed">Confirmed</button>
                <button class="filter" data-filter="completed">Completed</button>
            </div>
            <div class="appointments-box" id="appointments-box">
                <!-- Appointments will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        const calendarHeader = document.querySelector('.calendar-header h2');
        const calendarBody = document.getElementById('calendar-body');
        const prevMonthBtn = document.querySelector('.prev-month');
        const nextMonthBtn = document.querySelector('.next-month');
        const filterButtons = document.querySelectorAll('.appointment-filters button');
        const appointmentsBox = document.getElementById('appointments-box');
        const totalAppointmentsSpan = document.getElementById('total-appointments');

        let currentDate = new Date();
        const today = new Date();
        let currentFilter = 'all';

        // Function to format appointment time
        function formatTime(time) {
            return new Date(`2000-01-01T${time}`).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }

        // Function to load appointments for a date
        async function loadAppointments(date) {
            try {
                const response = await fetch(`../api/appointments.php?date=${date}`);
                if (!response.ok) {
                    throw new Error('Failed to fetch appointments');
                }
                const data = await response.json();
                displayAppointments(data.appointments || []);
            } catch (error) {
                console.error('Error loading appointments:', error);
                showError('Failed to load appointments');
            }
        }

        // Function to display appointments
        function displayAppointments(appointments) {
            appointmentsBox.innerHTML = '';
            if (appointments && appointments.length > 0) {
                appointments.forEach(appointment => {
                    if (currentFilter === 'all' || appointment.status === currentFilter) {
                        const appointmentItem = document.createElement('div');
                        appointmentItem.classList.add('appointment-item');
                        appointmentItem.innerHTML = `
                            <div class="appointment-time">${formatTime(appointment.appointment_datetime)}</div>
                            <div class="appointment-details">
                                <div class="patient-name">${appointment.patient_first_name} ${appointment.patient_last_name}</div>
                                <div class="doctor-name">Dr. ${appointment.doctor_first_name} ${appointment.doctor_last_name}</div>
                                <div class="reason">${appointment.reason_for_visit}</div>
                            </div>
                            <div class="appointment-status ${appointment.status}">${appointment.status}</div>
                        `;
                        appointmentsBox.appendChild(appointmentItem);
                    }
                });
            } else {
                appointmentsBox.innerHTML = '<div class="no-appointments">No appointments for this date</div>';
            }
        }

        // Function to show error message
        function showError(message) {
            appointmentsBox.innerHTML = `<div class="error-message">${message}</div>`;
        }

        // Function to generate calendar
        function generateCalendar(date) {
            const year = date.getFullYear();
            const month = date.getMonth();
            const firstDayOfMonth = new Date(year, month, 1);
            const lastDayOfMonth = new Date(year, month + 1, 0);
            const daysInMonth = lastDayOfMonth.getDate();
            const startDay = (firstDayOfMonth.getDay() + 6) % 7;

            calendarHeader.textContent = date.toLocaleString('default', {
                month: 'long',
                year: 'numeric',
            });
            calendarBody.innerHTML = '';

            let dayCounter = 1;
            for (let i = 0; i < 6; i++) {
                const row = document.createElement('tr');
                for (let j = 0; j < 7; j++) {
                    const cell = document.createElement('td');
                    if (i === 0 && j < startDay) {
                        row.appendChild(cell);
                    } else if (dayCounter > daysInMonth) {
                        row.appendChild(cell);
                    } else {
                        const currentDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(dayCounter).padStart(2, '0')}`;
                        cell.textContent = dayCounter;
                        cell.dataset.date = currentDate;
                        cell.classList.add('calendar-day');

                        if (year === today.getFullYear() && month === today.getMonth() && dayCounter === today.getDate()) {
                            cell.classList.add('today', 'selected');
                            loadAppointments(currentDate);
                        }

                        cell.addEventListener('click', function () {
                            document.querySelectorAll('.calendar-day.selected').forEach(el => el.classList.remove('selected'));
                            this.classList.add('selected');
                            loadAppointments(this.dataset.date);
                        });

                        row.appendChild(cell);
                        dayCounter++;
                    }
                }
                calendarBody.appendChild(row);
                if (dayCounter > daysInMonth) break;
            }
        }

        // Event listeners
        function navigateMonth(direction) {
            currentDate.setMonth(currentDate.getMonth() + direction);
            generateCalendar(currentDate);
        }

        const goTodayBtn = document.querySelector('.go-today');
        goTodayBtn.addEventListener('click', () => {
            currentDate = new Date(today.getTime());
            generateCalendar(currentDate);
        });

        prevMonthBtn.addEventListener('click', () => navigateMonth(-1));
        nextMonthBtn.addEventListener('click', () => navigateMonth(1));

        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                filterButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                currentFilter = button.dataset.filter;
                const selectedDate = document.querySelector('.calendar-day.selected')?.dataset.date;
                if (selectedDate) {
                    loadAppointments(selectedDate);
                }
            });
        });

        // Initial calendar generation
        document.addEventListener('DOMContentLoaded', () => {
            generateCalendar(currentDate);
        });
    </script>
</body>

</html>