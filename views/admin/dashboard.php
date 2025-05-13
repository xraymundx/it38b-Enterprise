<?php

// Include database connection
require_once __DIR__ . '/../../config/config.php';

// Fetch basic statistics
$userCountQuery = "SELECT COUNT(*) AS total_users FROM users";
$userCountResult = mysqli_query($conn, $userCountQuery);
$totalUsers = mysqli_fetch_assoc($userCountResult)['total_users'] ?? 0;

$doctorCountQuery = "SELECT COUNT(*) AS total_doctors FROM doctors";
$doctorCountResult = mysqli_query($conn, $doctorCountQuery);
$totalDoctors = mysqli_fetch_assoc($doctorCountResult)['total_doctors'] ?? 0;

$patientCountQuery = "SELECT COUNT(*) AS total_patients FROM patients";
$patientCountResult = mysqli_query($conn, $patientCountQuery);
$totalPatients = mysqli_fetch_assoc($patientCountResult)['total_patients'] ?? 0;

$appointmentCountQuery = "SELECT COUNT(*) AS total_appointments FROM appointments";
$appointmentCountResult = mysqli_query($conn, $appointmentCountQuery);
$totalAppointments = mysqli_fetch_assoc($appointmentCountResult)['total_appointments'] ?? 0;

$nurseCountQuery = "SELECT COUNT(*) AS total_nurses FROM nurses";
$nurseCountResult = mysqli_query($conn, $nurseCountQuery);
$totalNurses = mysqli_fetch_assoc($nurseCountResult)['total_nurses'] ?? 0;

// You can add more queries here for other statistics
?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-semibold mb-4">Admin Dashboard</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white shadow-md rounded-md p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 font-semibold">Total Users</p>
                    <p class="text-xl font-bold text-blue-500">
                        <?php echo htmlspecialchars($totalUsers); ?>
                    </p>
                </div>
                <div class="text-blue-500">
                    <span class="material-symbols-outlined">group</span>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-md rounded-md p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 font-semibold">Total Doctors</p>
                    <p class="text-xl font-bold text-green-500">
                        <?php echo htmlspecialchars($totalDoctors); ?>
                    </p>
                </div>
                <div class="text-green-500">
                    <span class="material-symbols-outlined">local_hospital</span>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-md rounded-md p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 font-semibold">Total Patients</p>
                    <p class="text-xl font-bold text-indigo-500">
                        <?php echo htmlspecialchars($totalPatients); ?>
                    </p>
                </div>
                <div class="text-indigo-500">
                    <span class="material-symbols-outlined">person</span>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-md rounded-md p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 font-semibold">Total Appointments</p>
                    <p class="text-xl font-bold text-yellow-500">
                        <?php echo htmlspecialchars($totalAppointments); ?>
                    </p>
                </div>
                <div class="text-yellow-500">
                    <span class="material-symbols-outlined">calendar_today</span>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-md rounded-md p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 font-semibold">Total Nurses</p>
                    <p class="text-xl font-bold text-teal-500"><?php echo htmlspecialchars($totalNurses); ?></p>
                </div>
                <div class="text-teal-500">
                    <span class="material-symbols-outlined">local_hospital</span>
                </div>
            </div>
        </div>

    </div>

    <div class="bg-white shadow-md rounded-md p-4 mb-6">
        <h2 class="text-lg font-semibold mb-2">Recent Activity</h2>
        <p class="text-gray-600">No recent activity logs available at the moment.</p>
    </div>

    <div class="bg-white shadow-md rounded-md p-4">
        <h2 class="text-lg font-semibold mb-2">Quick Actions</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
            <a href="?page=create_user" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <span class="material-symbols-outlined align-middle">person_add</span> Add User
            </a>
            <a href="?page=create_doctor"                
                class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                <span class="material-symbols-outlined align-middle">group_add</span> Add Doctor
            </a>
            <a href="?page=create_patient"                
                class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                <span class="material-symbols-outlined align-middle">person_add</span> Add Patient
            </a>
            <a href="?page=create_nurse" class="bg-teal-500 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded">
                <span class="material-symbols-outlined align-middle">person_add</span> Add Nurse
            </a>
            <a href="?page=appointments"                
                class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                <span class="material-symbols-outlined align-middle">calendar_month</span> Manage
                Appointments
            </a>
            <a href="?page=specializations"                
                class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                <span class="material-symbols-outlined align-middle">category</span> Manage
                Specializations
            </a>

        </div>
    </div>
</div>