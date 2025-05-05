<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Dashboard</title>
    <link rel="stylesheet" href="../../style/style.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f3f4f6;
            margin: 0;
        }

        .toolbar {
            background-color: white;
            color: black;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 2;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            transition: left 0.3s, width 0.3s;
            /* Add transition for smooth adjustment */
        }

        .toolbar.open {
            left: 280px;
            /* Shift toolbar to the right when sidenav is open */
            width: calc(100% - 280px);
            /* Reduce width to avoid covering sidenav */
        }

        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .toolbar-left button {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #4b5563;
            /* Gray 600 */
            padding: 0;
            margin: 0;
            outline: none;
        }

        .toolbar-left h2 {
            font-size: 1.5rem;
            margin: 0;
            font-weight: 500;
            text-transform: capitalize;
            /* Add to capitalize the title */
        }

        .toolbar-search {
            background-color: #f3f4f6;
            /* Light gray */
            border-radius: 6px;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .toolbar-search .material-symbols-outlined {
            font-size: 1.2rem;
            color: #6b7280;
            /* Gray 500 */
        }

        .toolbar-search input[type="text"] {
            border: none;
            background: none;
            outline: none;
            font-size: 1rem;
            color: #374151;
            /* Gray 700 */
        }

        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .toolbar-actions button {
            background: none;
            border: 1px solid #6b7280;
            /* Gray 500 */
            color: #6b7280;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            outline: none;
        }

        .toolbar-actions .notification {
            position: relative;
        }

        .toolbar-actions .notification .material-symbols-outlined {
            font-size: 1.5rem;
            color: #4b5563;
            /* Gray 600 */
        }

        .toolbar-actions .notification::after {
            content: '';
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: red;
            color: white;
            font-size: 0.7rem;
            border-radius: 50%;
            width: 14px;
            height: 14px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .sidenav {
            height: 100%;
            width: 0;
            /* Initially hidden */
            position: fixed;
            z-index: 1;
            top: 0;
            left: 0;
            background: linear-gradient(to bottom, #000144 46%, #000144 67%, #0002AA 100%);
            color: white;
            /* Default text color for sidenav */
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
            font-size: 2rem;
            /* Increased by 5px from 1.5rem */
            margin: 0;
            color: white;
            /* Default white for the whole text if no span */
        }

        .sidenav-header h1 span.mf {
            color: #00B4D8;
        }

        .sidenav-header h1 span.clinic {
            color: white;
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
            /* Explicitly set text color to white */
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background-color 0.2s ease;
        }

        .sidenav-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            /* Slightly lighter overlay on hover */
            color: white;
            /* Ensure text remains white on hover */
        }

        .sidenav-item.active {
            background-color: #0ea5e9;
            /* Sky 500 */
            font-weight: 500;
            color: white;
            /* Ensure active text is white */
        }

        .sidenav-footer {
            margin-top: auto;
            padding: 20px;
            background-color: rgba(0, 0, 0, 0.2);
            /* Slightly darker overlay for footer */
            border-top: 1px solid rgba(255, 255, 255, 0.1);
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
            color: white;
            /* Explicitly set user details text to white */
        }

        .user-details strong {
            display: block;
        }

        .sidenav-footer a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            /* Explicitly set footer link text to white */
            text-decoration: none;
            font-size: 0.9rem;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background-color 0.2s ease;
        }

        .sidenav-footer a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            /* Slightly lighter overlay on hover */
            color: white;
            /* Ensure text remains white on hover */
        }

        .main {
            margin-left: 0;
            /* Initially no margin */
            padding: 20px;
            padding-top: 70px;
            /* Adjust top padding to avoid overlap with toolbar */
            transition: margin-left 0.3s;
        }

        .main.open {
            margin-left: 280px;
            padding-top: 70px;
            /* Maintain top padding when sidenav is open */
        }

        .maincontainer {
            margin-top: 20px;
        }

        @media screen and (max-width: 768px) {
            .toolbar.open {
                left: 100%;
                /* Move toolbar off-screen when sidenav is full width */
                width: 0;
            }

            .main.open {
                margin-left: 100%;
                padding-top: 70px;
                /* Maintain top padding */
            }
        }
    </style>
</head>

<body>

    <div class="toolbar">
        <div class="toolbar-left">
            <button onclick="openNav()">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h2 id="pageTitle">Dashboard</h2>
        </div>
        <div class="toolbar-search">
            <span class="material-symbols-outlined">search</span>
            <input type="text" placeholder="Search anything...">
            <button
                style="border: none; background: none; outline: none; cursor: pointer; color: #6b7280; font-size: 0.9rem; display: flex; align-items: center; gap: 5px;">
                <span class="material-symbols-outlined" style="font-size: 1rem;">keyboard</span> + K
            </button>
        </div>
        <div class="toolbar-actions">
            <div class="notification">
                <span class="material-symbols-outlined">notifications</span>
            </div>
        </div>
    </div>

    <div id="mySidenav" class="sidenav">
        <div class="sidenav-header">
            <h1><span class="mf">MF</span> CLINIC</h1>
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
    const toolbar = document.querySelector(".toolbar");
    const pageTitleElement = document.getElementById("pageTitle");

    function openNav() {
        sidenav.classList.add("open");
        main.classList.add("open");
        toolbar.classList.add("open");
    }

    function closeNav() {
        sidenav.classList.remove("open");
        main.classList.remove("open");
        toolbar.classList.remove("open");
    }

    // Function to update the toolbar title based on the page
    function updateToolbarTitle() {
        const urlParams = new URLSearchParams(window.location.search);
        const page = urlParams.get('page');
        let title = 'Dashboard'; // Default title

        if (page) {
            title = page.replace('_', ' '); // Replace underscores with spaces
        }

        pageTitleElement.textContent = title;
    }

    // Add active class to the current page link and update the toolbar title
    document.addEventListener('DOMContentLoaded', function () {
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

        updateToolbarTitle(); // Call the function on page load
    });

    // Update the title when the URL changes (e.g., clicking a sidenav link)
    window.addEventListener('popstate', updateToolbarTitle);
</script>