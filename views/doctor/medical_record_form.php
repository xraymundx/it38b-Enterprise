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
        'doctor_id' => isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0,
        'diagnosis' => isset($_POST['diagnosis']) ? $_POST['diagnosis'] : '',
        'treatment' => isset($_POST['treatment']) ? $_POST['treatment'] : '',
        'prescribed_medications' => isset($_POST['prescribed_medications']) ? $_POST['prescribed_medications'] : '',
        'test_results' => isset($_POST['test_results']) ? $_POST['test_results'] : '',
        'notes' => isset($_POST['notes']) ? $_POST['notes'] : ''
    ];

    // Call add_medical_record function
    $result = add_medical_record($data);

    if ($result['success']) {
        // Redirect to the appointment view page with success message
        header("Location: /it38b-Enterprise/views/nurse/appointment_view.php?id={$data['appointment_id']}&success=Medical+record+added+successfully");
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
        header("Location: /it38b-Enterprise/routes/dashboard_router.php?page=medical_records&error=Invalid+appointment+ID");
        exit();
    }
} else {
    header("Location: /it38b-Enterprise/routes/dashboard_router.php?page=medical_records&error=Missing+appointment+ID");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Medical Record</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Add Medical Record</h1>
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
                        <p class="text-sm text-gray-600">Reason for Visit:</p>
                        <p class="font-medium"><?php echo htmlspecialchars($appointment['reason_for_visit']); ?></p>
                    </div>
                </div>
            </div>

            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="space-y-6">
                <input type="hidden" name="appointment_id" value="<?php echo $appointmentId; ?>">
                <input type="hidden" name="patient_id" value="<?php echo $appointment['patient_id']; ?>">
                <input type="hidden" name="doctor_id" value="<?php echo $appointment['doctor_id']; ?>">

                <div class="grid grid-cols-1 gap-6">
                    <!-- Diagnosis -->
                    <div>
                        <label for="diagnosis" class="block text-sm font-medium text-gray-700 mb-1">Diagnosis</label>
                        <textarea name="diagnosis" id="diagnosis" rows="3" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>

                    <!-- Treatment -->
                    <div>
                        <label for="treatment" class="block text-sm font-medium text-gray-700 mb-1">Treatment</label>
                        <textarea name="treatment" id="treatment" rows="3" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>

                    <!-- Prescribed Medications -->
                    <div>
                        <label for="prescribed_medications"
                            class="block text-sm font-medium text-gray-700 mb-1">Prescribed Medications</label>
                        <textarea name="prescribed_medications" id="prescribed_medications" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>

                    <!-- Test Results -->
                    <div>
                        <label for="test_results" class="block text-sm font-medium text-gray-700 mb-1">Test
                            Results</label>
                        <textarea name="test_results" id="test_results" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Additional Notes</label>
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
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-file-medical mr-2"></i> Add Medical Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>