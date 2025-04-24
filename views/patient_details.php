<!DOCTYPE html>
<html>
<head>
    <title>Patient Details</title>
</head>
<body>
    <h1>Patient Details</h1>
    <?php if ($patient): ?>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($patient['name']); ?></p>
        <p><strong>Birth Date:</strong> <?php echo htmlspecialchars($patient['birth_date']); ?></p>
        <p><strong>Medical History:</strong><br><?php echo htmlspecialchars($patient['medical_history']); ?></p>
        <p><a href="index.php?action=list">Back to Patient List</a></p>
    <?php else: ?>
        <p>Patient not found.</p>
    <?php endif; ?>
</body>
</html>