<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Nurse Dashboard</title>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            /* Apply Poppins font */
            background-color: #f3f4f6;
            /* Light gray background */
        }

        .toolbar {
            position: fixed;
            top: 0;
            left: 0;
            height: 64px;
            width: 100%;
            background-color: white;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1rem;
            transition: left 0.3s ease, width 0.3s ease;
            z-index: 1000;
            color: #111827;
            box-sizing: border-box;
            flex-wrap: wrap;
            /* Important for allowing stacking on small screens */
        }

        .toolbar.open {
            left: 280px;
            width: calc(100% - 280px);
        }

        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 0;
            /* Prevents overflow issues with long titles */
        }

        .toolbar-left h2 {
            font-size: 2rem;
            font-weight: 600;
            margin: 0;
            font-family: 'Poppins', sans-serif;
            color: #111827;
        }

        /* 🔧 Menu Button */
        .toolbar-left button {
            background: none;
            border: none;
            padding: 0;
            font-size: 2rem;
            color: #111827;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease;
            flex-shrink: 0;
            /* Prevent shrinking */
        }

        .toolbar-left button .material-symbols-outlined {
            font-size: inherit;
        }

        /* 🔍 Search */
        .toolbar-search {
            display: flex;
            align-items: center;
            background-color: #f3f4f6;
            padding: 6px 12px;
            border-radius: 8px;
            gap: 8px;
            min-width: 0;
            margin-left: 1rem;

        }

        .toolbar-search input {
            border: none;
            background: transparent;
            outline: none;
            flex: 1;
            font-size: 1rem;
            /* Adjusted font size for Poppins */
            color: #374151;
            min-width: 0;
            /* Important for preventing input overflow */
        }

        .toolbar-search .material-symbols-outlined {
            font-size: 1.2rem;
            color: #6b7280;
            flex-shrink: 0;
            /* Prevent shrinking */
        }

        /* ⚙️ Actions */
        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
            white-space: nowrap;
            flex-shrink: 0;
            /* Prevent shrinking */
        }

        .toolbar-actions button:hover {
            background-color: #e5e7eb;
        }

        .toolbar-actions .notification .material-symbols-outlined {
            font-size: 2rem;
            color: #4b5563;
        }

        .sidenav {
            position: fixed;
            left: 0;
            top: 0;
            height: 100%;
            width: 0;
            overflow-x: hidden;
            background: linear-gradient(to bottom, #000144 46%, #000144 67%, #0002AA 100%);
            /* Dark blue gradient */
            color: white;
            /* White sidenav text */
            transition: width 0.3s ease;
            z-index: 1001;
            display: flex;
            flex-direction: column;
        }

        .sidenav.open {
            width: 280px;
        }

        .sidenav-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            /* Adjusted padding */
            background-color: rgba(0, 0, 0, 0.2);
            /* Darker header background */
        }

        .sidenav-header h1 {
            margin: 0;
            font-size: 2rem;
            /* Retained size */
            color: white;
        }

        .sidenav-header .closebtn {
            font-size: 2rem;
            /* Adjusted size */
            color: white;
            text-decoration: none;
            cursor: pointer;
        }

        .sidenav-item {
            padding: 20px 20px;
            margin: 5px 10px;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            transition: background-color 0.2s ease;
        }

        .sidenav-item:hover,
        .sidenav-item.active {
            background-color: #0ea5e9;
            color: white;
            border-radius: 8px;
            margin-top: 5px;
            margin-bottom: 5px;
            margin-left: 10px;
            margin-right: 10px;
        }

        .sidenav-content {
            margin: 30px 0 0 0;
            /* Adjusted padding */
            overflow-y: auto;
            flex: 1;
        }

        .sidenav-footer {
            margin-top: auto;
            padding: 1.2rem;
            background-color: white;
            border-top: none;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin: 1rem;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transform: translateY(5px);
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            overflow-y: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .sidenav-footer>*::-webkit-scrollbar {
            display: none;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0;
        }

        .user-info img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-details {
            font-size: 0.85rem;
            color: #374151;
            font-family: 'Poppins', sans-serif;

        }

        .user-details strong {
            display: block;
            font-weight: 500;
            color: black;
            font-family: 'Poppins', sans-serif;

        }

        .sidenav-footer-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sidenav-footer-options a {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #4b5563;
            text-decoration: none;
            font-size: 0.9rem;
            padding: 0.5rem 0;
            border-radius: 4px;
            transition: background-color 0.15s ease-in-out;
            font-family: 'Poppins', sans-serif;

        }

        .sidenav-footer-options a:hover {
            background-color: #f3f4f6;
            color: #374151;
        }

        .sidenav-footer-options a .material-symbols-outlined {
            font-size: 1.1rem;
        }


        .sidenav-footer>a {
            display: none !important;
        }

        .main {
            margin-left: 0;
            padding: 80px 1rem 1rem;
            transition: margin-left 0.3s ease;
        }

        .main.open {
            margin-left: 280px;
            padding-top: 80px;
        }

        .toolbar.open {
            left: 280px;
            width: calc(100% - 280px);
        }

        @media screen and (max-width: 768px) {
            .sidenav.open {
                width: 100%;
            }

            .main.open {
                margin-left: 0;
            }

            .toolbar.open {
                left: 0;
                width: 100%;
            }

            .toolbar {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                padding: 0.3rem 0.5rem;
                height: auto;
                gap: 0.3rem;
                flex-wrap: nowrap;
            }

            .toolbar-left {
                display: flex;
                align-items: center;
                gap: 0.3rem;
                margin-bottom: 0;
            }

            .toolbar-left h2 {
                font-size: 1rem;

            }

            .toolbar-left button {
                font-size: 1.3rem;
            }

            .toolbar-search {
                display: flex;
                align-items: center;
                margin: 0;
                padding: 0.2rem 0.4rem;
                background-color: #e5e7eb;
                border-radius: 6px;
                flex-grow: 1;
            }

            .toolbar-search input {
                font-size: 0.8rem;
                padding-left: 0.3rem;
            }

            .toolbar-search input::placeholder {
                color: #6b7280;
                font-size: 0.8rem;
            }

            .toolbar-search .material-symbols-outlined {
                font-size: 1rem;
                margin-right: 0.2rem;
            }

            .toolbar-actions {
                display: flex;
                align-items: center;
                gap: 0.3rem;
            }

            .toolbar-actions .notification .material-symbols-outlined {
                font-size: 1.5rem;
            }
        }

        .material-symbols-outlined {
            font-size: 1.2rem;
            vertical-align: middle;
        }

        /* Specific color for MF */
        .sidenav-header h1 .mf {
            color: #00B4D8;
            /* Your preferred blue for MF */
            font-size: inherit;
            /* Inherit size from h1 */
        }

        /* Style for CLINIC to ensure it's white if needed */
        .sidenav-header h1 .clinic {
            color: white;
            font-size: inherit;
            /* Inherit size from h1 */
        }

        .sidenav-header h1 {
            font-size: 2rem;
            /* Increased size for "MF CLINIC" */
        }
    </style>
</head>

<body>
    <div class="toolbar open">
        <div class="toolbar-left">
            <button onclick="toggleNav()">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h2 id="pageTitle"></h2>
        </div>
        <div class="toolbar-search">
            <span class="material-symbols-outlined">search</span>
            <input type="text" placeholder="Search anything...">
            <button style="background: none; border: none;">
                <span class="material-symbols-outlined">keyboard</span> + K
            </button>
        </div>
        <div class="toolbar-actions">
            <div class="notification">
                <span class="material-symbols-outlined">notifications</span>
            </div>
        </div>
    </div>

    <div id="mySidenav" class="sidenav open">
        <div class="sidenav-header">
            <h1><span class="mf">MF</span> CLINIC</h1>
            <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
        </div>
        <div class="sidenav-content">
            <a href="?page=dashboard" class="sidenav-item active">
                <span class="material-symbols-outlined">home</span> Dashboard
            </a>
            <a href="?page=patients" class="sidenav-item">
                <span class="material-symbols-outlined">group</span> Patients
            </a>
            <a href="?page=appointments" class="sidenav-item">
                <span class="material-symbols-outlined">calendar_today</span> Appointments
            </a>
            <a href="?page=medical_records" class="sidenav-item">
                <span class="material-symbols-outlined">note</span> Medical Records
            </a>
            <a href="?page=billing_records" class="sidenav-item">
                <span class="material-symbols-outlined">receipt</span> Billing Records
            </a>
        </div>
        <div class="sidenav-footer">
            <div class="user-info">
                <img src="https://via.placeholder.com/30" alt="User Avatar">
                <div class="user-details">
                    <strong>John</strong>
                    <small>Nurse</small>
                </div>
            </div>
            <div class="sidenav-footer-options">
                <a href="#">
                    <span class="material-symbols-outlined">settings</span>
                    Settings
                </a>
                <a href="#">
                    <span class="material-symbols-outlined">logout</span>
                    Log Out
                </a>
            </div>
        </div>
    </div>

    <div class="main open">
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

    <script>
        const sidenav = document.getElementById("mySidenav");
        const main = document.querySelector(".main");
        const toolbar = document.querySelector(".toolbar");
        const pageTitleElement = document.getElementById("pageTitle");

        let isNavOpen = true;

        function openNav() {
            sidenav.classList.add("open");
            main.classList.add("open");
            toolbar.classList.add("open");
            isNavOpen = true;
        }

        function closeNav() {
            sidenav.classList.remove("open");
            main.classList.remove("open");
            toolbar.classList.remove("open");
            isNavOpen = false;
        }

        function toggleNav() {
            isNavOpen ? closeNav() : openNav();
        }

        function updateToolbarTitle() {
            const urlParams = new URLSearchParams(window.location.search);
            const page = urlParams.get('page')
            pageTitleElement.textContent = page.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
        }

        function updateActiveLink() {
            const links = document.querySelectorAll('.sidenav-item');
            const page = new URLSearchParams(window.location.search).get('page');
            links.forEach(link => {
                link.classList.toggle('active', link.href.includes(page));
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            openNav(); // Ensure it's open at start
            updateToolbarTitle();
            updateActiveLink();
            updateMobileSearchPlaceholder();
        });

        window.addEventListener('popstate', () => {
            updateToolbarTitle();
            updateActiveLink();
        });
        function updateMobileSearchPlaceholder() {
            if (window.innerWidth <= 768) {
                const searchInput = document.querySelector('.toolbar-search input');
                if (searchInput) {
                    searchInput.placeholder = 'Search';
                }
            } else {
                const searchInput = document.querySelector('.toolbar-search input');
                if (searchInput) {
                    searchInput.placeholder = 'Search anything...';
                }
            }
        }

        window.addEventListener('resize', updateMobileSearchPlaceholder);
    </script>
</body>

</html>