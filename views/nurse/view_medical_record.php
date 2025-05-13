<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../functions/medical_records.php';

// Ensure 'id' is present and valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /it38b-Enterprise/views/nurse/medical_records.php?error=Invalid+record+ID");
    exit();
}

$recordId = intval($_GET['id']);
$appointmentId = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : null;

// Fetch medical record with all related information
$record = get_medical_record_by_id($recordId);

// Redirect if record not found
if (!$record) {
    header("Location: /it38b-Enterprise/views/nurse/medical_records.php?error=Record+not+found");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Medical Record Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Medical Record Details</h1>
                <div class="flex space-x-3">
                    <?php if ($appointmentId): ?>
                        <a href="/it38b-Enterprise/views/nurse/appointment_view.php?id=<?php echo $appointmentId; ?>"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Appointment
                        </a>
                    <?php else: ?>
                        <a href="/it38b-Enterprise/routes/dashboard_router.php?page=medical_records"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i> Back to List
                        </a>
                    <?php endif; ?>
                    <a href="/it38b-Enterprise/views/nurse/edit_medical_record.php?id=<?php echo $recordId; ?>"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                        <i class="fas fa-edit mr-2"></i> Edit
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                <!-- Basic Information -->
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Medical Record Information</h2>

                    <div class="mb-4">
                        <p class="text-sm text-gray-500 mb-1">Record ID</p>
                        <p class="font-medium"><?php echo htmlspecialchars($record['record_id']); ?></p>
                    </div>

                    <div class="mb-4">
                        <p class="text-sm text-gray-500 mb-1">Date</p>
                        <p class="font-medium"><?php echo htmlspecialchars($record['formatted_date']); ?></p>
                    </div>

                    <div class="mb-4">
                        <p class="text-sm text-gray-500 mb-1">Time</p>
                        <p class="font-medium"><?php echo htmlspecialchars($record['formatted_time']); ?></p>
                    </div>

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
                </div>

                <!-- People Information -->
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">People Information</h2>

                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-700 mb-3">Patient</h3>
                        <div class="mb-2">
                            <p class="text-sm text-gray-500 mb-1">Name</p>
                            <p class="font-medium"><?php echo htmlspecialchars($record['patient_name']); ?></p>
                        </div>
                        <a href="/it38b-Enterprise/functions/view_patient.php?id=<?php echo $record['patient_id']; ?>"
                            class="text-blue-600 hover:underline inline-flex items-center mt-2">
                            <i class="fas fa-user-circle mr-1"></i> View Patient Details
                        </a>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-700 mb-3">Doctor</h3>
                        <div class="mb-2">
                            <p class="text-sm text-gray-500 mb-1">Name</p>
                            <p class="font-medium">Dr. <?php echo htmlspecialchars($record['doctor_name']); ?></p>
                        </div>
                        <div class="mb-2">
                            <p class="text-sm text-gray-500 mb-1">Specialization</p>
                            <p class="font-medium"><?php echo htmlspecialchars($record['specialization_name']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Medical Details -->
            <div class="bg-gray-50 p-6 rounded-lg mb-8">
                <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Medical Details</h2>

                <div class="mb-4">
                    <p class="text-sm text-gray-500 mb-1">Diagnosis</p>
                    <div class="bg-white p-3 rounded border border-gray-200">
                        <?php echo nl2br(htmlspecialchars($record['diagnosis'])); ?>
                    </div>
                </div>

                <div class="mb-4">
                    <p class="text-sm text-gray-500 mb-1">Treatment</p>
                    <div class="bg-white p-3 rounded border border-gray-200">
                        <?php echo nl2br(htmlspecialchars($record['treatment'])); ?>
                    </div>
                </div>

                <div class="mb-4">
                    <p class="text-sm text-gray-500 mb-1">Prescribed Medications</p>
                    <div class="bg-white p-3 rounded border border-gray-200">
                        <?php echo !empty($record['prescribed_medications']) ? nl2br(htmlspecialchars($record['prescribed_medications'])) : 'None'; ?>
                    </div>
                </div>

                <div class="mb-4">
                    <p class="text-sm text-gray-500 mb-1">Test Results</p>
                    <div class="bg-white p-3 rounded border border-gray-200">
                        <?php echo !empty($record['test_results']) ? nl2br(htmlspecialchars($record['test_results'])) : 'None'; ?>
                    </div>
                </div>

                <div class="mb-4">
                    <p class="text-sm text-gray-500 mb-1">Notes</p>
                    <div class="bg-white p-3 rounded border border-gray-200">
                        <?php echo !empty($record['notes']) ? nl2br(htmlspecialchars($record['notes'])) : 'None'; ?>
                    </div>
                </div>
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
                        <a href="/it38b-Enterprise/routes/dashboard_router.php?page=medical_records"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                            <i class="fas fa-arrow-left mr-1"></i> Back to List
                        </a>
                    <?php endif; ?>
                </div>

                <div class="space-x-3">
                    <a href="/it38b-Enterprise/views/nurse/edit_medical_record.php?id=<?php echo $recordId; ?>"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-edit mr-2"></i> Edit Record
                    </a>
                    <a href="/it38b-Enterprise/functions/delete_medical_record.php?id=<?php echo $recordId; ?>"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                        onclick="return confirm('WARNING: This will permanently delete this medical record. This action cannot be undone. Are you sure?')">
                        <i class="fas fa-trash-alt mr-2"></i> Delete
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>