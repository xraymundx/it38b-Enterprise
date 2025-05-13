<?php
// Test script to check if the appointment statistics API is working correctly
$curl = curl_init('http://localhost/it38b-Enterprise/api/appointments.php?stats=month');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($curl);
curl_close($curl);

echo "API Response:\n";
echo $response;
echo "\n\n";

// Parse the JSON response
$data = json_decode($response, true);
echo "Parsed data:\n";
print_r($data);