<?php
class User {
    private $role;

    public function __construct($role) {
        $this->role = $role;
    }

    public function getRole() {
        return $this->role;
    }
}
?>
