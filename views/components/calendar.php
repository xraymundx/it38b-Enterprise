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
            padding: 2rem;
            background-color: #f4f6f8;
        }

        .dashboard-calendar {
            width: 360px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            background-color: #fff;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background-color: #f9f9f9;
            border-bottom: 1px solid #eee;
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
        }

        .calendar-grid th,
        .calendar-grid td {
            width: 14.28%;
            padding: 10px;
            text-align: center;
            font-size: 0.9em;
        }

        .calendar-grid th {
            background-color: #f2f2f2;
            color: #333;
        }

        .calendar-grid td {
            border: 1px solid #f0f0f0;
            color: #444;
            height: 40px;
            vertical-align: middle;
        }


        .calendar-day {
            cursor: pointer;
            transition: background-color 0.2s ease;
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
            padding: 15px;
            background-color: #f9f9f9;
            border-top: 1px solid #eee;
            display: flex;
            flex-direction: column;
            gap: 10px;
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
            <div class="total-appointments">Total Appointments</div>
            <div class="appointment-filters">
                <button class="filter active">All</button>
                <button class="filter">Today</button>
                <button class="filter">Upcoming</button>
                <button class="filter">Done</button>
            </div>
            <div class="appointments-box" id="appointments-box">
                <!-- Placeholder items -->
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

        let currentDate = new Date();
        const today = new Date();

        function generateCalendar(date) {
            const year = date.getFullYear();
            const month = date.getMonth();
            const firstDayOfMonth = new Date(year, month, 1);
            const lastDayOfMonth = new Date(year, month + 1, 0);
            const daysInMonth = lastDayOfMonth.getDate();
            const startDay = (firstDayOfMonth.getDay() + 6) % 7; // Adjust for Monday start

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
                        cell.dataset.date = `${year}-${String(month + 1).padStart(2, '0')}-${String(dayCounter).padStart(2, '0')}`;
                        cell.classList.add('calendar-day');

                        if (
                            year === today.getFullYear() &&
                            month === today.getMonth() &&
                            dayCounter === today.getDate()
                        ) {
                            cell.classList.add('today', 'selected');
                        }

                        cell.addEventListener('click', function () {
                            document.querySelectorAll('.calendar-day.selected').forEach((el) => el.classList.remove('selected'));
                            this.classList.add('selected');
                            console.log('Selected date:', this.dataset.date);
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
            generateCalendar(currentDate);
        });
        prevMonthBtn.addEventListener('click', () => navigateMonth(-1));
        nextMonthBtn.addEventListener('click', () => navigateMonth(1));

        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                filterButtons.forEach((btn) => btn.classList.remove('active'));
                button.classList.add('active');
                // Placeholder: Filter logic here
                console.log('Filter:', button.textContent);
            });
        });

        generateCalendar(currentDate);
    </script>
</body>

</html>