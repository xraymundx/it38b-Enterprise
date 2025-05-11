<?php
// config/functions.php

function log_event($conn, $user_id, $event_type, $description)
{
    $stmt = mysqli_prepare($conn, "
        INSERT INTO logs (user_id, event_type, description, timestamp)
        VALUES (?, ?, ?, NOW())
    ");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iss", $user_id, $event_type, $description);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        error_log("Error preparing log event statement: " . mysqli_error($conn));
    }
}