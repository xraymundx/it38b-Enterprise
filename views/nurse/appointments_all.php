<?php
require_once __DIR__ . '/../../config/config.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Appointments</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.3.0/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" type="text/css"
        href="https://cdn.datatables.net/buttons/3.2.3/css/buttons.dataTables.min.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script type="text/javascript" src="https://cdn.datatables.net/2.3.0/js/dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/3.2.3/js/dataTables.buttons.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        .flat-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .action-button {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .action-button:hover {
            transform: translateY(-1px);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 500px;
            margin: 50px auto;
            padding: 24px;
            position: relative;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="flat-card p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Appointments</h1>
                <div class="flex space-x-4">
                    <button onclick="openAddAppointmentModal()"
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                        <i class="fas fa-plus mr-2"></i> New Appointment
                    </button>
                    <select id="statusFilter"
                        class="rounded-lg border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <option value="">All Status</option>
                        <option value="Requested">Requested</option>
                        <option value="Scheduled">Scheduled</option>
                        <option value="Completed">Completed</option>
                        <option value="No Show">No Show</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
            </div>

            <table id="appointmentsTable" class="w-full">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date & Time</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- Add Appointment Modal -->
    <div id="addAppointmentModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800">New Appointment</h2>
                <button onclick="closeAddAppointmentModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="addAppointmentForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Patient</label>
                    <select name="patient_id" required
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <option value="">Select Patient</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Doctor</label>
                    <select name="doctor_id" required
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <option value="">Select Doctor</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Date</label>
                    <input type="date" name="date" required
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Time</label>
                    <input type="time" name="time" required
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Reason for Visit</label>
                    <textarea name="reason" required rows="3"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea name="notes" rows="2"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeAddAppointmentModal()"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Create Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            // Initialize DataTable
            const table = $('#appointmentsTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '/it38b-Enterprise/api/appointments.php',
                    dataSrc: function (json) {
                        if (!json.success) {
                            console.error('API Error:', json.error);
                            return [];
                        }
                        return json.appointments || [];
                    },
                    error: function (xhr, error, thrown) {
                        console.error('DataTables Error:', error, thrown);
                        Swal.fire('Error', 'Failed to load appointments', 'error');
                    }
                },
                columns: [
                    { data: 'appointment_id' },
                    {
                        data: 'appointment_datetime',
                        render: function (data) {
                            return new Date(data).toLocaleString();
                        }
                    },
                    {
                        data: null,
                        render: function (data) {
                            return `${data.patient_first_name} ${data.patient_last_name}`;
                        }
                    },
                    {
                        data: null,
                        render: function (data) {
                            return `Dr. ${data.doctor_first_name} ${data.doctor_last_name}`;
                        }
                    },
                    { data: 'reason_for_visit' },
                    {
                        data: 'status',
                        render: function (data) {
                            const statusColors = {
                                'Requested': 'bg-yellow-100 text-yellow-800',
                                'Scheduled': 'bg-blue-100 text-blue-800',
                                'Completed': 'bg-green-100 text-green-800',
                                'No Show': 'bg-red-100 text-red-800',
                                'Cancelled': 'bg-gray-100 text-gray-800'
                            };
                            return `<span class="status-badge ${statusColors[data] || 'bg-gray-100 text-gray-800'}">${data}</span>`;
                        }
                    },
                    {
                        data: null,
                        render: function (data) {
                            let actions = '';

                            // View action for all statuses
                            actions += `<button onclick="viewAppointment(${data.appointment_id})" class="action-button bg-blue-50 text-blue-600 hover:bg-blue-100">
                                        <i class="fas fa-eye"></i> View
                                      </button>`;

                            // Status-specific actions
                            if (data.status === 'Requested') {
                                actions += `
                                    <button onclick="updateAppointmentStatus(${data.appointment_id}, 'Scheduled')" class="action-button bg-green-50 text-green-600 hover:bg-green-100">
                                        <i class="fas fa-check"></i> Schedule
                                    </button>
                                    <button onclick="updateAppointmentStatus(${data.appointment_id}, 'Cancelled')" class="action-button bg-red-50 text-red-600 hover:bg-red-100">
                                        <i class="fas fa-times"></i> Reject
                                    </button>`;
                            } else if (data.status === 'Scheduled') {
                                actions += `
                                    <button onclick="updateAppointmentStatus(${data.appointment_id}, 'Completed')" class="action-button bg-green-50 text-green-600 hover:bg-green-100">
                                        <i class="fas fa-check-double"></i> Complete
                                    </button>
                                    <button onclick="updateAppointmentStatus(${data.appointment_id}, 'No Show')" class="action-button bg-red-50 text-red-600 hover:bg-red-100">
                                        <i class="fas fa-user-times"></i> No Show
                                    </button>`;
                            }

                            return `<div class="flex space-x-2">${actions}</div>`;
                        }
                    }
                ],
                order: [[1, 'desc']],
                pageLength: 10,
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                language: {
                    processing: '<div class="flex items-center justify-center"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div></div>',
                    emptyTable: 'No appointments found',
                    zeroRecords: 'No matching appointments found'
                }
            });

            // Status filter
            $('#statusFilter').on('change', function () {
                table.column(5).search(this.value).draw();
            });

            // Load patients and doctors for the form
            loadPatientsAndDoctors();

            // Handle form submission
            $('#addAppointmentForm').on('submit', function (e) {
                e.preventDefault();
                const formData = new FormData(this);
                const data = Object.fromEntries(formData.entries());

                fetch('/it38b-Enterprise/api/appointments.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            Swal.fire('Success', 'Appointment created successfully', 'success');
                            closeAddAppointmentModal();
                            table.ajax.reload();
                        } else {
                            Swal.fire('Error', result.error || 'Failed to create appointment', 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error', 'Failed to create appointment', 'error');
                    });
            });
        });

        // Function to load patients and doctors
        function loadPatientsAndDoctors() {
            // Load patients
            fetch('/it38b-Enterprise/api/patients.php')
                .then(response => response.json())
                .then(data => {
                    const patientSelect = document.querySelector('select[name="patient_id"]');
                    data.forEach(patient => {
                        const option = document.createElement('option');
                        option.value = patient.patient_id;
                        option.textContent = `${patient.first_name} ${patient.last_name}`;
                        patientSelect.appendChild(option);
                    });
                });

            // Load doctors
            fetch('/it38b-Enterprise/api/doctors.php')
                .then(response => response.json())
                .then(data => {
                    const doctorSelect = document.querySelector('select[name="doctor_id"]');
                    data.forEach(doctor => {
                        const option = document.createElement('option');
                        option.value = doctor.doctor_id;
                        option.textContent = `Dr. ${doctor.first_name} ${doctor.last_name}`;
                        doctorSelect.appendChild(option);
                    });
                });
        }

        // Modal functions
        function openAddAppointmentModal() {
            document.getElementById('addAppointmentModal').style.display = 'block';
        }

        function closeAddAppointmentModal() {
            document.getElementById('addAppointmentModal').style.display = 'none';
            document.getElementById('addAppointmentForm').reset();
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const modal = document.getElementById('addAppointmentModal');
            if (event.target === modal) {
                closeAddAppointmentModal();
            }
        }

        // Existing functions
        function viewAppointment(id) {
            Swal.fire({
                title: 'Appointment Details',
                html: 'Loading...',
                showConfirmButton: false,
                allowOutsideClick: false
            });

            fetch(`/it38b-Enterprise/api/appointments.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const appointment = data.appointment;
                        Swal.fire({
                            title: 'Appointment Details',
                            html: `
                                <div class="text-left">
                                    <p><strong>Date & Time:</strong> ${new Date(appointment.appointment_datetime).toLocaleString()}</p>
                                    <p><strong>Patient:</strong> ${appointment.patient_first_name} ${appointment.patient_last_name}</p>
                                    <p><strong>Doctor:</strong> Dr. ${appointment.doctor_first_name} ${appointment.doctor_last_name}</p>
                                    <p><strong>Reason:</strong> ${appointment.reason_for_visit}</p>
                                    <p><strong>Status:</strong> ${appointment.status}</p>
                                    <p><strong>Notes:</strong> ${appointment.notes || 'None'}</p>
                                </div>
                            `,
                            confirmButtonText: 'Close'
                        });
                    } else {
                        Swal.fire('Error', 'Failed to load appointment details', 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('Error', 'Failed to load appointment details', 'error');
                });
        }

        function updateAppointmentStatus(id, newStatus) {
            Swal.fire({
                title: 'Confirm Status Update',
                text: `Are you sure you want to mark this appointment as ${newStatus}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, update it',
                cancelButtonText: 'No, cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('/it38b-Enterprise/api/appointments.php', {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            appointment_id: id,
                            status: newStatus
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Success', 'Appointment status updated successfully', 'success');
                                $('#appointmentsTable').DataTable().ajax.reload();
                            } else {
                                Swal.fire('Error', data.error || 'Failed to update appointment status', 'error');
                            }
                        })
                        .catch(error => {
                            Swal.fire('Error', 'Failed to update appointment status', 'error');
                        });
                }
            });
        }
    </script>
</body>

</html>