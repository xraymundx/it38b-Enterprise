<?php
// Ensure this file is in the 'views/patient/' directory

// Check if a record ID is provided in the query parameters
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<div class="p-4"><div class="bg-white rounded-lg shadow p-6"><p class="text-red-500">Invalid medical record ID.</p><p class="mt-2"><a href="?page=medical_records" class="text-blue-500 hover:underline">Back to Medical Records</a></p></div></div>';
    exit();
}

$recordId = intval($_GET['id']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Record Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Medical Record Details</h1>
                <a href="?page=medical_records"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Medical Records
                </a>
            </div>

            <div id="medical-record-details">
                <p class="text-gray-600">Loading medical record details...</p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            loadRecordDetails(<?php echo $recordId; ?>);
        });

        function loadRecordDetails(recordId) {
            fetch(`/it38b-Enterprise/api/patient/medical_records.php?action=view&id=${recordId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP error ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.record) {
                        displayRecordDetails(data.record);
                    } else {
                        document.getElementById('medical-record-details').innerHTML = `<p class="text-red-500">${data.error || 'Failed to load medical record details.'}</p><p class="mt-2"><a href="?page=medical_records" class="text-blue-500 hover:underline">Back to Medical Records</a></p>`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching record details:', error);
                    document.getElementById('medical-record-details').innerHTML = '<p class="text-red-500">Failed to load medical record details. Please try again later.</p><p class="mt-2"><a href="?page=medical_records" class="text-blue-500 hover:underline">Back to Medical Records</a></p>';
                });
        }

        function displayRecordDetails(record) {
            const container = document.getElementById('medical-record-details');
            container.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Record Information</h2>
                        <div class="mb-4">
                            <p class="text-sm text-gray-500 mb-1">Date & Time</p>
                            <p class="font-medium">${record.formatted_date} at ${record.formatted_time}</p>
                        </div>
                        <div class="mb-4">
                            <p class="text-sm text-gray-500 mb-1">Doctor</p>
                            <p class="font-medium">Dr. ${htmlspecialchars(record.doctor_first_name + ' ' + record.doctor_last_name)} (${htmlspecialchars(record.specialization_name)})</p>
                        </div>
                        ${record.appointment_id ? `
                            <div class="mb-4">
                                <p class="text-sm text-gray-500 mb-1">Appointment</p>
                                <p class="font-medium">#${record.appointment_id} (${record.appointment_date})</p>
                            </div>
                        ` : ''}
                    </div>

                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Diagnosis</h2>
                        <p class="text-gray-700">${htmlspecialchars(record.diagnosis)}</p>
                    </div>
                </div>

                ${record.prescribed_medications ? `
                    <div class="bg-gray-50 p-6 rounded-lg mb-8">
                        <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Prescribed Medications</h2>
                        <p class="text-gray-700">${htmlspecialchars(record.prescribed_medications).split('\\n').map(line => `<p>${line}</p>`).join('')}</p>
                    </div>
                ` : ''}

                ${record.test_results ? `
                    <div class="bg-gray-50 p-6 rounded-lg mb-8">
                        <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Test Results</h2>
                        <pre class="bg-gray-100 rounded-md p-4 text-sm font-mono">${htmlspecialchars(record.test_results)}</pre>
                    </div>
                ` : ''}
            `;
        }

        function htmlspecialchars(str) {
            if (typeof str !== 'string') return str;
            return str.replace(/[&<>"']/g, function (match) {
                switch (match) {
                    case '&': return '&amp;';
                    case '<': return '&lt;';
                    case '>': return '&gt;';
                    case '"': return '&quot;';
                    case "'": return '&#039;';
                    default: return match;
                }
            });
        }
    </script>
</body>

</html>