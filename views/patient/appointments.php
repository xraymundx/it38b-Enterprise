<?php
// Ensure this file is in the 'views/patient/' directory
?>

<div class="p-4">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold">My Appointments</h2>
            <a href="?page=appointments&view=request"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                <span class="material-symbols-outlined mr-2">add</span> Request Appointment
            </a>
        </div>

        <div id="appointments-list">
            <p class="text-gray-600">Loading appointments...</p>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        loadAppointments();
    });

    function loadAppointments(status = null) {
        let url = '/it38b-Enterprise/api/patient/appointments.php?action=list';
        if (status) {
            url += `&status=${status}`;
        }

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    displayAppointments(data.appointments);
                } else {
                    document.getElementById('appointments-list').innerHTML = `<p class="text-red-500">${data.error || 'Failed to load appointments.'}</p>`;
                }
            })
            .catch(error => {
                console.error('Error fetching appointments:', error);
                document.getElementById('appointments-list').innerHTML = '<p class="text-red-500">Failed to load appointments. Please try again later.</p>';
            });
    }

    function displayAppointments(appointments) {
        const container = document.getElementById('appointments-list');
        container.innerHTML = '';

        if (appointments && appointments.length > 0) {
            const table = document.createElement('table');
            table.className = 'min-w-full divide-y divide-gray-200';

            const thead = document.createElement('thead');
            thead.className = 'bg-gray-50';
            thead.innerHTML = `
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        `;
            table.appendChild(thead);

            const tbody = document.createElement('tbody');
            tbody.className = 'bg-white divide-y divide-gray-200';
            appointments.forEach(appointment => {
                const row = tbody.insertRow();
                row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">${appointment.formatted_date}</div>
                    <div class="text-sm text-gray-500">${appointment.formatted_time}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">Dr. ${htmlspecialchars(appointment.doctor_first_name + ' ' + appointment.doctor_last_name)}</div>
                    <div class="text-sm text-gray-500">${htmlspecialchars(appointment.specialization_name)}</div>
                </td>
                <td class="px-6 py-4">${htmlspecialchars(appointment.reason)}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                         ${getStatusColorClass(appointment.status)}">
                        ${appointment.status ? ucfirst(appointment.status) : ''}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium flex space-x-2">
                    ${appointment.status === 'Scheduled' || appointment.status === 'Requested' ? `
                        <button onclick="cancelAppointment(${appointment.appointment_id})"
                                class="inline-flex items-center px-2.5 py-1.5 border border-red-500 text-red-500 rounded-md text-xs font-semibold hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                            Cancel
                        </button>
                        ${appointment.status === 'Scheduled' ? `
                            <a href="?page=appointments&view=reschedule&id=${appointment.appointment_id}"
                               class="inline-flex items-center px-2.5 py-1.5 border border-blue-500 text-blue-500 rounded-md text-xs font-semibold hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Reschedule
                            </a>
                        ` : ''}
                    ` : ''}
                </td>
            `;
            });
            table.appendChild(tbody);
            container.appendChild(table);
        } else {
            container.innerHTML = `
            <div class="text-center py-4">
                <p class="text-gray-500 mb-4">No appointments found.</p>
                <a href="?page=appointments&view=request"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    <span class="material-symbols-outlined mr-2">add</span> Request New Appointment
                </a>
            </div>
        `;
        }
    }

    function cancelAppointment(appointmentId) {
        if (confirm('Are you sure you want to cancel this appointment?')) {
            fetch(`/it38b-Enterprise/api/patient/appointments.php?action=cancel&id=${appointmentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Appointment cancelled successfully');
                        loadAppointments(); // Reload appointments list
                    } else {
                        alert('Failed to cancel appointment: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to cancel appointment. Please try again later.');
                });
        }
    }

    function getStatusColorClass(status) {
        switch (status) {
            case 'Completed':
                return 'bg-green-100 text-green-800';
            case 'Scheduled':
                return 'bg-blue-100 text-blue-800';
            case 'Cancelled':
                return 'bg-red-100 text-red-800';
            case 'Requested':
                return 'bg-yellow-100 text-yellow-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }

    function ucfirst(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
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