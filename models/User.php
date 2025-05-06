<?php
class User
{
    private $role;
    private $name;
    public function __construct($role, $name)
    {
        $this->role = $role;
        $this->name = $name;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function getName()
    {
        return $this->name;
    }

    public static function fetchUser($userId)
    {
        // This is a simulation, in real use, this should fetch from the database
        if ($userId == 1) {
            return new User("nurse", "Nurse Jane");
        }
        // Return a default user if not found
        return new User("guest", "Guest");
    }
}
?>