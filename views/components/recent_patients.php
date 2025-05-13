<?php
// Include database connection
require '../config/config.php';

// Get recent patients with their descriptions
$query = "SELECT p.patient_id, u.first_name, u.last_name, p.created_at,
          COALESCE(mr.diagnosis, 'New Patient') as description
          FROM patients p
          JOIN users u ON p.user_id = u.user_id
          LEFT JOIN (
              SELECT patient_id, diagnosis, created_at,
                     ROW_NUMBER() OVER (PARTITION BY patient_id ORDER BY created_at DESC) as rn
              FROM medicalrecords
          ) mr ON p.patient_id = mr.patient_id AND mr.rn = 1
          ORDER BY p.created_at DESC
          LIMIT 3";

$result = $conn->query($query);
?>

<div class="recent-patients">
    <h3>Recent Patients</h3>
    <?php if ($result && $result->num_rows > 0): ?>
        <div class="patient-list">
            <?php while ($patient = $result->fetch_assoc()): ?>
                <div class="patient-item">
                    <div class="patient-info">
                        <h4><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></h4>
                        <p><?php echo htmlspecialchars($patient['description']); ?></p>
                        <small>Added: <?php echo date('M d, Y', strtotime($patient['created_at'])); ?></small>
                    </div>
                    <a href="<?php echo $config['app']['url']; ?>/index.php?page=view_patient&id=<?php echo $patient['patient_id']; ?>"
                        class="view-link">View Details</a>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p>No recent patients found.</p>
    <?php endif; ?>
</div>

<style>
    .recent-patients {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .recent-patients h3 {
        color: #333;
        margin-bottom: 15px;
        font-size: 1.2em;
    }

    .patient-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .patient-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        transition: transform 0.2s;
    }

    .patient-item:hover {
        transform: translateX(5px);
    }

    .patient-info h4 {
        margin: 0;
        color: #2c3e50;
        font-size: 1.1em;
    }

    .patient-info p {
        margin: 5px 0;
        color: #666;
        font-size: 0.9em;
    }

    .patient-info small {
        color: #888;
        font-size: 0.8em;
    }

    .view-link {
        padding: 8px 15px;
        background: #6c5dd3;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        font-size: 0.9em;
        transition: background 0.3s;
    }

    .view-link:hover {
        background: #5649a8;
    }
</style>