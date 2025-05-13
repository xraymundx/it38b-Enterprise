<style>
    body {
        font-family: sans-serif;
        background-color: #f4f6f8;
        margin: 0;
        padding: 20px;
        box-sizing: border-box;
    }

    .parent {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        grid-template-rows: repeat(5, auto);
        grid-column-gap: 20px;
        grid-row-gap: 20px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .add-patient-doctor {
        border: 1px solid #333;
        grid-area: 1 / 1 / 3 / 4;
        background-color: #fff;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        display: grid;
        grid-template-columns: 1fr 1fr;
        grid-template-rows: auto auto auto;
        /* Added a row for the slot button */
        grid-template-areas:
            "title image"
            "appointment-button image"
            "slot-button image";
        /* Added grid area for slot button */
        gap: 10px;
        align-items: start;
    }

    .add-patient-doctor h3 {
        grid-area: title;
        margin: 0;
        font-size: 1.5em;
        color: #333;
    }

    .add-appointment-button,
    .add-slot-button {
        /* Apply styles to both buttons */
        background-color: #6c5dd3;
        color: white;
        border: none;
        border-radius: 20px;
        padding: 12px 20px;
        cursor: pointer;
        font-size: 1em;
        transition: background-color 0.3s ease;
        text-decoration: none;
        /* Remove default link underline */
        display: inline-block;
        /* Allows setting padding and margin */
        text-align: center;
        /* Center text within the button */
    }

    .add-appointment-button {
        grid-area: appointment-button;
    }

    .add-slot-button {
        grid-area: slot-button;
    }

    .add-appointment-button:hover,
    .add-slot-button:hover {
        background-color: #5649a8;
    }

    .doctor-image-container {
        grid-area: image;
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        overflow: hidden;
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }

    .doctor-image-container img {
        display: block;
        width: auto;
        height: auto;
        max-height: 240px;
        border-radius: 8px;
        object-fit: contain;
        overflow: hidden;
    }

    .stats {
        border: 1px solid #333;
        grid-area: 1 / 4 / 3 / 6;
        background-color: #fff;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .stat-line {
        display: flex;
        flex-direction: column;
    }

    .stat-label {
        font-size: 0.8em;
        color: #777;
        margin-bottom: 2px;
    }

    .stat-number {
        font-size: 2em;
        color: #333;
        font-weight: bold;
    }

    .calender {
        grid-area: 3 / 1 / 4 / 3;
        background-color: #fff;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .calender h3 {
        margin-top: 0;
        color: #333;
        font-size: 1.1em;
        margin-bottom: 10px;
    }

    .calendar-content {
        /* Add basic calendar styles here */
    }

    .appointment-requests {
        border: 1px solid #333;
        grid-area: 3 / 3 / 6 / 6;
        background-color: #fff;
        border-radius: 10px;
        padding: 15px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .appointment-requests-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .appointment-requests-header h3 {
        font-size: 1.1em;
        margin: 0;
        color: #333;
    }

    .view-all-button {
        background-color: #6c5dd3;
        color: white;
        border: none;
        border-radius: 5px;
        padding: 8px 12px;
        cursor: pointer;
        font-size: 0.9em;
        transition: background-color 0.3s ease;
    }

    .view-all-button:hover {
        background-color: #5649a8;
    }

    .appointment-requests-list {
        list-style: none;
        padding: 0;
        margin: 0;
        overflow-y: auto;
        max-height: 300px;
        /* Adjust as needed */
    }

    .appointment-request-item {
        display: flex;
        gap: 10px;
        padding: 10px 0;
        border-bottom: 1px solid #eee;
        align-items: center;
    }

    .appointment-request-item:last-child {
        border-bottom: none;
    }

    .patient-info {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-grow: 1;
    }

    .patient-avatar {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background-color: #ddd;
        color: #666;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 0.8em;
        flex-shrink: 0;
    }

    .patient-name {
        font-weight: bold;
        color: #333;
    }

    .appointment-date,
    .appointment-time {
        color: #555;
        font-size: 0.9em;
        white-space: nowrap;
    }

    .appointment-actions {
        display: flex;
        gap: 5px;
        flex-shrink: 0;
    }

    .accept-button,
    .reject-button {
        border: none;
        border-radius: 5px;
        padding: 8px 10px;
        cursor: pointer;
        font-size: 0.85em;
        transition: opacity 0.3s ease;
        text-decoration: none;
        /* Prevent text decoration if accidentally nested in <a> */
    }

    .accept-button {
        background-color: #4CAF50;
        color: white;
    }

    .reject-button {
        background-color: #F44336;
        color: white;
    }

    .accept-button:hover,
    .reject-button:hover {
        opacity: 0.8;
    }

    .no-requests {
        padding: 15px 0;
        color: #777;
        text-align: center;
        font-style: italic;
    }

    /* Responsive adjustments (adjust as needed) */
    @media (max-width: 768px) {
        .parent {
            grid-template-columns: 1fr;
        }

        .add-patient-doctor,
        .stats,
        .calender,
        .new-patient-list,
        .appointment-requests {
            grid-area: auto;
            margin-bottom: 20px;
        }
    }
</style>

<body>

    <div class="parent">
        <div class="add-patient-doctor">
            <h3>Add appointment to the schedule</h3>
            <a href="/it38b-Enterprise/routes/dashboard_router.php?page=schedule" class="add-appointment-button">+ Add
                Appointment</a>

            <div class="doctor-image-container">
                <img src="/it38b-Enterprise/resources/doctor.svg" alt="Doctor Illustration">
            </div>
        </div>
        <div class="stats">
            <div class="stat-line">
                <span class="stat-label">Total appointments this month</span>
                <span class="stat-number" id="total-appointments">0</span>
            </div>
            <div class="stat-line">
                <span class="stat-label">Total pending appointments this month</span>
                <span class="stat-number" id="pending-appointments">0</span>
            </div>
            <div class="stat-line">
                <span class="stat-label">Total completed appointments this month</span>
                <span class="stat-number" id="completed-appointments">0</span>
            </div>
        </div>
        <div class="calender">
            <?php include(__DIR__ . '/../components/pocket_calendar.php'); ?>
        </div>
        <div class="appointment-requests">
            <div class="appointment-requests-header">
                <h3>Appointment Requests</h3>
                <a href="appointments_all.php" class="view-all-button">View all</a>
            </div>
            <ul class="appointment-requests-list">
            </ul>
            <div class="no-requests">No new appointment requests.</div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const appointmentRequestsContainer = document.querySelector('.appointment-requests');
            const appointmentRequestsList = appointmentRequestsContainer.querySelector('.appointment-requests-list');
            const noRequestsMessage = appointmentRequestsContainer.querySelector('.no-requests');
            const totalAppointmentsSpan = document.getElementById('total-appointments');
            const pendingAppointmentsSpan = document.getElementById('pending-appointments');
            const completedAppointmentsSpan = document.getElementById('completed-appointments');

            // Function to fetch appointment requests from the database
            async function fetchAppointmentRequests() {
                try {
                    const response = await fetch('/it38b-Enterprise/api/appointments.php?status=Requested');
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const data = await response.json();

                    if (!data.success) {
                        throw new Error(data.error || 'Failed to load appointment requests');
                    }

                    const requests = data.appointments || [];

                    if (requests && requests.length > 0) {
                        noRequestsMessage.style.display = 'none';
                        appointmentRequestsList.style.display = 'block';
                        appointmentRequestsList.innerHTML = '';

                        requests.forEach(request => {
                            const appointmentDate = new Date(request.appointment_datetime);
                            const listItem = document.createElement('li');
                            listItem.classList.add('appointment-request-item');
                            listItem.innerHTML = `
                                <div class="patient-info">
                                    <div class="patient-avatar">
                                        <span>${request.patient_first_name.charAt(0).toUpperCase()}</span>
                                    </div>
                                    <span class="patient-name">${request.patient_first_name} ${request.patient_last_name}</span>
                                </div>
                                <span class="appointment-date">${appointmentDate.toLocaleDateString()}</span>
                                <span class="appointment-time">${appointmentDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                                <div class="appointment-actions">
                                    <button class="accept-button" data-id="${request.appointment_id}">Accept</button>
                                    <button class="reject-button" data-id="${request.appointment_id}">Reject</button>
                                </div>
                            `;
                            appointmentRequestsList.appendChild(listItem);
                        });
                    } else {
                        appointmentRequestsList.style.display = 'none';
                        noRequestsMessage.style.display = 'block';
                    }
                } catch (error) {
                    console.error('Error fetching appointment requests:', error);
                    appointmentRequestsList.innerHTML = '<li class="error-message">Failed to load appointment requests.</li>';
                    noRequestsMessage.style.display = 'none';
                }
            }

            // Function to handle accepting or rejecting appointment requests
            async function handleAppointmentAction(appointmentId, action) {
                try {
                    const response = await fetch(`/it38b-Enterprise/api/appointments.php?id=${appointmentId}&action=${action}`, {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    });

                    if (!response.ok) {
                        const errorData = await response.json();
                        throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
                    }

                    const result = await response.json();
                    if (result.success) {
                        // Show success message
                        alert(result.message || `Appointment ${action}ed successfully`);
                        // Reload the data
                        await fetchAppointmentRequests();
                        await fetchAppointmentStats();
                    } else {
                        throw new Error(result.message || 'Failed to process appointment');
                    }
                } catch (error) {
                    console.error(`Error ${action}ing appointment ${appointmentId}:`, error);
                    alert(`Failed to ${action} appointment: ${error.message}`);
                }
            }

            // Function to fetch appointment statistics
            async function fetchAppointmentStats() {
                try {
                    // Use the local proxy with absolute path
                    const response = await fetch('/it38b-Enterprise/views/nurse/appointment_stats_proxy.php');
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const data = await response.json();

                    if (data && data.success) {
                        const stats = data.stats || {};
                        totalAppointmentsSpan.textContent = stats.total || 0;
                        pendingAppointmentsSpan.textContent = stats.pending || 0;
                        completedAppointmentsSpan.textContent = stats.completed || 0;
                    } else {
                        throw new Error('Invalid statistics data received');
                    }
                } catch (error) {
                    console.error('Error fetching appointment stats:', error);
                    // Set default values on error
                    totalAppointmentsSpan.textContent = '0';
                    pendingAppointmentsSpan.textContent = '0';
                    completedAppointmentsSpan.textContent = '0';
                }
            }

            // Event listener for appointment actions
            appointmentRequestsList.addEventListener('click', (event) => {
                if (event.target.classList.contains('accept-button')) {
                    const requestId = event.target.dataset.id;
                    handleAppointmentAction(requestId, 'accept');
                } else if (event.target.classList.contains('reject-button')) {
                    const requestId = event.target.dataset.id;
                    handleAppointmentAction(requestId, 'reject');
                }
            });

            // Initial data load
            fetchAppointmentRequests();
            fetchAppointmentStats();
        });
    </script>
</body>