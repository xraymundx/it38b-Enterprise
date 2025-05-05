<?php
session_start();
require_once('models/User.php');
$user = new User('nurse');
$_SESSION['user'] = $user;

if (isset($_SESSION['user'])) {
    $user = $_SESSION['user'];
    if ($user instanceof User) {
        ?>
        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Nurse Dashboard</title>
            <link rel="stylesheet" href="style/style.css">
            <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
        </head>

        <body>

            <div id="mySidenav" class="sidenav">
                <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
                <a href="index.php">Dashboard</a>
                <a href="patients.php">Patients</a>
                <a href="appointments.php">Appointments</a>
                <a href="medical_records.php">Medical Records</a>
                <a href="billing_records.php">Billing Records</a>
            </div>

            <div class="main">

                <div class="stickybtn"> 
                    <span style="font-size:30px;cursor:pointer" onclick="openNav()">&#9776;</span>
                </div>

                <div class="maincontainer">
                    <h1>Welcome to the Nurse Dashboard</h1>
                    <ul>
                        <li><a href="patients.php">Patients</a></li>
                        <li><a href="appointments.php">Appointments</a></li>
                        <li><a href="medical_records.php">Medical Records</a></li>
                        <li><a href="billing_records.php">Billing Records</a></li>
                    </ul>
                </div>

            </div>

        </body>

        <script>
            function openNav() {
                document.getElementById("mySidenav").style.width = "250px";
                document.querySelector(".main").style.marginLeft = "250px";
            }

            function closeNav() {
                document.getElementById("mySidenav").style.width = "0";
                document.querySelector(".main").style.marginLeft = "0";
            }
        </script>

        </html>
        <?php
    }
} else {
    echo "No user logged in.";
}
?>
