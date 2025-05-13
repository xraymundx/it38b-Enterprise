<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../functions/view_appointment.php';

// Ensure 'id' is present and valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /it38b-Enterprise/views/nurse/appointments_all.php?error=Invalid+appointment+ID");
    exit();
}

$appointmentId = intval($_GET['id']);

// Fetch appointment with all related information
$appointment = get_appointment_by_id($appointmentId);

// Redirect if appointment not found
if (!$appointment) {
    header("Location: /it38b-Enterprise/views/nurse/appointments_all.php?error=Appointment+not+found");
    exit();
}

// Format the appointment date and time
$appointmentDateTime = new DateTime($appointment['appointment_datetime']);
$formattedDate = $appointmentDateTime->format('F j, Y');
$formattedTime = $appointmentDateTime->format('g:i A');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Appointment Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Appointment Details</h1>
                <div class="flex space-x-3">
                    <a href="/it38b-Enterprise/routes/dashboard_router.php?page=appointments&status=all"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i> Back to List
                    </a>
                    <a href="/it38b-Enterprise/views/nurse/appointment_edit.php?id=<?php echo $appointmentId; ?>"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                        <i class="fas fa-edit mr-2"></i> Edit
                    </a>
                </div>
            </div>

            <div class="mb-8">
                <div
                    class="inline-block px-4 py-2 rounded-full <?php echo get_appointment_status_class($appointment['status']); ?>">
                    <span class="font-semibold"><?php echo htmlspecialchars($appointment['status']); ?></span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Basic Information -->
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Appointment Information</h2>

                    <div class="mb-4">
                        <p class="text-sm text-gray-500 mb-1">Appointment ID</p>
                        <p class="font-medium"><?php echo htmlspecialchars($appointment['appointment_id']); ?></p>
                    </div>

                    <div class="mb-4">
                        <p class="text-sm text-gray-500 mb-1">Date</p>
                        <p class="font-medium"><?php echo htmlspecialchars($formattedDate); ?></p>
                    </div>

                    <div class="mb-4">
                        <p class="text-sm text-gray-500 mb-1">Time</p>
                        <p class="font-medium"><?php echo htmlspecialchars($formattedTime); ?></p>
                    </div>

                    <div class="mb-4">
                        <p class="text-sm text-gray-500 mb-1">Reason for Visit</p>
                        <p class="font-medium"><?php echo htmlspecialchars($appointment['reason_for_visit']); ?></p>
                    </div>

                    <div class="mb-4">
                        <p class="text-sm text-gray-500 mb-1">Notes</p>
                        <p class="font-medium">
                            <?php echo !empty($appointment['notes']) ? nl2br(htmlspecialchars($appointment['notes'])) : 'None'; ?>
                        </p>
                    </div>
                </div>

                <!-- Patient & Doctor Information -->
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">People Information</h2>

                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-700 mb-3">Patient</h3>
                        <div class="mb-2">
                            <p class="text-sm text-gray-500 mb-1">Name</p>
                            <p class="font-medium">
                                <?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?>
                            </p>
                        </div>
                        <div class="mb-2">
                            <p class="text-sm text-gray-500 mb-1">Email</p>
                            <p class="font-medium"><?php echo htmlspecialchars($appointment['patient_email']); ?></p>
                        </div>
                        <a href="/it38b-Enterprise/functions/view_patient.php?id=<?php echo $appointment['patient_id']; ?>"
                            class="text-blue-600 hover:underline inline-flex items-center mt-2">
                            <i class="fas fa-user-circle mr-1"></i> View Patient Details
                        </a>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-700 mb-3">Doctor</h3>
                        <div class="mb-2">
                            <p class="text-sm text-gray-500 mb-1">Name</p>
                            <p class="font-medium">
                                Dr.
                                <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?>
                            </p>
                        </div>
                        <div class="mb-2">
                            <p class="text-sm text-gray-500 mb-1">Specialization</p>
                            <p class="font-medium"><?php echo htmlspecialchars($appointment['specialization_name']); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Medical Records & Billing Section -->
            <?php if ($appointment['status'] === 'Scheduled' || $appointment['status'] === 'Completed'): ?>
                <div class="mt-8 bg-gray-50 p-6 rounded-lg">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Medical & Billing Records</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="text-lg font-medium text-gray-700">Medical Records</h3>
                                <?php if ($appointment['status'] === 'Scheduled' || $appointment['status'] === 'Completed'): ?>
                                    <a href="/it38b-Enterprise/views/nurse/medical_record_form.php?appointment_id=<?php echo $appointmentId; ?>"
                                        class="px-3 py-1 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
                                        <i class="fas fa-plus mr-1"></i> Add Record
                                    </a>
                                <?php endif; ?>
                            </div>

                            <?php if (empty($appointment['medical_records'])): ?>
                                <p class="text-gray-500 italic">No medical records available.</p>
                                <p class="text-gray-500">Debug: <?php echo count($appointment['medical_records']); ?> records
                                    found.</p>
                            <?php else: ?>
                                <p class="text-gray-500">Debug: <?php echo count($appointment['medical_records']); ?> records
                                    found.</p>
                                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-300">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col"
                                                    class="py-3 pl-4 pr-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                                    Date</th>
                                                <th scope="col"
                                                    class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                                    Diagnosis</th>
                                                <th scope="col"
                                                    class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                                    Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white">
                                            <?php foreach ($appointment['medical_records'] as $record): ?>
                                                <tr>
                                                    <td class="whitespace-nowrap py-2 pl-4 pr-3 text-sm text-gray-500">
                                                        <?php echo htmlspecialchars($record['formatted_date']); ?>
                                                    </td>
                                                    <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-500">
                                                        <?php echo htmlspecialchars(substr($record['diagnosis'], 0, 30) . (strlen($record['diagnosis']) > 30 ? '...' : '')); ?>
                                                    </td>
                                                    <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-500">
                                                        <a href="/it38b-Enterprise/views/nurse/view_medical_record.php?id=<?php echo $record['record_id']; ?>&appointment_id=<?php echo $appointmentId; ?>"
                                                            class="text-blue-600 hover:text-blue-800">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="text-lg font-medium text-gray-700">Billing Records</h3>
                                <a href="/it38b-Enterprise/views/nurse/billing_record_form.php?appointment_id=<?php echo $appointmentId; ?>"
                                    class="px-3 py-1 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm">
                                    <i class="fas fa-plus mr-1"></i> Add Bill
                                </a>
                            </div>

                            <?php if (empty($appointment['billing_records'])): ?>
                                <p class="text-gray-500 italic">No billing records available.</p>
                            <?php else: ?>
                                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-300">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col"
                                                    class="py-3 pl-4 pr-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                                    Invoice</th>
                                                <th scope="col"
                                                    class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                                    Amount</th>
                                                <th scope="col"
                                                    class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                                    Status</th>
                                                <th scope="col"
                                                    class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                                    Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white">
                                            <?php foreach ($appointment['billing_records'] as $bill): ?>
                                                <tr>
                                                    <td class="whitespace-nowrap py-2 pl-4 pr-3 text-sm text-gray-500">
                                                        <?php echo !empty($bill['invoice_number']) ? htmlspecialchars($bill['invoice_number']) : 'N/A'; ?>
                                                    </td>
                                                    <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-500">
                                                        $<?php echo htmlspecialchars(number_format($bill['amount'], 2)); ?>
                                                    </td>
                                                    <td class="whitespace-nowrap px-3 py-2 text-sm">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                            <?php
                                                            switch ($bill['payment_status']) {
                                                                case 'Paid':
                                                                    echo 'bg-green-100 text-green-800';
                                                                    break;
                                                                case 'Partial':
                                                                    echo 'bg-yellow-100 text-yellow-800';
                                                                    break;
                                                                case 'Cancelled':
                                                                    echo 'bg-red-100 text-red-800';
                                                                    break;
                                                                default:
                                                                    echo 'bg-gray-100 text-gray-800';
                                                            }
                                                            ?>">
                                                            <?php echo htmlspecialchars($bill['payment_status']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-500">
                                                        <a href="/it38b-Enterprise/views/nurse/view_billing_record.php?id=<?php echo $bill['bill_id']; ?>&appointment_id=<?php echo $appointmentId; ?>"
                                                            class="text-blue-600 hover:text-blue-800">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="mt-8 flex justify-between border-t pt-6">
                <div>
                    <a href="/it38b-Enterprise/routes/dashboard_router.php?page=appointments&status=all"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        <i class="fas fa-arrow-left mr-1"></i> Back to List
                    </a>
                </div>

                <div class="space-x-3">
                    <?php if ($appointment['status'] === 'Requested'): ?>
                        <a href="/it38b-Enterprise/functions/update_appointment_status.php?id=<?php echo $appointmentId; ?>&status=Scheduled"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            <i class="fas fa-check mr-2"></i> Schedule
                        </a>
                        <a href="/it38b-Enterprise/functions/update_appointment_status.php?id=<?php echo $appointmentId; ?>&status=Cancelled"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            <i class="fas fa-times mr-2"></i> Reject
                        </a>
                    <?php elseif ($appointment['status'] === 'Scheduled'): ?>
                        <a href="/it38b-Enterprise/functions/update_appointment_status.php?id=<?php echo $appointmentId; ?>&status=Completed"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            <i class="fas fa-check-double mr-2"></i> Complete
                        </a>
                        <a href="/it38b-Enterprise/functions/update_appointment_status.php?id=<?php echo $appointmentId; ?>&status=No Show"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            <i class="fas fa-user-times mr-2"></i> No Show
                        </a>
                    <?php endif; ?>

                    <?php if ($appointment['status'] !== 'Cancelled'): ?>
                        <a href="/it38b-Enterprise/functions/update_appointment_status.php?id=<?php echo $appointmentId; ?>&status=Cancelled"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                            onclick="return confirm('Are you sure you want to cancel this appointment?')">
                            <i class="fas fa-ban mr-2"></i> Cancel Appointment
                        </a>
                    <?php endif; ?>

                    <a href="/it38b-Enterprise/functions/delete_appointment.php?id=<?php echo $appointmentId; ?>"
                        class="px-4 py-2 bg-red-700 text-white rounded-lg hover:bg-red-800 transition-colors"
                        onclick="return confirm('WARNING: This will permanently delete the appointment. This action cannot be undone. Are you sure?')">
                        <i class="fas fa-trash-alt mr-2"></i> Delete Permanently
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>