<?php
// Include necessary files and establish database connection
require_once __DIR__ . '/../../config/config.php';

// Fetch actual data from the database
global $conn;

// Get total clinic earnings from billing records
$earningsQuery = "SELECT SUM(amount) as total_earnings FROM billingrecords WHERE payment_status = 'Paid'";
$earningsResult = mysqli_query($conn, $earningsQuery);
$earningsData = mysqli_fetch_assoc($earningsResult);
$totalEarnings = $earningsData['total_earnings'] ? number_format($earningsData['total_earnings'], 2) : '0.00';

// Get total patients count
$patientsQuery = "SELECT COUNT(*) as total_patients FROM patients";
$patientsResult = mysqli_query($conn, $patientsQuery);
$patientsData = mysqli_fetch_assoc($patientsResult);
$totalPatients = $patientsData['total_patients'];

// Get total appointments count
$appointmentsQuery = "SELECT COUNT(*) as total_appointments FROM appointments";
$appointmentsResult = mysqli_query($conn, $appointmentsQuery);
$appointmentsData = mysqli_fetch_assoc($appointmentsResult);
$totalAppointments = $appointmentsData['total_appointments'];
?>

<style>
    .dashboard-cards-container {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .dashboard-card {
        flex: 1;
        min-width: 200px;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        padding: 15px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        box-sizing: border-box;
        color: white;
    }

    .dashboard-card-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        font-size: 2em;
        opacity: 0.8;
    }

    .dashboard-card-icon .material-symbols-outlined {
        font-size: inherit;
        /* Inherit size from parent */
    }

    .dashboard-card-title {
        font-size: 0.9em;
        margin-bottom: 5px;
        opacity: 0.9;
    }

    .dashboard-card-value {
        font-size: 1.8em;
        font-weight: bold;
    }

    /* Specific background colors for each card */
    .card-earnings {
        background-color: #3f51b5;
        /* Blue */
    }

    .card-patients {
        background-color: #ffc107;
        /* Yellow */
    }

    .card-appointments {
        background-color: #4caf50;
        /* Green */
    }

    .card-earnings .dashboard-card-icon {
        background-color: rgba(255, 255, 255, 0.2);
    }

    .card-patients .dashboard-card-icon {
        background-color: rgba(255, 255, 255, 0.47);
    }

    .card-appointments .dashboard-card-icon {
        background-color: rgba(255, 255, 255, 0.2);
    }
</style>

<div class="dashboard-cards-container">
    <div class="dashboard-card card-earnings">
        <div class="dashboard-card-icon">
            <span class="material-symbols-outlined">paid</span>
        </div>
        <div class="dashboard-card-info">
            <div class="dashboard-card-title">Clinic Earnings</div>
            <div class="dashboard-card-value"><?php echo $totalEarnings; ?> PHP</div>
        </div>
    </div>

    <div class="dashboard-card card-patients">
        <div class="dashboard-card-icon">
            <span class="material-symbols-outlined">person</span>
        </div>
        <div class="dashboard-card-info">
            <div class="dashboard-card-title">Total Patients</div>
            <div class="dashboard-card-value"><?php echo $totalPatients; ?></div>
        </div>
    </div>

    <div class="dashboard-card card-appointments">
        <div class="dashboard-card-icon">
            <span class="material-symbols-outlined">calendar_month</span>
        </div>
        <div class="dashboard-card-info">
            <div class="dashboard-card-title">Appointments</div>
            <div class="dashboard-card-value"><?php echo $totalAppointments; ?></div>
        </div>
    </div>
</div>