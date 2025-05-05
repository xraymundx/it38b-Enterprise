<?php
session_start();
require_once('../../models/User.php');
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
            <link rel="stylesheet" href="../../style/style.css">
            <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
        </head>

        <body>

            <div id="mySidenav" class="sidenav">
                <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
                <a href="?page=dashboard">Dashboard</a>
                <a href="?page=patients">Patients</a>
                <a href="?page=appointments">Appointments</a>
                <a href="?page=medical_records">Medical Records</a>
                <a href="?page=billing_records">Billing Records</a>
            </div>

            <div class="main">

                <div class="stickybtn"> 
                    <span style="font-size:30px;cursor:pointer" onclick="openNav()">&#9776;</span>
                </div>

                <div class="maincontainer">
                    <h1>Welcome to the Nurse Dashboard</h1>
                    <?php
                    // Check the 'page' query parameter and include the corresponding file
                    if (isset($_GET['page'])) {
                        $page = $_GET['page'];
                        switch ($page) {
                            case 'dashboard':
                                include('dashboard.php');
                                break;
                            case 'patients':
                                include('patients.php');
                                break;
                            case 'appointments':
                                include('appointments.php');
                                break;
                            case 'medical_records':
                                include('medical_records.php');
                                break;
                            case 'billing_records':
                                include('billing_records.php');
                                break;
                            default:
                                echo "Page not found.";
                        }
                    } else {
                        // Default to dashboard if no page is specified
                        include('dashboard.php');
                    }
                    ?>
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
