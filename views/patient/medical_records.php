<?php
// Ensure this file is in the 'views/patient/' directory
?>

<div class="p-4">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold">My Medical Records</h2>
        </div>

        <div id="medical-records-list">
            <p class="text-gray-600">Loading medical records...</p>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        loadMedicalRecords();
    });

    function loadMedicalRecords() {
        fetch('/it38b-Enterprise/api/patient/medical_records.php?action=list')
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    displayMedicalRecords(data.records);
                } else {
                    document.getElementById('medical-records-list').innerHTML = `<p class="text-red-500">${data.error || 'Failed to load medical records.'}</p>`;
                }
            })
            .catch(error => {
                console.error('Error fetching medical records:', error);
                document.getElementById('medical-records-list').innerHTML = '<p class="text-red-500">Failed to load medical records. Please try again later.</p>';
            });
    }

    function displayMedicalRecords(records) {
        const container = document.getElementById('medical-records-list');
        container.innerHTML = '';

        if (records && records.length > 0) {
            const table = document.createElement('table');
            table.className = 'min-w-full divide-y divide-gray-200';

            const thead = document.createElement('thead');
            thead.className = 'bg-gray-50';
            thead.innerHTML = `
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diagnosis</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            `;
            table.appendChild(thead);

            const tbody = document.createElement('tbody');
            tbody.className = 'bg-white divide-y divide-gray-200';
            records.forEach(record => {
                const row = tbody.insertRow();
                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">${record.formatted_date}</div>
                        <div class="text-sm text-gray-500">${record.formatted_time}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">Dr. ${htmlspecialchars(record.doctor_first_name + ' ' + record.doctor_last_name)}</div>
                        <div class="text-sm text-gray-500">${htmlspecialchars(record.specialization_name)}</div>
                    </td>
                    <td class="px-6 py-4">${htmlspecialchars(record.short_diagnosis)}${record.diagnosis.length > 100 ? '...' : ''}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="?page=medical_records&view=details&id=${record.record_id}"
                           class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            View Details
                        </a>
                    </td>
                `;
            });
            table.appendChild(tbody);
            container.appendChild(table);
        } else {
            container.innerHTML = '<p class="text-gray-500">No medical records found.</p>';
        }
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