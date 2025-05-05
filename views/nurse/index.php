<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Dashboard</title>
    <link rel="stylesheet" href="../../style/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <style>
        body {
            font-family: "Lato", sans-serif;
            background-color: #f3f4f6;
        }

        .sidenav {
            height: 100%;
            width: 0; /* Initially hidden */
            position: fixed;
            z-index: 1;
            top: 0;
            left: 0;
            background-color: #1e3a8a; /* Indigo 900 */
            color: white;
            overflow-x: hidden;
            transition: 0.3s;
            padding-top: 20px;
            display: flex;
            flex-direction: column;
        }

        .sidenav.open {
            width: 280px;
        }

        .sidenav-header {
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .sidenav-header h1 {
            font-size: 1.5rem;
            margin: 0;
        }

        .sidenav-header .closebtn {
            color: white;
            font-size: 2rem;
            cursor: pointer;
            text-decoration: none;
        }

        .sidenav-item {
            padding: 10px 20px;
            text-decoration: none;
            font-size: 1rem;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background-color 0.2s ease;
        }

        .sidenav-item:hover {
            background-color: #374151; /* Gray 700 */
        }

        .sidenav-item.active {
            background-color: #0ea5e9; /* Sky 500 */
            font-weight: 500;
        }

        .sidenav-footer {
            margin-top: auto;
            padding: 20px;
            background-color: #312e81; /* Indigo 800 */
            border-top: 1px solid #4338ca; /* Indigo 700 */
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-details {
            font-size: 0.9rem;
        }

        .user-details strong {
            display: block;
        }

        .sidenav-footer a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background-color 0.2s ease;
        }

        .sidenav-footer a:hover {
            background-color: #4338ca; /* Indigo 700 */
        }

        .main {
            margin-left: 0; /* Initially no margin */
            transition: margin-left 0.3s;
            padding: 20px;
        }

        .main.open {
            margin-left: 280px;
        }

        .stickybtn {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 2;
            display: block; /* Initially visible */
        }

        .stickybtn.hidden {
            display: none;
        }

        .stickybtn span {
            font-size: 2rem;
            cursor: pointer;
            color: #4b5563; /* Gray 600 */
        }

        .maincontainer {
            margin-top: 20px;
        }

        @media screen and (max-width: 768px) {
            .sidenav.open {
                width: 100%;
            }

            .main.open {
                margin-left: 100%;
            }
        }
    </style>
</head>

<body>

    <div id="mySidenav" class="sidenav">
        <div class="sidenav-header">
            <h1>MF CLINIC</h1>
            <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
        </div>
        <a href="?page=dashboard" class="sidenav-item active">
            <span class="material-symbols-outlined">home</span>
            Dashboard
        </a>
        <a href="?page=patients" class="sidenav-item">
            <span class="material-symbols-outlined">group</span>
            Patients
        </a>
        <a href="?page=appointments" class="sidenav-item">
            <span class="material-symbols-outlined">calendar_today</span>
            Appointments
        </a>
        <a href="?page=medical_records" class="sidenav-item">
            <span class="material-symbols-outlined">note</span>
            Medical Records
        </a>
        <a href="?page=billing_records" class="sidenav-item">
            <span class="material-symbols-outlined">receipt</span>
            Billing Records
        </a>
        <div class="sidenav-footer">
            <div class="user-info">
                <img src="https://via.placeholder.com/40" alt="User Avatar">
                <div class="user-details">
                    <strong>John</strong>
                    <small>Nurse</small>
                </div>
            </div>
            <a href="#" class="flex items-center gap-2">
                <span class="material-symbols-outlined">settings</span>
                Settings
            </a>
            <a href="#" class="flex items-center gap-2">
                <span class="material-symbols-outlined">logout</span>
                Log Out
            </a>
        </div>
    </div>

    <div class="main">

        <div id="stickyButton" class="stickybtn">
            <span style="font-size:2rem;cursor:pointer;color:#4b5563" onclick="openNav()">&#9776;</span>
        </div>

        <div class="maincontainer">
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
    const sidenav = document.getElementById("mySidenav");
    const main = document.querySelector(".main");
    const stickyButton = document.getElementById("stickyButton");

    function openNav() {
        sidenav.classList.add("open");
        main.classList.add("open");
        stickyButton.classList.add("hidden");
    }

    function closeNav() {
        sidenav.classList.remove("open");
        main.classList.remove("open");
        stickyButton.classList.remove("hidden");
    }

    // Add active class to the current page link
    document.addEventListener('DOMContentLoaded', function() {
        const links = document.querySelectorAll('.sidenav-item');
        const currentPage = window.location.search;

        links.forEach(link => {
            const linkPage = link.getAttribute('href').substring(1); // Remove the '?'
            if (currentPage === linkPage) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    });
</script>

</html>