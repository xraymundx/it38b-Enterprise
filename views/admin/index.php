<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is an administrator
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'administrator') {
    header('Location: /login.php'); // Adjust the path as needed
    exit();
}

require_once __DIR__ . '/../../config/config.php';
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$adminUser = mysqli_fetch_assoc($result);

// Base path for including admin-specific pages
$base_path = ''; // Assuming your admin pages are in a 'pages' subdirectory

// Determine the current page
$page = $_GET['page'] ?? 'dashboard'; // Default to 'dashboard'

// Array of allowed admin pages (match the files you created)
$allowedPages = [
    'dashboard',
    'users',
    'create_user',
    'edit_user',
    'edit_appointment',
    'doctors',
    'create_doctor',
    'edit_doctor',
    'patients',
    'create_patient',
    'edit_patient',
    'nurses',
    'create_nurse',
    'edit_nurse',
    'specializations',
    'create_specialization',
    'edit_specialization',
    'appointments',
    'logs',
    'settings',
    'audit_search',
    'error', // Make sure 'error' is in the allowed pages
    // Add more pages as you create them
];

if (!in_array($page, $allowedPages)) {
    $page = 'error'; // Redirect to an error page if not allowed
}

$pagePath = __DIR__ . '/' . $base_path . '/' . $page . '.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background-color: #f3f4f6;
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
        }

        .toolbar-left h2 {
            font-size: 1.5rem;
            /* Adjusted font size for admin */
            font-weight: 600;
            margin: 0;
            font-family: 'Poppins', sans-serif;
            color: #111827;
        }

        .toolbar-left button {
            background: none;
            border: none;
            padding: 0;
            font-size: 1.5rem;
            /* Adjusted font size for admin */
            color: #111827;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease;
            flex-shrink: 0;
        }

        .toolbar-left button .material-symbols-outlined {
            font-size: inherit;
        }

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
            color: #374151;
            min-width: 0;
        }

        .toolbar-search .material-symbols-outlined {
            font-size: 1.2rem;
            color: #6b7280;
            flex-shrink: 0;
        }

        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .toolbar-actions button:hover {
            background-color: #e5e7eb;
        }

        .toolbar-actions .notification .material-symbols-outlined {
            font-size: 1.5rem;
            /* Adjusted font size for admin */
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
            /* Original sidebar gradient */
            color: white;
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
            background-color: rgba(0, 0, 0, 0.2);
        }

        .sidenav-header h1 {
            margin: 0;
            font-size: 1.8rem;
            /* Adjusted font size for admin */
            color: white;
        }

        .sidenav-header .closebtn {
            font-size: 2rem;
            color: white;
            text-decoration: none;
            cursor: pointer;
        }

        .sidenav-item {
            padding: 12px 20px;
            margin: 2px 10px;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            transition: background-color 0.2s ease;
            border-radius: 6px;
        }

        .sidenav-item:hover,
        .sidenav-item.active {
            background-color: #0ea5e9;
            /* Original active color */
            color: white;
            margin-top: 2px;
            margin-bottom: 2px;
            margin-left: 10px;
            margin-right: 10px;
        }

        .sidenav-content {
            margin: 30px 0 0 0;
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

        /* Specific color for Admin */
        .sidenav-header h1 .admin-site {
            color: #00B4D8;
            /* Using the blue color */
            font-size: inherit;
        }

        .sidenav-header h1 .panel {
            color: white;
            font-size: inherit;
        }

        .sidenav-header h1 {
            font-size: 1.8rem;
        }
    </style>
</head>

<body>
    <div class="toolbar open">
        <div class="toolbar-left">
            <button onclick="toggleNav()">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h2 id="pageTitle">Admin Dashboard</h2>
        </div>
        <div class="toolbar-search">
            <span class="material-symbols-outlined">search</span>
            <input type="text" placeholder="Search admin functions...">
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
            <h1><span class="admin-site">Admin</span> <span class="panel">Panel</span></h1>
            <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
        </div>
        <div class="sidenav-content">
            <a href="?page=dashboard" class="sidenav-item">
                <span class="material-symbols-outlined">home</span> Dashboard
            </a>
            <a href="?page=logs" class="sidenav-item">
                <span class="material-symbols-outlined">policy</span> Logs
            </a>
            <a href="?page=users" class="sidenav-item">
                <span class="material-symbols-outlined">group</span> Users
            </a>
            <a href="?page=doctors" class="sidenav-item">
                <span class="material-symbols-outlined">local_hospital</span> Doctors
            </a>
            <a href="?page=patients" class="sidenav-item">
                <span class="material-symbols-outlined">person</span> Patients
            </a>
            <a href="?page=nurses" class="sidenav-item">
                <span class="material-symbols-outlined">masks</span> Nurses
            </a>
            <a href="?page=specializations" class="sidenav-item">
                <span class="material-symbols-outlined">category</span> Specializations
            </a>
            <a href="?page=appointments" class="sidenav-item">
                <span class="material-symbols-outlined">calendar_today</span> Appointments
            </a>
        </div>
        <div<div class="sidenav-footer">
            <?php if (isset($adminUser)): ?>
                <div class="user-info">
                    <img src="https://via.placeholder.com/30" alt="Admin Avatar">
                    <div class="user-details">
                        <strong><?php echo htmlspecialchars($adminUser['first_name'] . ' ' . $adminUser['last_name']); ?></strong>
                        <small>Administrator</small>
                    </div>
                </div>
                <div class="sidenav-footer-options">
                    <a href="?page=settings">
                        <span class="material-symbols-outlined">settings</span>
                        Settings
                    </a>
                    <a href="/logout.php">
                        <span class="material-symbols-outlined">logout</span>
                        Log Out
                    </a>
                </div>
            <?php endif; ?>
    </div>
    </div>
    <div class="main open">
        <div class="maincontainer">
            <?php
            switch ($page) {
                case 'dashboard':
                    include __DIR__ . '/' . $base_path . '/dashboard.php';
                    break;
                case 'logs':
                    include __DIR__ . '/' . $base_path . '/logs.php';
                    break;
                case 'users':
                    include __DIR__ . '/' . $base_path . '/users.php';
                    break;
                case 'create_user':
                    include __DIR__ . '/' . $base_path . '/create_user.php';
                    break;
                case 'edit_user':
                    include __DIR__ . '/' . $base_path . '/edit_user.php';
                    break;
                case 'doctors':
                    include __DIR__ . '/' . $base_path . '/doctors.php';
                    break;
                case 'create_doctor':
                    include __DIR__ . '/' . $base_path . '/create_doctor.php';
                    break;
                case 'edit_doctor':
                    include __DIR__ . '/' . $base_path . '/edit_doctor.php';
                    break;
                case 'patients':
                    include __DIR__ . '/' . $base_path . '/patients.php';
                    break;
                case 'create_patient':
                    include __DIR__ . '/' . $base_path . '/create_patient.php';
                    break;
                case 'edit_patient':
                    include __DIR__ . '/' . $base_path . '/edit_patient.php';
                    break;
                case 'nurses':
                    include __DIR__ . '/' . $base_path . '/nurses.php';
                    break;
                case 'create_nurse':
                    include __DIR__ . '/' . $base_path . '/create_nurse.php';
                    break;
                case 'edit_nurse':
                    include __DIR__ . '/' . $base_path . '/edit_nurse.php';
                    break;
                case 'specializations':
                    include __DIR__ . '/' . $base_path . '/specializations.php';
                    break;
                case 'create_specialization':
                    include __DIR__ . '/' . $base_path . '/create_specialization.php';
                    break;
                case 'edit_specialization':
                    include __DIR__ . '/' . $base_path . '/edit_specialization.php';
                    break;

                case 'appointments':
                    include __DIR__ . '/' . $base_path . '/appointments.php';
                    break;
                case 'settings':
                    include __DIR__ . '/' . $base_path . '/settings.php';
                    break;
                case 'edit_appointment':
                    include __DIR__ . '/' . $base_path . '/edit_appointment.php';
                    break;
                case 'error':
                    echo '<div class="p-4"><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">Error: Page not found.</div></div>';
                    break;
                default:
                    include __DIR__ . '/' . $base_path . '/dashboard.php'; // Default to dashboard
            }
            ?>
        </div>
    </div>

    <script>
        const sidenav = document.getElementById("mySidenav");
        const main = document.querySelector(".main");
        const toolbar = document.querySelector(".toolbar");
        const pageTitleElement = document.getElementById("pageTitle");
        const sidenavItems = document.querySelectorAll('.sidenav-item');
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
            const page = urlParams.get('page');
            if (pageTitleElement && page) {
                let title = page.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
                pageTitleElement.textContent = title;
            } else if (pageTitleElement) {
                pageTitleElement.textContent = 'Admin Dashboard';
            }
        }

        function updateActiveLink() {
            const currentUrl = window.location.href;

            sidenavItems.forEach(item => {
                item.classList.remove('active');
                const href = item.getAttribute('href');
                if (currentUrl.includes(href) && href !== '?page=') {
                    item.classList.add('active');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            openNav();
            updateToolbarTitle();
            updateActiveLink();
            updateMobileSearchPlaceholder();
        });

        window.addEventListener('popstate', () => {
            updateToolbarTitle();
            updateActiveLink();
        });

        function updateMobileSearchPlaceholder() {
            const searchInput = document.querySelector('.toolbar-search input');
            if (searchInput) {
                searchInput.placeholder = window.innerWidth <= 768 ? 'Search' : 'Search admin functions...';
            }
        }

        window.addEventListener('resize', updateMobileSearchPlaceholder);
    </script>
</body>

</html>