<?php

class DoctorController
{
    public function dashboard()
    {
        require_once('views/doctor/dashboard.php');
    }

    public function listAppointments()
    {
        require_once('views/doctor/appointments.php');
    }

    public function viewAppointment($id)
    {
        require_once('views/doctor/view_appointment.php');
    }

    public function addAppointment()
    {
        require_once('views/doctor/add_appointment.php');
    }

    public function editAppointment($id)
    {
        require_once('views/doctor/edit_appointment.php');
    }

    public function deleteAppointment($id)
    {
        // Logic to delete an appointment
    }
}
?>