<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../functions/medical_records.php';

// Check for form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'record_id' => $_POST['record_id'],
        'diagnosis' => $_POST['diagnosis'],
        'treatment' => $_POST['treatment'],
        'prescribed_medications' => $_POST['prescribed_medications'] ?? '',
        'test_results' => $_POST['test_results'] ?? '',
        'notes' => $_POST['notes'] ?? ''
    ];

    $result = update_medical_record($data);

    if ($result['success']) {
        header("Location: /it38b-Enterprise/views/nurse/view_medical_record.php?id=" . $result['record_id'] . "&success=Record+updated+successfully");
        exit();
    } else {
        $error = $result['error'];
    }
}

// Ensure 'id' is present and valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /it38b-Enterprise/views/nurse/medical_records.php?error=Invalid+record+ID");
    exit();
}

$recordId = intval($_GET['id']);

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
    <title>Edit Medical Record</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Edit Medical Record</h1>
                <a href="/it38b-Enterprise/views/nurse/view_medical_record.php?id=<?php echo $recordId; ?>"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Record
                </a>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                <input type="hidden" name="record_id" value="<?php echo $recordId; ?>">

                <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="font-medium text-gray-700 mb-2">
                            Patient: <span
                                class="text-gray-600"><?php echo htmlspecialchars($record['patient_name']); ?></span>
                        </p>
                        <p class="font-medium text-gray-700 mb-2">
                            Doctor: <span class="text-gray-600">Dr.
                                <?php echo htmlspecialchars($record['doctor_name']); ?></span>
                        </p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-700 mb-2">
                            Date: <span
                                class="text-gray-600"><?php echo htmlspecialchars($record['formatted_date']); ?></span>
                        </p>
                        <p class="font-medium text-gray-700 mb-2">
                            Time: <span
                                class="text-gray-600"><?php echo htmlspecialchars($record['formatted_time']); ?></span>
                        </p>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="diagnosis" class="block text-sm font-medium text-gray-700 mb-2">Diagnosis *</label>
                    <textarea name="diagnosis" id="diagnosis" rows="4"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        required><?php echo htmlspecialchars($record['diagnosis']); ?></textarea>
                </div>

                <div class="mb-6">
                    <label for="treatment" class="block text-sm font-medium text-gray-700 mb-2">Treatment *</label>
                    <textarea name="treatment" id="treatment" rows="4"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        required><?php echo htmlspecialchars($record['treatment']); ?></textarea>
                </div>

                <div class="mb-6">
                    <label for="prescribed_medications" class="block text-sm font-medium text-gray-700 mb-2">Prescribed
                        Medications</label>
                    <textarea name="prescribed_medications" id="prescribed_medications" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($record['prescribed_medications'] ?? ''); ?></textarea>
                </div>

                <div class="mb-6">
                    <label for="test_results" class="block text-sm font-medium text-gray-700 mb-2">Test Results</label>
                    <textarea name="test_results" id="test_results" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($record['test_results'] ?? ''); ?></textarea>
                </div>

                <div class="mb-6">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Additional Notes</label>
                    <textarea name="notes" id="notes" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($record['notes'] ?? ''); ?></textarea>
                </div>

                <div class="flex justify-between border-t pt-6">
                    <a href="/it38b-Enterprise/views/nurse/view_medical_record.php?id=<?php echo $recordId; ?>"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        <i class="fas fa-times mr-2"></i> Cancel
                    </a>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-save mr-2"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>