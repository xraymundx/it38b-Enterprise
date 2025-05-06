<?php
// config/roles.php

// Roles configuration
$roles = [
    'administrator' => ['view_patient', 'add_patient', 'edit_patient', 'delete_patient'],
    'doctor' => ['view_patient', 'add_patient', 'edit_patient'],
    'nurse' => ['view_patient', 'add_patient'],
    'guest' => ['view_patient'],
];

// Function to check if a role has a specific permission
function hasPermission($role, $permission)
{
    global $roles;
    return isset($roles[$role]) && in_array($permission, $roles[$role]);
}

// Return roles array to be used elsewhere
return $roles;
?>