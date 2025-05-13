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
            box-sizing: border-box;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 0 15px 0;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .view-all-appointments-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 0.8em;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .view-all-appointments-button:hover {
            background-color: #0056b3;
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
                <span>Total Appointments</span>
                <button class="view-all-appointments-button">View All</button>
            </div>
            <div class="appointment-filters">
                <button class="filter active">All</button>
                <button class="filter">Today</button>
                <button class="filter">Upcoming</button>
                <button class="filter">Done</button>
            </div>
            <div class="appointments-box" id="appointments-box">
                <div class="appointment-item">8:00 AM - Dental Checkup</div>
                <div class="appointment-item">10:30 AM - Physical Therapy</div>
                <div class="appointment-item">1:00 PM - Vaccination</div>
                <div class="appointment-item">3:45 PM - Blood Test</div>
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
        const viewAllAppointmentsButton = document.querySelector('.view-all-appointments-button');

        let currentDate = new Date();
        const today = new Date();
        let selectedDate = new Date().toISOString().split('T')[0]; // Track selected date in YYYY-MM-DD format
        let currentFilter = 'all';

        // Load appointments for a specific date
        async function loadAppointments(date) {
            try {
                const response = await fetch(`/it38b-Enterprise/api/appointments.php?date=${date}`);
                if (!response.ok) {
                    throw new Error('Failed to fetch appointments');
                }

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || 'Failed to load appointments');
                }

                displayAppointments(data.appointments || []);
            } catch (error) {
                console.error('Error loading appointments:', error);
                appointmentsBox.innerHTML = '<div class="appointment-item">Failed to load appointments</div>';
            }
        }

        // Display appointments in the appointments box
        function displayAppointments(appointments) {
            appointmentsBox.innerHTML = '';

            if (!appointments || appointments.length === 0) {
                appointmentsBox.innerHTML = '<div class="appointment-item">No appointments for this date</div>';
                return;
            }

            // Filter appointments based on selected filter
            let filteredAppointments = appointments;
            if (currentFilter !== 'all') {
                const statusMap = {
                    'today': () => {
                        const todayStr = new Date().toISOString().split('T')[0];
                        return appointments.filter(a => a.appointment_datetime.includes(todayStr));
                    },
                    'upcoming': () => {
                        const now = new Date();
                        return appointments.filter(a => new Date(a.appointment_datetime) > now);
                    },
                    'done': () => {
                        return appointments.filter(a => a.status === 'Completed');
                    }
                };

                if (statusMap[currentFilter]) {
                    filteredAppointments = statusMap[currentFilter]();
                }
            }

            // Display the filtered appointments
            filteredAppointments.forEach(appointment => {
                const appointmentTime = new Date(appointment.appointment_datetime);
                const formattedTime = appointmentTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                const appointmentItem = document.createElement('div');
                appointmentItem.classList.add('appointment-item');
                appointmentItem.innerHTML = `${formattedTime} - ${appointment.patient_first_name} ${appointment.patient_last_name}`;

                // Make appointment clickable to view details
                appointmentItem.style.cursor = 'pointer';
                appointmentItem.addEventListener('click', () => {
                    window.location.href = `/it38b-Enterprise/views/nurse/appointment_view.php?id=${appointment.appointment_id}`;
                });

                appointmentsBox.appendChild(appointmentItem);
            });
        }

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
                        cell.textContent = dayCounter;
                        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(dayCounter).padStart(2, '0')}`;
                        cell.dataset.date = dateStr;
                        cell.classList.add('calendar-day');

                        if (
                            year === today.getFullYear() &&
                            month === today.getMonth() &&
                            dayCounter === today.getDate()
                        ) {
                            cell.classList.add('today');

                            // Select today initially if no date is selected
                            if (!document.querySelector('.calendar-day.selected')) {
                                cell.classList.add('selected');
                                selectedDate = dateStr;
                                loadAppointments(dateStr);
                            }
                        }

                        // Mark the selected date if it's in the current month view
                        if (dateStr === selectedDate) {
                            cell.classList.add('selected');
                        }

                        cell.addEventListener('click', function () {
                            document.querySelectorAll('.calendar-day.selected').forEach((el) => el.classList.remove('selected'));
                            this.classList.add('selected');
                            selectedDate = this.dataset.date;
                            loadAppointments(selectedDate);
                        });

                        row.appendChild(cell);
                        dayCounter++;
                    }
                }

                calendarBody.appendChild(row);

                if (dayCounter > daysInMonth) break;
            }
        }

        function navigateMonth(direction) {
            currentDate.setMonth(currentDate.getMonth() + direction);
            generateCalendar(currentDate);
        }

        const goTodayBtn = document.querySelector('.go-today');
        goTodayBtn.addEventListener('click', () => {
            currentDate = new Date(today.getTime());
            selectedDate = currentDate.toISOString().split('T')[0];
            generateCalendar(currentDate);
            loadAppointments(selectedDate);
        });

        prevMonthBtn.addEventListener('click', () => navigateMonth(-1));
        nextMonthBtn.addEventListener('click', () => navigateMonth(1));

        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                filterButtons.forEach((btn) => btn.classList.remove('active'));
                button.classList.add('active');
                currentFilter = button.textContent.toLowerCase().trim();
                loadAppointments(selectedDate);
            });
        });

        viewAllAppointmentsButton.addEventListener('click', () => {
            // Redirect to the appointments list page
            window.location.href = '/it38b-Enterprise/routes/dashboard_router.php?page=appointments&status=all';
        });

        // Initialize calendar and load today's appointments
        generateCalendar(currentDate);
        loadAppointments(selectedDate);
    </script>
</body>

</html>