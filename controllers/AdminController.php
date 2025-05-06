<?php

class AdminController
{
    public function dashboard()
    {
        require_once('views/admin/dashboard.php');
    }

    public function listUsers()
    {
        require_once('views/admin/users.php');
    }

    public function addUser()
    {
        require_once('views/admin/add_user.php');
    }

    public function editUser($id)
    {
        require_once('views/admin/edit_user.php');
    }

    public function deleteUser($id)
    {
        // Logic to delete a user
    }
}
?>