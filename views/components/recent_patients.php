<?php
// Include database connection
require_once 'config/config.php'; // Adjust the path as needed

// Fetch the 3 most recent patients, their first and last names, and descriptions
$query = "SELECT p.patient_id, p.first_name, p.last_name, p.created_at, d.description
          FROM patients p
          LEFT JOIN patient_descriptions d ON p.patient_id = d.patient_id
          ORDER BY p.created_at DESC LIMIT 3";
$result = $conn->query($query);

// Check if the query was successful AND if there are results
$recentPatients = [];
if ($result) { // First, check if the query executed successfully
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $recentPatients[] = $row;
        }
    }
    $result->free(); // It's good practice to free the result set
} else {
    // Handle the error if the query failed
    echo "Error executing query: " . $conn->error;
    // Optionally, you might want to log the error or display a user-friendly message
}
?>
<style>
    .recent-patients-container {
        border: 1px solid #333;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        padding: 20px;
        margin-bottom: 20px;
    }

    .recent-patients-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .recent-patients-header h2 {
        margin: 0;
        font-size: 1.2em;
        color: #333;
    }

    .recent-patients-table {
        width: 100%;
        border-collapse: collapse;
    }

    .recent-patients-table thead th {
        padding: 10px;
        text-align: left;
        font-size: 0.9em;
        color: #777;
        border-bottom: 1px solid #ccc;
    }

    .recent-patients-table tbody td {
        padding: 15px 10px;
        text-align: left;
        font-size: 0.95em;
        color: #333;
        border-bottom: 1px solid #ccc;
    }

    .recent-patients-table tbody tr:last-child td {
        border-bottom: none;
    }

    .patient-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .patient-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #ddd;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2em;
        color: #666;
    }

    .patient-name {
        font-weight: 500;
    }

    .see-more-link {
        text-align: center;
        margin-top: 10px;
    }

    .see-more-link a {
        display: inline-block;
        padding: 8px 15px;
        color: #fff;
        background-color: #007bff;
        border: none;
        border-radius: 20px;
        text-decoration: none;
        font-size: 0.9em;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .see-more-link a:hover {
        background-color: #0056b3;
        text-decoration: none;
    }
</style>

<div class="recent-patients-container">
    <div class="recent-patients-header">
        <h2>Recent Patients</h2>
    </div>
    <table class="recent-patients-table">
        <thead>
            <tr>
                <th>Patient</th>
                <th>Date Added</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($recentPatients)): ?>
                <?php foreach ($recentPatients as $patient): ?>
                    <tr>
                        <td>
                            <div class="patient-info">
                                <div class="patient-avatar">
                                    <?php
                                    $initials = '';
                                    if (!empty($patient['first_name'])) {
                                        $initials .= strtoupper($patient['first_name'][0]);
                                    }
                                    if (!empty($patient['last_name'])) {
                                        $initials .= strtoupper($patient['last_name'][0]);
                                    }
                                    echo htmlspecialchars(substr($initials, 0, 2)); // Display up to 2 initials
                                    ?>
                                </div>
                                <div class="patient-name">
                                    <?php
                                    echo htmlspecialchars($patient['first_name'] ?? '');
                                    if (!empty($patient['first_name']) && !empty($patient['last_name'])) {
                                        echo ' ';
                                    }
                                    echo htmlspecialchars($patient['last_name'] ?? '');
                                    ?>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars(date('m/d/Y', strtotime($patient['created_at']))); ?></td>
                        <td><?php echo htmlspecialchars($patient['description'] ?? 'New Patient'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" style="text-align: center;">No recent patients found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="see-more-link">
        <a href="?page=patients">See More...</a>
    </div>
</div>