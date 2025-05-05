<?php

class User {
    private $role;
    private $name;

    public function __construct($role = 'guest') {
        $this->role = $role;
        $this->name = "Nurse";
    }

    // Get the user role
    public function getRole() {
        return $this->role;
    }

    // Get the user name
    public function getName() {
        return $this->name;
    }

    // Set user role (in case you need to update the role)
    public function setRole($role) {
        $this->role = $role;
    }

}

?>
