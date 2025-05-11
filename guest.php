<?php
// About and Contact content will be included dynamically
if (isset($_GET['page'])) {
    $page = $_GET['page'];
} else {
    $page = 'home';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MF Clinic</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Rubik+Moonrocks&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            background-color: white;
        }

        #map {
            height: 500px;
            width: 100%;
            border-radius: 12px;
        }

        /* Style for the map container */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 30px 60px;
            background-color: #fff;
            flex-wrap: wrap;
            width: 100%;
        }

        .logo {
            font-size: 2.5em;
            font-weight: bold;
        }

        .logo span {
            color: #00bcd4;
        }

        nav {
            flex: 1;
            display: flex;
            justify-content: center;
        }

        nav ul {
            list-style: none;
            display: flex;
            padding: 0;
            margin: 0;
            gap: 30px;
        }

        nav a {
            text-decoration: none;
            color: #333;
            padding: 8px 0;
            position: relative;
            font-weight: 500;
            font-size: 1.4em;
        }

        nav a.active::after {
            content: "";
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #00bcd4;
        }

        nav a:hover {
            color: #00bcd4;
        }

        .auth-links {
            display: flex;
            gap: 10px;
        }

        .auth-links a {
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }

        .auth-links a:first-child {
            color: #333;
        }

        .auth-links a:last-child {
            background-color: #00bcd4;
            color: white;
            box-shadow: 0 2px 4px rgba(0, 188, 212, 0.2);
        }

        .auth-links a:last-child:hover {
            background-color: #0097a7;
        }

        .hero {

            align-items: center;

            background-color: rgb(255, 255, 255);
            padding: 0;
            /* Remove default padding */
            min-height: calc(100vh - 80px);
            width: 100%;
            transition: all 0.5s ease-in-out;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
            margin: 0;
        }

        .hero.fade-in {
            opacity: 1;
        }

        .hero-text {
            flex: 1 1 400px;
            padding-right: 40px;
        }

        .hero-text h1 {
            font-size: 4em;
            margin-bottom: 20px;
        }

        .hero-text h1 span {
            color: #00bcd4;
        }

        .hero-text p {
            font-size: 1.3em;
            color: #333;
            background-color: #e0f7fa;
            padding: 20px 25px;
            border-left: 8px solid #00bcd4;
            border-top-right-radius: 20px;
            border-bottom-right-radius: 20px;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .hero-button {
            background-color: #1a237e;
            color: white;
            padding: 16px 32px;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1em;
            box-shadow: 0 4px 12px rgba(26, 35, 126, 0.2);
            transition: background-color 0.3s ease;
        }

        .hero-button:hover {
            background-color: #0d114d;
        }

        .hero-image-container {
            flex: 1 1 300px;
            text-align: center;
            margin-top: 30px;
        }

        .hero-image-placeholder {
            background-color: #f0f0f0;
            width: 100%;
            max-width: 320px;
            height: 320px;
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.5em;
            color: #777;
            margin: auto;
        }

        /* Footer Styles */
        .footer {
            background-color: #333;
            color: white;
            padding: 40px 60px;
            text-align: center;
            font-size: 0.9em;
        }

        .footer p {
            margin-bottom: 10px;
        }

        .footer a {
            color: #00bcd4;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer a:hover {
            color: #26c6da;
        }


        /* Mobile Styling */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                align-items: flex-start;
                padding: 20px;
                position: relative;
                /* Needed for absolute positioning of burger */
            }

            .footer {
                padding: 30px 20px;
                font-size: 0.8em;
            }

            nav {
                justify-content: flex-start;
                width: 100%;
                display: none;
                /* Initially hide the navigation */
                margin-top: 10px;
            }

            nav ul {
                flex-direction: column;
                gap: 10px;
                margin-top: 0;
            }

            .auth-links {
                flex-direction: column;
                align-items: flex-start;
                width: 100%;
                margin-top: 20px;
                display: none;
                /* Initially hide auth links */
            }

            .auth-links a {
                width: 100%;
                text-align: center;
            }

            .hero {
                flex-direction: column;
                padding: 40px 20px;
                text-align: center;
                min-height: calc(100vh - 140px);
                /* Adjust for stacked header elements */
            }

            .hero-text {
                padding: 0;
                margin-top: 20px;
                /* Push text down a bit */
            }

            .hero-text h1 {
                font-size: 2.4em;
            }

            .hero-text p {
                font-size: 1.1em;
                border-left: 6px solid #00bcd4;
                border-top-right-radius: 16px;
                border-bottom-right-radius: 16px;
            }

            .hero-image-placeholder {
                width: 80%;
                aspect-ratio: 1 / 1;
                max-width: none;
                /* Remove max-width for better scaling */
                height: auto;
                /* Adjust height automatically */
            }

            /* Burger Menu Styles */
            .burger {
                display: block;
                cursor: pointer;
                width: 30px;
                height: 3px;
                background-color: #333;
                position: absolute;
                top: 30px;
                /* Adjust top position */
                right: 20px;
                /* Adjust right position */
                transition: all 0.3s ease;
            }

            .burger:before,
            .burger:after {
                content: '';
                position: absolute;
                left: 0;
                width: 30px;
                height: 3px;
                background-color: #333;
                transition: all 0.3s ease;
            }

            .burger:before {
                top: -10px;
            }

            .burger:after {
                top: 10px;
            }

            .burger.active {
                background-color: transparent;
            }

            .burger.active:before {
                transform: translateY(10px) rotate(45deg);
            }

            .burger.active:after {
                transform: translateY(-10px) rotate(-45deg);
            }

            /* Show nav and auth links when burger is active */
            nav.open,
            .auth-links.open {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            // Toggle burger menu and navigation/auth links
            $(".burger").on("click", function () {
                $(this).toggleClass("active");
                $("nav").toggleClass("open");
                $(".auth-links").toggleClass("open");
            });

            // Highlight the active nav link
            $("nav a").on("click", function (e) {
                e.preventDefault();
                $("nav a").removeClass("active");
                $(this).addClass("active");

                var page = $(this).attr("href").substring(1); // Get the page (about, contact, etc.)
                loadContent(page);

                // Close the mobile menu after clicking a link
                $(".burger").removeClass("active");
                $("nav").removeClass("open");
                $(".auth-links").removeClass("open");
            });

            function loadContent(page) {
                $(".hero").removeClass("fade-in").fadeOut(100, function () { // Quickly fade out the old content and remove the fade-in class
                    $.ajax({
                        url: page + ".php",
                        success: function (data) {
                            $(".hero").html(data).fadeIn(100, function () { // Quickly fade in the new content and then add the fade-in class
                                $(".hero").addClass("fade-in");
                                // Initialize Leaflet map ONLY after the content is loaded and on the contact page
                                if (page === 'contact') {
                                    initMap();
                                }
                            });
                        }
                    });
                });
            }

            function initMap() {
                var map = L.map('map').setView([8.372516, 124.856571], 15); // Coordinates and zoom level

                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);

                L.marker([8.372516, 124.856571]).addTo(map)
                    .bindPopup('MF Clinic Location')
                    .openPopup();
            }

            // Initially load home page content
            loadContent('<?php echo $page; ?>');
        });
    </script>
</head>

<body>
    <header>
        <div class="logo"><span>MF</span> CLINIC</div>
        <div class="burger"></div>
        <nav>
            <ul>
                <li><a href="#home" class="active">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </nav>
        <div class="auth-links">
            <a href="login.php" onclick="window.location.href='login.php'; return false;">Log In</a>
            <a href="#">Sign Up</a>
        </div>
    </header>

    <section class="hero">

    </section>


    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> MF Clinic. All rights reserved.</p>
        <p>Located in Manolo Fortich, Northern Mindanao, Philippines.</p>
        <p><a href="#">Privacy Policy</a> | <a href="#">Terms of Service</a></p>
    </footer>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
</body>

</html>