<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/billing_records.php';

// Ensure bill ID is present and valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /it38b-Enterprise/views/nurse/billing_records.php?error=Invalid+bill+ID");
    exit();
}

$billId = intval($_GET['id']);

// Attempt to delete the record
$result = delete_billing_record($billId);

if ($result['success']) {
    header("Location: /it38b-Enterprise/views/nurse/billing_records.php?success=Record+deleted+successfully");
} else {
    header("Location: /it38b-Enterprise/views/nurse/view_billing_record.php?id={$billId}&error=" . urlencode($result['error']));
}
exit();