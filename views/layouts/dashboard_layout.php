<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Dashboard</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>
    <div class="sidenav">
        <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
        <a href="index.php">Home</a>
        <a href="patients.php">Patients</a>
        <a href="appointments.php">Appointments</a>
        <a href="medical_records.php">Medical Records</a>
        <a href="billing_records.php">Billing Records</a>
    </div>
    <div class="main">
        <div class="stickybtn">
            <span style="font-size:30px;cursor:pointer" onclick="openNav()">&#9776;</span>
        </div>
    </div>
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
</body>
</html>
