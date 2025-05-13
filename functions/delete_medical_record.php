<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/medical_records.php';

// Ensure record ID is present and valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /it38b-Enterprise/views/nurse/medical_records.php?error=Invalid+record+ID");
    exit();
}

$recordId = intval($_GET['id']);

// Attempt to delete the record
$result = delete_medical_record($recordId);

if ($result['success']) {
    header("Location: /it38b-Enterprise/views/nurse/medical_records.php?success=Record+deleted+successfully");
} else {
    header("Location: /it38b-Enterprise/views/nurse/view_medical_record.php?id={$recordId}&error=" . urlencode($result['error']));
}
exit();