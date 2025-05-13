<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../functions/view_appointment.php';
require_once __DIR__ . '/../../functions/edit_appointment.php';

// Initialize variables
$appointmentId = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;
$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $data = [
        'appointment_id' => isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0,
        'patient_id' => isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0,
        'description' => isset($_POST['description']) ? $_POST['description'] : '',
        'amount' => isset($_POST['amount']) ? $_POST['amount'] : 0,
        'payment_status' => isset($_POST['payment_status']) ? $_POST['payment_status'] : 'Pending',
        'payment_method' => isset($_POST['payment_method']) ? $_POST['payment_method'] : '',
        'invoice_number' => isset($_POST['invoice_number']) ? $_POST['invoice_number'] : '',
        'notes' => isset($_POST['notes']) ? $_POST['notes'] : '',
        'record_id' => isset($_POST['record_id']) ? intval($_POST['record_id']) : null
    ];

    // Call add_billing_record function
    $result = add_billing_record($data);

    if ($result['success']) {
        // Redirect to the appointment view page with success message
        header("Location: /it38b-Enterprise/views/nurse/appointment_view.php?id={$data['appointment_id']}&success=Billing+record+added+successfully");
        exit();
    } else {
        // Store error message
        $error = $result['error'];
    }
}

// Get appointment details if ID is provided
if ($appointmentId > 0) {
    $appointment = get_appointment_by_id($appointmentId);
    if (!$appointment) {
        header("Location: /it38b-Enterprise/routes/dashboard_router.php?page=billing_records&error=Invalid+appointment+ID");
        exit();
    }

    // Get medical records associated with this appointment
    $medicalRecords = get_appointment_medical_records($appointmentId);
} else {
    header("Location: /it38b-Enterprise/routes/dashboard_router.php?page=billing_records&error=Missing+appointment+ID");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Billing Record</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Add Billing Record</h1>
                <a href="/it38b-Enterprise/views/nurse/appointment_view.php?id=<?php echo $appointmentId; ?>"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Appointment
                </a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6"
                    role="alert">
                    <strong class="font-bold">Success!</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <div class="mb-6 bg-blue-50 p-4 rounded-lg">
                <h2 class="text-lg font-semibold text-blue-800 mb-2">Appointment Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Patient:</p>
                        <p class="font-medium">
                            <?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Doctor:</p>
                        <p class="font-medium">Dr.
                            <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Date & Time:</p>
                        <p class="font-medium">
                            <?php
                            $appointmentDateTime = new DateTime($appointment['appointment_datetime']);
                            echo htmlspecialchars($appointmentDateTime->format('F j, Y g:i A'));
                            ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Status:</p>
                        <p class="font-medium"><?php echo htmlspecialchars($appointment['status']); ?></p>
                    </div>
                </div>
            </div>

            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="space-y-6">
                <input type="hidden" name="appointment_id" value="<?php echo $appointmentId; ?>">
                <input type="hidden" name="patient_id" value="<?php echo $appointment['patient_id']; ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Associated Medical Record -->
                    <div>
                        <label for="record_id" class="block text-sm font-medium text-gray-700 mb-1">Associated Medical
                            Record (Optional)</label>
                        <select name="record_id" id="record_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">None</option>
                            <?php foreach ($medicalRecords as $record): ?>
                                <option value="<?php echo $record['record_id']; ?>">
                                    <?php echo htmlspecialchars($record['formatted_date'] . ' - ' . substr($record['diagnosis'], 0, 30) . (strlen($record['diagnosis']) > 30 ? '...' : '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Invoice Number -->
                    <div>
                        <label for="invoice_number" class="block text-sm font-medium text-gray-700 mb-1">Invoice
                            Number</label>
                        <input type="text" name="invoice_number" id="invoice_number"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Description -->
                    <div class="md:col-span-2">
                        <label for="description"
                            class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" id="description" rows="2" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>

                    <!-- Amount -->
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">$</span>
                            </div>
                            <input type="number" name="amount" id="amount" step="0.01" min="0" required
                                class="w-full pl-7 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <!-- Payment Status -->
                    <div>
                        <label for="payment_status" class="block text-sm font-medium text-gray-700 mb-1">Payment
                            Status</label>
                        <select name="payment_status" id="payment_status" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="Pending">Pending</option>
                            <option value="Paid">Paid</option>
                            <option value="Partial">Partial</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>

                    <!-- Payment Method -->
                    <div>
                        <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-1">Payment
                            Method</label>
                        <select name="payment_method" id="payment_method"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Payment Method</option>
                            <option value="Cash">Cash</option>
                            <option value="Credit Card">Credit Card</option>
                            <option value="Debit Card">Debit Card</option>
                            <option value="Check">Check</option>
                            <option value="Insurance">Insurance</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <!-- Notes -->
                    <div class="md:col-span-2">
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea name="notes" id="notes" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-between pt-4 border-t">
                    <a href="/it38b-Enterprise/views/nurse/appointment_view.php?id=<?php echo $appointmentId; ?>"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                        <i class="fas fa-file-invoice-dollar mr-2"></i> Add Billing Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>