<?php
require_once __DIR__ . '/../../config/config.php';

// Check if user is logged in and is a nurse
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'nurse') {
    header('Location: /login.php');
    exit();
}
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
            margin-right: 4px;
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
        <?php
        // Display error messages
        if (isset($_GET['error'])) {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline">' . htmlspecialchars($_GET['error']) . '</span>
            </div>';
        }

        // Display success messages
        if (isset($_GET['success'])) {
            echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline">' . htmlspecialchars($_GET['success']) . '</span>
            </div>';
        }
        ?>
        <div class="flat-card p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Appointments</h1>
                <div class="flex space-x-4">

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
    <div id="appointmentModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-4">
                <h2 id="modalTitle" class="text-xl font-bold text-gray-800">New Appointment</h2>
                <button onclick="closeAppointmentModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="appointmentForm" class="space-y-4">
                <input type="hidden" name="appointment_id" id="appointment_id">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Patient</label>
                    <select name="patient_id" id="patient_id" required
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <option value="">Select Patient</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Doctor</label>
                    <select name="doctor_id" id="doctor_id" required
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <option value="">Select Doctor</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Date</label>
                    <input type="date" name="date" id="appointment_date" required
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Time</label>
                    <input type="time" name="time" id="appointment_time" required
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Reason for Visit</label>
                    <textarea name="reason" id="reason" required rows="3"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea name="notes" id="notes" rows="2"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"></textarea>
                </div>
                <div id="status-field" class="hidden">
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" id="status"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <option value="Requested">Requested</option>
                        <option value="Scheduled">Scheduled</option>
                        <option value="Completed">Completed</option>
                        <option value="No Show">No Show</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="mt-4 pt-2 border-t flex justify-between items-center">
                    <div id="edit-actions" class="hidden space-x-2">
                        <button type="button" id="attachMedicalRecordBtn"
                            class="px-3 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled>
                            <i class="fas fa-file-medical mr-1"></i> Attach Medical Record
                        </button>
                        <button type="button" id="attachBillingBtn"
                            class="px-3 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled>
                            <i class="fas fa-file-invoice-dollar mr-1"></i> Attach Billing
                        </button>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeAppointmentModal()"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" id="submitBtn"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Create Appointment
                        </button>
                    </div>
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
                            Swal.fire('Error', json.error || 'Failed to load appointments', 'error');
                            return [];
                        }
                        return json.appointments || [];
                    },
                    error: function (xhr, error, thrown) {
                        console.error('DataTables Error:', xhr, error, thrown);
                        Swal.fire('Error', 'Failed to load appointments: ' + (thrown || 'Server error'), 'error');
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
                            let actions = `
                                <a href="/it38b-Enterprise/views/nurse/appointment_view.php?id=${data.appointment_id}" class="action-button bg-blue-50 text-blue-600 hover:bg-blue-100">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="/it38b-Enterprise/views/nurse/appointment_edit.php?id=${data.appointment_id}" class="action-button bg-yellow-50 text-yellow-600 hover:bg-yellow-100">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="/it38b-Enterprise/functions/delete_appointment.php?id=${data.appointment_id}" class="action-button bg-red-50 text-red-600 hover:bg-red-100" 
                                   onclick="return confirm('WARNING: This will permanently delete the appointment. This action cannot be undone. Are you sure?')">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </a>`;

                            // Status-specific actions
                            if (data.status === 'Requested') {
                                actions += `
                                    <a href="/it38b-Enterprise/functions/update_appointment_status.php?id=${data.appointment_id}&status=Scheduled" class="action-button bg-green-50 text-green-600 hover:bg-green-100">
                                        <i class="fas fa-check"></i> Schedule
                                    </a>
                                    <a href="/it38b-Enterprise/functions/update_appointment_status.php?id=${data.appointment_id}&status=Cancelled" class="action-button bg-red-50 text-red-600 hover:bg-red-100">
                                        <i class="fas fa-times"></i> Reject
                                    </a>`;
                            } else if (data.status === 'Scheduled') {
                                actions += `
                                    <a href="/it38b-Enterprise/functions/update_appointment_status.php?id=${data.appointment_id}&status=Completed" class="action-button bg-green-50 text-green-600 hover:bg-green-100">
                                        <i class="fas fa-check-double"></i> Complete
                                    </a>
                                    <a href="/it38b-Enterprise/functions/update_appointment_status.php?id=${data.appointment_id}&status=No Show" class="action-button bg-red-50 text-red-600 hover:bg-red-100">
                                        <i class="fas fa-user-times"></i> No Show
                                    </a>`;
                            }

                            return `<div class="flex flex-wrap space-x-1 space-y-1">${actions}</div>`;
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

            // Form submission
            $('#appointmentForm').on('submit', function (e) {
                e.preventDefault();
                const isEdit = $('#appointment_id').val() !== '';

                // Collect form data
                const formData = {
                    doctor_id: $('#doctor_id').val(),
                    patient_id: $('#patient_id').val(),
                    date: $('#appointment_date').val(),
                    time: $('#appointment_time').val(),
                    reason: $('#reason').val(),
                    notes: $('#notes').val()
                };

                // Add appointment_id and status if editing
                if (isEdit) {
                    formData.appointment_id = $('#appointment_id').val();
                    formData.status = $('#status').val();
                }

                // API endpoint and method
                const method = isEdit ? 'PUT' : 'POST';

                // Submit the data
                $.ajax({
                    url: '/it38b-Enterprise/api/appointments.php',
                    type: method,
                    contentType: 'application/json',
                    data: JSON.stringify(formData),
                    success: function (response) {
                        if (response.success) {
                            Swal.fire('Success', isEdit ? 'Appointment updated successfully' : 'Appointment created successfully', 'success');
                            closeAppointmentModal();
                            table.ajax.reload();
                        } else {
                            Swal.fire('Error', response.error || 'Failed to process appointment', 'error');
                        }
                    },
                    error: function (xhr) {
                        let errorMessage = 'Server error occurred';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            errorMessage = response.error || errorMessage;
                        } catch (e) {
                            console.error('Error parsing error response:', e);
                        }
                        Swal.fire('Error', errorMessage, 'error');
                    }
                });
            });
        });

        function loadPatientsAndDoctors() {
            // Load patients
            $.ajax({
                url: '/it38b-Enterprise/api/patients.php',
                type: 'GET',
                success: function (response) {
                    const patientSelect = $('#patient_id');
                    patientSelect.find('option:not(:first)').remove();

                    if (response.success && response.patients) {
                        response.patients.forEach(function (patient) {
                            patientSelect.append(`<option value="${patient.patient_id}">${patient.first_name} ${patient.last_name}</option>`);
                        });
                    }
                },
                error: function () {
                    console.error('Failed to load patients');
                }
            });

            // Load doctors
            $.ajax({
                url: '/it38b-Enterprise/api/doctors.php',
                type: 'GET',
                success: function (response) {
                    const doctorSelect = $('#doctor_id');
                    doctorSelect.find('option:not(:first)').remove();

                    if (response.success && response.doctors) {
                        response.doctors.forEach(function (doctor) {
                            doctorSelect.append(`<option value="${doctor.doctor_id}">Dr. ${doctor.first_name} ${doctor.last_name}</option>`);
                        });
                    }
                },
                error: function () {
                    console.error('Failed to load doctors');
                }
            });
        }

        // Open modal for adding new appointment
        function openAddAppointmentModal() {
            resetForm();
            $('#modalTitle').text('New Appointment');
            $('#submitBtn').text('Create Appointment');
            $('#status-field').addClass('hidden');
            $('#edit-actions').addClass('hidden');
            $('#appointmentModal').show();
        }

        // Open modal for editing appointment
        function editAppointment(id) {
            resetForm();
            $('#modalTitle').text('Edit Appointment');
            $('#submitBtn').text('Update Appointment');
            $('#status-field').removeClass('hidden');
            $('#edit-actions').removeClass('hidden');
            $('#appointment_id').val(id);

            // Fetch appointment data
            $.ajax({
                url: `/it38b-Enterprise/api/appointments.php?id=${id}`,
                type: 'GET',
                success: function (response) {
                    if (response.success && response.appointment) {
                        const appointment = response.appointment;
                        const datetime = new Date(appointment.appointment_datetime);

                        // Format date as YYYY-MM-DD for input
                        const date = datetime.toISOString().split('T')[0];

                        // Format time as HH:MM for input
                        const hours = String(datetime.getHours()).padStart(2, '0');
                        const minutes = String(datetime.getMinutes()).padStart(2, '0');
                        const time = `${hours}:${minutes}`;

                        // Populate form fields
                        $('#patient_id').val(appointment.patient_id);
                        $('#doctor_id').val(appointment.doctor_id);
                        $('#appointment_date').val(date);
                        $('#appointment_time').val(time);
                        $('#reason').val(appointment.reason_for_visit);
                        $('#notes').val(appointment.notes);
                        $('#status').val(appointment.status);

                        // Enable medical record button for completed appointments
                        if (appointment.status === 'Completed') {
                            $('#attachMedicalRecordBtn').prop('disabled', false);
                            $('#attachBillingBtn').prop('disabled', false);
                        }

                        $('#appointmentModal').show();
                    } else {
                        Swal.fire('Error', response.error || 'Failed to load appointment details', 'error');
                    }
                },
                error: function (xhr) {
                    let errorMessage = 'Server error occurred';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMessage = response.error || errorMessage;
                    } catch (e) {
                        console.error('Error parsing error response:', e);
                    }
                    Swal.fire('Error', errorMessage, 'error');
                }
            });
        }

        // View appointment details
        function viewAppointment(id) {
            $.ajax({
                url: `/it38b-Enterprise/api/appointments.php?id=${id}`,
                type: 'GET',
                success: function (response) {
                    if (response.success && response.appointment) {
                        const appointment = response.appointment;

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
                            confirmButtonText: 'Close',
                            showCancelButton: appointment.status === 'Completed',
                            cancelButtonText: 'Medical Records',
                            cancelButtonColor: '#10B981'
                        }).then((result) => {
                            if (result.dismiss === Swal.DismissReason.cancel && appointment.status === 'Completed') {
                                // This will be implemented in the future
                                Swal.fire('Coming Soon', 'Medical records functionality will be available soon!', 'info');
                            }
                        });
                    } else {
                        Swal.fire('Error', response.error || 'Failed to load appointment details', 'error');
                    }
                },
                error: function (xhr) {
                    let errorMessage = 'Server error occurred';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMessage = response.error || errorMessage;
                    } catch (e) {
                        console.error('Error parsing error response:', e);
                    }
                    Swal.fire('Error', errorMessage, 'error');
                }
            });
        }

        // Update appointment status
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
                    $.ajax({
                        url: '/it38b-Enterprise/api/appointments.php',
                        type: 'PUT',
                        contentType: 'application/json',
                        data: JSON.stringify({
                            appointment_id: id,
                            status: newStatus
                        }),
                        success: function (response) {
                            if (response.success) {
                                Swal.fire('Success', 'Appointment status updated successfully', 'success');
                                $('#appointmentsTable').DataTable().ajax.reload();
                            } else {
                                Swal.fire('Error', response.error || 'Failed to update appointment status', 'error');
                            }
                        },
                        error: function (xhr) {
                            let errorMessage = 'Server error occurred';
                            try {
                                const response = JSON.parse(xhr.responseText);
                                errorMessage = response.error || errorMessage;
                            } catch (e) {
                                console.error('Error parsing error response:', e);
                            }
                            Swal.fire('Error', errorMessage, 'error');
                        }
                    });
                }
            });
        }

        // Reset form fields
        function resetForm() {
            $('#appointmentForm')[0].reset();
            $('#appointment_id').val('');
        }

        // Close modal
        function closeAppointmentModal() {
            $('#appointmentModal').hide();
            resetForm();
        }

        // Placeholder for future medical record attachment
        $(document).on('click', '#attachMedicalRecordBtn', function () {
            Swal.fire('Coming Soon', 'Medical record attachment functionality will be available soon!', 'info');
        });

        // Placeholder for future billing attachment
        $(document).on('click', '#attachBillingBtn', function () {
            Swal.fire('Coming Soon', 'Billing attachment functionality will be available soon!', 'info');
        });

        // Close modal when clicking outside
        window.onclick = function (event) {
            const modal = document.getElementById('appointmentModal');
            if (event.target === modal) {
                closeAppointmentModal();
            }
        }
    </script>
</body>

</html>