<?php

class Role {
    const NURSE = 1;
    const DOCTOR = 2;
    const ADMIN = 3;

    public static function find($role_id) {
        // Normally, this would fetch from a database, but for simplicity:
        switch ($role_id) {
            case self::NURSE:
                return 'nurse';
            case self::DOCTOR:
                return 'doctor';
            case self::ADMIN:
                return 'admin';
            default:
                return null;
        }
    }
}
?>
