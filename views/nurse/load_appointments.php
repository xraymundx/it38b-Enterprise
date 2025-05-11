<?php
require 'config/config.php';
require 'config/functions.php';

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;
$appointments = [];

$stmt_count = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE DATE(appointment_datetime) = ?");
$stmt_count->bind_param("s", $date);
$stmt_count->execute();
$total_items = $stmt_count->get_result()->fetch_row()[0];
$stmt_count->close();

$total_pages = ceil($total_items / $items_per_page);

$stmt_appointments = $conn->prepare("SELECT p.first_name AS patient_name, TIME(a.appointment_datetime) AS appointment_time, a.reason_for_visit, a.status, a.notes, a.created_at, a.updated_at
                                    FROM appointments a
                                    JOIN patients p ON a.patient_id = p.patient_id
                                    WHERE DATE(a.appointment_datetime) = ?
                                    ORDER BY a.appointment_datetime
                                    LIMIT ?, ?");
$stmt_appointments->bind_param("sii", $date, $offset, $items_per_page);
$stmt_appointments->execute();
$result_appointments = $stmt_appointments->get_result();
$appointments = $result_appointments->fetch_all(MYSQLI_ASSOC);
$stmt_appointments->close();
?>
<h2>Appointments for <?= date('Y-m-d', strtotime($date)) ?></h2>
<?php if (empty($appointments)): ?>
    <p>No appointments scheduled for this date.</p>
<?php else: ?>
    <table class="appointment-table">
        <thead>
            <tr>
                <th>Patient Name</th>
                <th>Time</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Notes</th>
                <th>Created At</th>
                <th>Updated At</th>
            </tr>
        </thead>
        <tbody id="appointments-table-body">
            <?php foreach ($appointments as $appointment): ?>
                <tr>
                    <td><?= htmlspecialchars($appointment['patient_name']) ?></td>
                    <td><?= date('g:i A', strtotime($appointment['appointment_time'])) ?></td>
                    <td><?= htmlspecialchars($appointment['reason_for_visit']) ?></td>
                    <td><?= htmlspecialchars($appointment['status']) ?></td>
                    <td><?= htmlspecialchars($appointment['notes']) ?></td>
                    <td><?= date('Y-m-d H:i:s', strtotime($appointment['created_at'])) ?></td>
                    <td><?= date('Y-m-d H:i:s', strtotime($appointment['updated_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($total_pages > 1): ?>
        <div class="pagination" id="appointments-pagination">
            <?php if ($page > 1): ?>
                <a href="#" onclick="loadAppointments('<?= $date ?>', <?= $page - 1 ?>)">Previous</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="#" onclick="loadAppointments('<?= $date ?>', <?= $i ?>)"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="#" onclick="loadAppointments('<?= $date ?>', <?= $page + 1 ?>)">Next</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>