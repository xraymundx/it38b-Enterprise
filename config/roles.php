<?php

$roles = [
    'administrator' => ['view_patient', 'add_patient', 'edit_patient', 'delete_patient'],
    'doctor'        => ['view_patient', 'add_patient', 'edit_patient'],
    'nurse'         => ['view_patient', 'add_patient'],
    'guest'         => ['view_patient'],
];

function hasPermission($role, $permission) {
    global $roles;
    return isset($roles[$role]) && in_array($permission, $roles[$role]);
}

// Simulate a logged-in user's role for now
$currentUserRole = 'doctor'; // You can change this for testing