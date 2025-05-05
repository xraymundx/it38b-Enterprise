<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nurse Dashboard</title>
<link rel="stylesheet" href="calendar.css">
<style>
    body {
        font-family: sans-serif;
        margin: 20px;
        background-color: #f4f4f4;
    }

    .maincontainer {
        max-width: 1200px;
        margin: 0 auto;
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .dashboard-nav {
        display: flex;
        margin-bottom: 20px;
        border-bottom: 1px solid #ddd;
    }

    .dashboard-nav button {
        background: none;
        border: none;
        padding: 10px 15px;
        font-size: 1rem;
        cursor: pointer;
        color: #555;
        border-bottom: 2px solid transparent;
    }

    .dashboard-nav button.active {
        color: #007bff;
        border-bottom-color: #007bff;
    }

    .dashboard-content>div {
        display: none;
        /* Initially hide all content sections */
    }

    .dashboard-content>div.active {
        display: block;
        /* Show the active content section */
    }

    .dashboard-overview {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }

    .overview-card {
        flex: 1;
        background-color: #e9ecef;
        border-radius: 6px;
        padding: 15px;
        text-align: center;
    }

    .overview-card.earnings {
        background-color: #007bff;
        color: white;
    }

    .overview-card.patients {
        background-color: #28a745;
        color: white;
    }

    .overview-card.appointments-count {
        background-color: #ffc107;
        color: #333;
    }

    .card-title {
        font-size: 1.1rem;
        font-weight: bold;
        margin-bottom: 5px;
    }

    .card-value {
        font-size: 1.5rem;
    }

    .dashboard-widgets {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .widget {
        background-color: #fff;
        border-radius: 6px;
        padding: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .widget-title {
        font-size: 1.2rem;
        margin-bottom: 10px;
        border-bottom: 1px solid #ddd;
        padding-bottom: 5px;
    }

    /* Recent Patients Table */
    .recent-patients-table {
        width: 100%;
        border-collapse: collapse;
    }

    .recent-patients-table th,
    .recent-patients-table td {
        padding: 8px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .recent-patients-table th {
        font-weight: bold;
    }

    .patient-info {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .patient-info img {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        object-fit: cover;
    }

    .see-more {
        margin-top: 10px;
        text-align: right;
    }

    .see-more a {
        color: #007bff;
        text-decoration: none;
    }

    /* Appointments Tab Styles */
    .appointments-list-container {
        padding: 15px;
        background-color: #fff;
        border-radius: 6px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .appointment-item {
        border-bottom: 1px solid #eee;
        padding: 10px 0;
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .appointment-item:last-child {
        border-bottom: none;
    }

    .appointment-details {
        flex-grow: 1;
    }

    .appointment-details h4 {
        font-size: 1rem;
        margin-bottom: 5px;
    }

    .appointment-meta {
        font-size: 0.875rem;
        color: #777;
    }

    /* Responsive Layout */
    @media (max-width: 768px) {
        .dashboard-overview {
            flex-direction: column;
        }

        .dashboard-widgets {
            grid-template-columns: 1fr;
        }
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const navButtons = document.querySelectorAll('.dashboard-nav button');
        const contentDivs = document.querySelectorAll('.dashboard-content > div');

        function showTab(tabId) {
            contentDivs.forEach(div => div.classList.remove('active'));
            navButtons.forEach(button => button.classList.remove('active'));

            const targetDiv = document.getElementById(tabId);
            const targetButton = Array.from(navButtons).find(button => button.getAttribute('data-tab') === tabId);

            if (targetDiv) {
                targetDiv.classList.add('active');
            }
            if (targetButton) {
                targetButton.classList.add('active');
            }
        }

        navButtons.forEach(button => {
            button.addEventListener('click', () => {
                const tabId = button.getAttribute('data-tab');
                showTab(tabId);
            });
        });

        // Show the default tab (e.g., dashboard) on load
        showTab('dashboard-home');

        // Example of fetching recent patients (replace with your actual data fetching)
        const recentPatientsTableBody = document.querySelector('#recent-patients-tab .recent-patients-table tbody');
        const recentPatientsData = [
            { name: 'Dan Mark Javier', date: '5/08/2025', description: 'Follow-up' },
            { name: 'Jane Doe', date: '5/09/2025', description: 'Vaccination' },
            { name: 'Peter Smith', date: '5/10/2025', description: 'Consultation' },
        ];

        if (recentPatientsTableBody) {
            recentPatientsData.forEach(patient => {
                const row = recentPatientsTableBody.insertRow();
                const nameCell = row.insertCell();
                const dateCell = row.insertCell();
                const descriptionCell = row.insertCell();

                nameCell.innerHTML = `<div class="patient-info"><img src="https://via.placeholder.com/30" alt="Patient Avatar"><span>${patient.name}</span></div>`;
                dateCell.textContent = patient.date;
                descriptionCell.textContent = patient.description;
            });
        }

        // Placeholder appointment data
        const appointmentsListContainer = document.getElementById('appointments-tab-content');
        if (appointmentsListContainer) {
            const appointmentsData = [
                { patient: 'Alice Brown', time: '9:00 AM', date: 'May 6, 2025', reason: 'Checkup' },
                { patient: 'Bob Green', time: '10:30 AM', date: 'May 6, 2025', reason: 'Vaccination' },
                { patient: 'Charlie White', time: '11:15 AM', date: 'May 6, 2025', reason: 'Consultation' },
                { patient: 'Diana Black', time: '2:00 PM', date: 'May 6, 2025', reason: 'Follow-up' },
            ];

            appointmentsData.forEach(appointment => {
                const appointmentItem = document.createElement('div');
                appointmentItem.classList.add('appointment-item');
                appointmentItem.innerHTML = `
                        <div class="appointment-details">
                            <h4>${appointment.patient}</h4>
                            <p class="appointment-meta">${appointment.time} - ${appointment.date}</p>
                            <p class="appointment-meta">Reason: ${appointment.reason}</p>
                        </div>
                    `;
                appointmentsListContainer.appendChild(appointmentItem);
            });
        }
    });
</script>
</head>

<body>
    <div class="maincontainer">
        <h1>Welcome Mr. John</h1>

        <div class="dashboard-nav">
            <button data-tab="dashboard-home" class="active">Dashboard</button>
            <button data-tab="appointments-tab">Appointments</button>
            <button data-tab="recent-patients-tab">Recent Patients</button>
        </div>

        <div class="dashboard-content">
            <div id="dashboard-home" class="active">
                <div class="dashboard-overview">
                    <div class="overview-card earnings">
                        <div class="card-content">
                            <span class="card-title">Clinic Earnings</span>
                            <span class="card-value">100PHP</span>
                        </div>
                    </div>

                    <div class="overview-card patients">
                        <div class="card-content">
                            <span class="card-title">Total Patient</span>
                            <span class="card-value">22</span>
                        </div>
                    </div>

                    <div class="overview-card appointments-count">
                        <div class="card-content">
                            <span class="card-title">Appointments</span>
                            <span class="card-value">50</span>
                        </div>
                    </div>
                </div>

                <div class="dashboard-widgets">
                    <div class="widget appointments-calendar">
                        <h2 class="widget-title">Appointments Calendar</h2>
                        <?php include 'calendar.php'; ?>
                    </div>
                </div>
            </div>

            <div id="appointments-tab">
                <h2 class="widget-title">Upcoming Appointments</h2>
                <div id="appointments-tab-content" class="appointments-list-container">
                </div>
            </div>

            <div id="recent-patients-tab">
                <h2 class="widget-title">Recent Patients</h2>
                <table class="recent-patients-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Date</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
                <div class="see-more">
                    <a href="#">See More...</a>
                </div>
            </div>
        </div>
    </div>
    </div>
</body>