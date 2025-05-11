<?php
require 'config/config.php';
require 'config/functions.php';

$doctor_id = isset($_GET['doctor_id']) ? (int) $_GET['doctor_id'] : null;
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$availability_slots = [];
$default_start_hour = 7;
$default_end_hour = 18;

function loadDoctorAvailability($conn, $doctor_id, $date)
{
    if (!$doctor_id)
        return [];
    $stmt = $conn->prepare("SELECT start_time FROM doctor_availability WHERE doctor_id = ? AND availability_date = ? ORDER BY start_time ASC");
    $stmt->bind_param("is", $doctor_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $availability = [];
    while ($row = $result->fetch_assoc()) {
        $availability[] = date('H:00', strtotime($row['start_time']));
    }
    $stmt->close();
    return $availability;
}

if ($doctor_id) {
    $saved_availability = loadDoctorAvailability($conn, $doctor_id, $date);
    if (!empty($saved_availability)) {
        $availability_slots = $saved_availability;
    } else {
        for ($i = $default_start_hour; $i <= $default_end_hour; $i++) {
            $availability_slots[] = sprintf('%02d:00', $i);
        }
    }
}
?>
<h2>Set Availability for Doctor ID <span id="selected-doctor-id-display"><?= $doctor_id ?></span> on <span
        id="selected-date-display"><?= date('Y-m-d', strtotime($date)) ?></span></h2>
<form id="availability-form" method="post">
    <input type="hidden" name="doctor_id_to_save" value="<?= $doctor_id ?>">
    <input type="hidden" name="selected_date" id="selected-date-form" value="<?= $date ?>">
    <div class="add-times">
        <button type="button" id="add-all-times" onclick="addAllTimes()">Add All +</button>
        <?php for ($i = $default_start_hour; $i <= $default_end_hour; $i++): ?>
            <?php $hour_format = sprintf('%02d:00', $i); ?>
            <button type="button" class="add-time-btn" data-time="<?= $hour_format ?>">
                <?= date('g:00 A', strtotime($hour_format)) ?> +
            </button>
        <?php endfor; ?>
    </div>
    <div class="selected-times" id="selected-times-display">
        <?php foreach ($availability_slots as $slot): ?>
            <span class="selected-time-slot" data-time="<?= $slot ?>">
                <?= date('g:00 A', strtotime($slot)) ?>
                <span class="remove-time" data-time="<?= $slot ?>">X</span>
                <input type="hidden" name="availability[]" value="<?= $slot ?>">
            </span>
        <?php endforeach; ?>
    </div>
    <div class="availability-actions">
        <button type="button" onclick="saveAvailability()">Save Settings</button>
        <button type="button" id="clear-all-times" onclick="clearAllTimes()">Clear All X</button>
    </div>
</form>
<script>
    // Re-define JavaScript functions needed within the loaded content
    function formatTime(time) { /* ... your formatTime function ... */ }
    function addTime(time) { /* ... your addTime function ... */ }
    function removeTime(time) { /* ... your removeTime function ... */ }
    function addAllTimes() { /* ... your addAllTimes function ... */ }
    function clearAllTimes() { /* ... your clearAllTimes function ... */ }
    // Event listeners will be attached in the main script after loading
</script>