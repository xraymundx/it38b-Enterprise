<?php
require '../config/config.php';
require_once __DIR__ . '/../../config/config.php';


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 max-w-2xl mx-auto">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Patient Settings</h1>

            <div id="settings-message" class="mb-4">
            </div>

            <form id="patient-settings-form" class="grid grid-cols-1 gap-6">
                <div>
                    <label for="date_of_birth" class="block text-gray-700 text-sm font-bold mb-2">Date of Birth:</label>
                    <input type="date" id="date_of_birth" name="date_of_birth"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div>
                    <label for="gender" class="block text-gray-700 text-sm font-bold mb-2">Gender:</label>
                    <select id="gender" name="gender"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div>
                    <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Description:</label>
                    <textarea id="description" name="description"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                </div>

                <div>
                    <label for="address" class="block text-gray-700 text-sm font-bold mb-2">Address:</label>
                    <textarea id="address" name="address"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                </div>

                <div>
                    <label for="medical_record_number" class="block text-gray-700 text-sm font-bold mb-2">Medical Record
                        Number:</label>
                    <input type="text" id="medical_record_number" name="medical_record_number"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div>
                    <label for="insurance_provider" class="block text-gray-700 text-sm font-bold mb-2">Insurance
                        Provider:</label>
                    <input type="text" id="insurance_provider" name="insurance_provider"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div>
                    <label for="insurance_policy_number" class="block text-gray-700 text-sm font-bold mb-2">Insurance
                        Policy Number:</label>
                    <input type="text" id="insurance_policy_number" name="insurance_policy_number"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div>
                    <label for="emergency_contact_name" class="block text-gray-700 text-sm font-bold mb-2">Emergency
                        Contact Name:</label>
                    <input type="text" id="emergency_contact_name" name="emergency_contact_name"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div>
                    <label for="emergency_contact_phone" class="block text-gray-700 text-sm font-bold mb-2">Emergency
                        Contact Phone:</label>
                    <input type="text" id="emergency_contact_phone" name="emergency_contact_phone"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div>
                    <label for="notes" class="block text-gray-700 text-sm font-bold mb-2">Notes:</label>
                    <textarea id="notes" name="notes"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                </div>

                <div>
                    <button type="submit"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Save Changes
                    </button>
                </div>
            </form>

            <div class="mt-6">
                <a href="/it38b-Enterprise/routes/dashboard_router.php?page=dashboard"
                    class="text-blue-500 hover:underline">Back to Dashboard</a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            loadPatientSettings();

            const settingsForm = document.getElementById('patient-settings-form');
            settingsForm.addEventListener('submit', function (event) {
                event.preventDefault();
                updatePatientSettings();
            });
        });

        async function loadPatientSettings() {
            try {
                const response = await fetch('/it38b-Enterprise/api/patient/settings.php?action=view');
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();
                if (data.success && data.patient) {
                    populateForm(data.patient);
                } else {
                    displayMessage('error', data.error || 'Failed to load settings.');
                }
            } catch (error) {
                console.error('Error loading settings:', error);
                displayMessage('error', 'Failed to load settings. Please try again.');
            }
        }

        function populateForm(patient) {
            document.getElementById('date_of_birth').value = patient.date_of_birth || '';
            document.getElementById('gender').value = patient.gender || '';
            document.getElementById('description').value = patient.description || '';
            document.getElementById('address').value = patient.address || '';
            document.getElementById('medical_record_number').value = patient.medical_record_number || '';
            document.getElementById('insurance_provider').value = patient.insurance_provider || '';
            document.getElementById('insurance_policy_number').value = patient.insurance_policy_number || '';
            document.getElementById('emergency_contact_name').value = patient.emergency_contact_name || '';
            document.getElementById('emergency_contact_phone').value = patient.emergency_contact_phone || '';
            document.getElementById('notes').value = patient.notes || '';
        }

        async function updatePatientSettings() {
            const formData = new FormData(document.getElementById('patient-settings-form'));

            try {
                const response = await fetch('/it38b-Enterprise/api/patient/settings.php?action=update', {
                    method: 'POST',
                    body: formData,
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                displayMessage(data.success ? 'success' : 'error', data.message || data.error || 'Update failed.');

            } catch (error) {
                console.error('Error updating settings:', error);
                displayMessage('error', 'Failed to update settings. Please try again.');
            }
        }

        function displayMessage(type, message) {
            const messageDiv = document.getElementById('settings-message');
            messageDiv.innerHTML = `<div class="${type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'} px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">${type === 'success' ? 'Success!' : 'Error!'}</strong>
                <span class="block sm:inline">${message}</span>
            </div>`;
            // Optionally clear the message after a few seconds
            setTimeout(() => {
                messageDiv.innerHTML = '';
            }, 5000);
        }
    </script>
</body>

</html>