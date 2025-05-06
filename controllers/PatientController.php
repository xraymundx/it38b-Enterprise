<?php

class PatientController
{
    public function dashboard()
    {
        require_once('views/patient/dashboard.php');
    }

    public function viewProfile()
    {
        require_once('views/patient/profile.php');
    }

    public function listAppointments()
    {
        require_once('views/patient/appointments.php');
    }
}
?>