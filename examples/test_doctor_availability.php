<?php
session_start();
require_once '../config/config.php';

// For testing purposes, simulate a nurse login
if (!isset($_SESSION['user_id'])) {
    // Find a nurse user in the database
    $query = "SELECT u.user_id FROM users u 
              JOIN nurses n ON u.user_id = n.user_id 
              LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $nurse = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $nurse['user_id'];
        $_SESSION['role'] = 'nurse';
    } else {
        die("No nurse user found in the database. Please create one first.");
    }
}

// Get the first doctor in the database for testing
$doctorQuery = "SELECT d.doctor_id, u.first_name, u.last_name FROM doctors d
                JOIN users u ON d.user_id = u.user_id
                LIMIT 1";
$doctorResult = mysqli_query($conn, $doctorQuery);

if (!$doctorResult || mysqli_num_rows($doctorResult) === 0) {
    die("No doctors found in the database. Please create a doctor first.");
}

$doctor = mysqli_fetch_assoc($doctorResult);
$doctor_id = $doctor['doctor_id'];
$doctor_name = $doctor['first_name'] . ' ' . $doctor['last_name'];

// Get today's date and a date one week from now for testing
$today = date('Y-m-d');
$nextWeek = date('Y-m-d', strtotime('+1 week'));

// Test scenarios

// 1. Get doctor's availability for today
echo "<h2>1. Getting doctor's availability for today ({$today})</h2>";
$availability = getDoctorAvailability($doctor_id, $today);
echo "Doctor $doctor_name's availability for $today:<br>";
echo "<pre>" . print_r($availability, true) . "</pre>";

// 2. Mark doctor as unavailable for next week
echo "<h2>2. Marking doctor as unavailable for $nextWeek</h2>";
$result = markDoctorUnavailable($doctor_id, $nextWeek, "Doctor on vacation");
echo "Result of marking doctor unavailable:<br>";
echo "<pre>" . print_r($result, true) . "</pre>";

// 3. Check availability for the date we just marked as unavailable
echo "<h2>3. Checking availability for the date marked as unavailable</h2>";
$availability = getDoctorAvailability($doctor_id, $nextWeek);
echo "Doctor $doctor_name's availability for $nextWeek:<br>";
echo "<pre>" . print_r($availability, true) . "</pre>";

// 4. Reset availability for next week to default schedule
echo "<h2>4. Resetting availability for $nextWeek to default schedule</h2>";
$result = resetDoctorAvailability($doctor_id, $nextWeek);
echo "Result of resetting doctor availability:<br>";
echo "<pre>" . print_r($result, true) . "</pre>";

// 5. Check availability again after reset
echo "<h2>5. Checking availability after reset</h2>";
$availability = getDoctorAvailability($doctor_id, $nextWeek);
echo "Doctor $doctor_name's availability for $nextWeek after reset:<br>";
echo "<pre>" . print_r($availability, true) . "</pre>";

// 6. Set specific availability for doctor on a date
echo "<h2>6. Setting specific availability for doctor on $nextWeek</h2>";
$timeSlots = ["09:00 AM", "10:00 AM", "11:00 AM"];
$result = setDoctorAvailability($doctor_id, $nextWeek, $timeSlots, "Limited hours for appointments");
echo "Result of setting specific availability:<br>";
echo "<pre>" . print_r($result, true) . "</pre>";

// 7. Check final availability
echo "<h2>7. Checking final availability</h2>";
$availability = getDoctorAvailability($doctor_id, $nextWeek);
echo "Doctor $doctor_name's final availability for $nextWeek:<br>";
echo "<pre>" . print_r($availability, true) . "</pre>";

// Helper functions

// Get doctor's availability
function getDoctorAvailability($doctor_id, $date)
{
    global $conn;

    // Format curl request to our API
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "http://localhost/it38b-Enterprise/api/doctor/availability.php?doctor_id=$doctor_id&date=$date",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIE => session_name() . '=' . session_id()
    ]);
    $response = curl_exec($curl);
    curl_close($curl);

    return json_decode($response, true);
}

// Mark doctor as unavailable
function markDoctorUnavailable($doctor_id, $date, $notes = "")
{
    global $conn;

    $data = [
        'doctor_id' => $doctor_id,
        'date' => $date,
        'timeSlots' => [], // Empty array means doctor is unavailable
        'notes' => $notes
    ];

    // Format curl request to our API
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "http://localhost/it38b-Enterprise/api/doctor/availability.php",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_COOKIE => session_name() . '=' . session_id()
    ]);
    $response = curl_exec($curl);
    curl_close($curl);

    return json_decode($response, true);
}

// Reset doctor availability to default schedule
function resetDoctorAvailability($doctor_id, $date)
{
    global $conn;

    // Format curl request to our API
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "http://localhost/it38b-Enterprise/api/doctor/availability.php?doctor_id=$doctor_id&date=$date",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "DELETE",
        CURLOPT_COOKIE => session_name() . '=' . session_id()
    ]);
    $response = curl_exec($curl);
    curl_close($curl);

    return json_decode($response, true);
}

// Set specific availability for doctor
function setDoctorAvailability($doctor_id, $date, $timeSlots, $notes = "")
{
    global $conn;

    $data = [
        'doctor_id' => $doctor_id,
        'date' => $date,
        'timeSlots' => $timeSlots,
        'notes' => $notes
    ];

    // Format curl request to our API
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "http://localhost/it38b-Enterprise/api/doctor/availability.php",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_COOKIE => session_name() . '=' . session_id()
    ]);
    $response = curl_exec($curl);
    curl_close($curl);

    return json_decode($response, true);
}