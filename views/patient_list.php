<!DOCTYPE html>
<html>
<head>
    <title>Patient List</title>
</head>
<body>
    <h1>Patient List</h1>
    <ul>
        <?php if (empty($patients)): ?>
            <li>No patients recorded yet.</li>
        <?php else: ?>
            <?php foreach ($patients as $patient): ?>
                <li>
                    <?php echo htmlspecialchars($patient['name']); ?>
                    (<a href="index.php?action=view&id=<?php echo $patient['id']; ?>">View Details</a>)
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
    <p><a href="index.php?action=add">Add New Patient</a></p>
</body>
</html>