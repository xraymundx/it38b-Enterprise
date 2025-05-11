<?php
// functions.php
function log_event($conn, $user_id, $event_type, $description)
{
    $stmt = $conn->prepare("INSERT INTO logs (user_id, event_type, description) VALUES (:user_id, :event_type, :description)");
    $stmt->execute([
        'user_id' => $user_id,
        'event_type' => $event_type,
        'description' => $description
    ]);
}