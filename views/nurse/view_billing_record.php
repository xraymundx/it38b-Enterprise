<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../functions/billing_records.php';

// Ensure 'id' is present and valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /it38b-Enterprise/views/nurse/billing_records.php?error=Invalid+record+ID");
    exit();
}

$billId = intval($_GET['id']);
$appointmentId = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : null;

// Fetch billing record with all related information
$record = get_billing_record_by_id($billId);

// Redirect if record not found
if (!$record) {
    header("Location: /it38b-Enterprise/views/nurse/billing_records.php?error=Record+not+found");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Billing Record Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Billing Record Details</h1>
                <div class="flex space-x-3">
                    <?php if ($appointmentId): ?>
                        <a href="/it38b-Enterprise/views/nurse/appointment_view.php?id=<?php echo $appointmentId; ?>"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Appointment
                        </a>
                    <?php else: ?>
                        <a href="/it38b-Enterprise/routes/dashboard_router.php?page=billing_records"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i> Back to List
                        </a>
                    <?php endif; ?>
                    <a href="/it38b-Enterprise/views/nurse/edit_billing_record.php?id=<?php echo $billId; ?>"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                        <i class="fas fa-edit mr-2"></i> Edit
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                <!-- Basic Billing Information -->
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Billing Information</h2>

                    <div class="mb-4">
                        <p class="text-sm text-gray-500 mb-1">Bill ID</p>
                        <p class="font-medium"><?php echo htmlspecialchars($record['bill_id']); ?></p>
                    </div>

                    <?php if (!empty($record['invoice_number'])): ?>
                        <div class="mb-4">
                            <p class="text-sm text-gray-500 mb-1">Invoice Number</p>
                            <p class="font-medium"><?php echo htmlspecialchars($record['invoice_number']); ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="mb-4">
                        <p class="text-sm text-gray-500 mb-1">Date</p>
                        <p class="font-medium"><?php echo htmlspecialchars($record['formatted_date']); ?></p>
                    </div>

                    <div class="mb-4">
                        <p class="text-sm text-gray-500 mb-1">Amount</p>
                        <p class="font-medium">$<?php echo htmlspecialchars(number_format($record['amount'], 2)); ?></p>
                    </div>

                    <div class="mb-4">
                        <p class="text-sm text-gray-500 mb-1">Payment Status</p>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?php
                            switch ($record['payment_status']) {
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
                            <?php echo htmlspecialchars($record['payment_status']); ?>
                        </span>
                    </div>

                    <?php if (!empty($record['payment_method'])): ?>
                        <div class="mb-4">
                            <p class="text-sm text-gray-500 mb-1">Payment Method</p>
                            <p class="font-medium"><?php echo htmlspecialchars($record['payment_method']); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($record['payment_date'])): ?>
                        <div class="mb-4">
                            <p class="text-sm text-gray-500 mb-1">Payment Date</p>
                            <p class="font-medium">
                                <?php echo htmlspecialchars(date('F j, Y', strtotime($record['payment_date']))); ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <?php if ($record['appointment_id']): ?>
                        <div class="mb-4">
                            <p class="text-sm text-gray-500 mb-1">Appointment</p>
                            <p class="font-medium">
                                <a href="/it38b-Enterprise/views/nurse/appointment_view.php?id=<?php echo $record['appointment_id']; ?>"
                                    class="text-blue-600 hover:underline">
                                    #<?php echo htmlspecialchars($record['appointment_id']); ?>
                                </a>
                            </p>
                        </div>
                    <?php endif; ?>

                    <?php if ($record['record_id']): ?>
                        <div class="mb-4">
                            <p class="text-sm text-gray-500 mb-1">Medical Record</p>
                            <p class="font-medium">
                                <a href="/it38b-Enterprise/views/nurse/view_medical_record.php?id=<?php echo $record['record_id']; ?>"
                                    class="text-blue-600 hover:underline">
                                    #<?php echo htmlspecialchars($record['record_id']); ?>
                                </a>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Patient Information -->
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Patient Information</h2>

                    <div class="mb-4">
                        <p class="text-sm text-gray-500 mb-1">Name</p>
                        <p class="font-medium"><?php echo htmlspecialchars($record['patient_name']); ?></p>
                    </div>

                    <div class="mb-4">
                        <p class="text-sm text-gray-500 mb-1">Email</p>
                        <p class="font-medium"><?php echo htmlspecialchars($record['patient_email']); ?></p>
                    </div>

                    <a href="/it38b-Enterprise/functions/view_patient.php?id=<?php echo $record['patient_id']; ?>"
                        class="text-blue-600 hover:underline inline-flex items-center mt-2">
                        <i class="fas fa-user-circle mr-1"></i> View Patient Details
                    </a>
                </div>
            </div>

            <!-- Description and Notes -->
            <div class="bg-gray-50 p-6 rounded-lg mb-8">
                <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Description & Notes</h2>

                <div class="mb-4">
                    <p class="text-sm text-gray-500 mb-1">Description</p>
                    <div class="bg-white p-3 rounded border border-gray-200">
                        <?php echo nl2br(htmlspecialchars($record['description'])); ?>
                    </div>
                </div>

                <?php if (!empty($record['notes'])): ?>
                    <div class="mb-4">
                        <p class="text-sm text-gray-500 mb-1">Notes</p>
                        <div class="bg-white p-3 rounded border border-gray-200">
                            <?php echo nl2br(htmlspecialchars($record['notes'])); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-between border-t pt-6">
                <div>
                    <?php if ($appointmentId): ?>
                        <a href="/it38b-Enterprise/views/nurse/appointment_view.php?id=<?php echo $appointmentId; ?>"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Appointment
                        </a>
                    <?php else: ?>
                        <a href="/it38b-Enterprise/routes/dashboard_router.php?page=billing_records"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                            <i class="fas fa-arrow-left mr-1"></i> Back to List
                        </a>
                    <?php endif; ?>
                </div>

                <div class="space-x-3">
                    <a href="/it38b-Enterprise/views/nurse/edit_billing_record.php?id=<?php echo $billId; ?>"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-edit mr-2"></i> Edit Record
                    </a>
                    <a href="/it38b-Enterprise/functions/delete_billing_record.php?id=<?php echo $billId; ?>"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                        onclick="return confirm('WARNING: This will permanently delete this billing record. This action cannot be undone. Are you sure?')">
                        <i class="fas fa-trash-alt mr-2"></i> Delete
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>