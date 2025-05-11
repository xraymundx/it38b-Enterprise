<div class="hero-text contact-info">
    <h2>Contact Us</h2>
    <p class="contact-intro">Feel free to contact our clinic for any inquiries.</p>
    <ul class="contact-details">
        <li>
            <i class="material-icons">phone</i>
            <a href="tel:09758375471">09758375471</a>
        </li>
        <li>
            <i class="material-icons">facebook</i>
            <a href="https://www.facebook.com/MFClinic" target="_blank">MF Clinic</a>
        </li>
        <li>
            <i class="material-icons">mail</i>
            <a href="mailto:MFClinic@gmail.com">MFClinic@gmail.com</a>
        </li>
        <li>
            <i class="material-icons">language</i>
            <a href="http://MFClinicatyourservice.com" target="_blank">MFClinicatyourservice.com</a>
        </li>
        <li>
            <i class="material-icons">location_on</i>
            <span>Tankulan, Manolo Fortich, Bukidnon Philippines</span>
        </li>
    </ul>
</div>
<div class="hero-image-container map-container">
    <div id="map"></div>
</div>

<style>
    .hero {
        display: flex;
        align-items: flex-start;
        /* Align items to the top */
        justify-content: space-between;
        background-color: rgb(255, 255, 255);
        padding: 80px 60px;
        min-height: calc(100vh - 80px);
        width: 100%;
        flex-wrap: wrap;
        transition: all 0.5s ease-in-out;
    }

    .contact-info {
        flex: 1 1 400px;
        padding-right: 40px;
    }

    .contact-info h2 {
        font-size: 3em;
        margin-bottom: 20px;
        color: #333;
    }

    .contact-intro {
        font-size: 1.2em;
        color: #555;
        margin-bottom: 30px;
    }

    .contact-details {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .contact-details li {
        display: flex;
        align-items: center;
        font-size: 1.1em;
        color: #333;
        margin-bottom: 15px;
    }

    .contact-details i.material-icons {
        font-size: 1.5em;
        color: #00bcd4;
        margin-right: 15px;
    }

    .contact-details a {
        text-decoration: none;
        color: #333;
    }

    .contact-details a:hover {
        color: #00bcd4;
    }

    .map-container {
        flex: 1 1 300px;
        text-align: center;
        margin-top: 30px;
    }

    .map-placeholder {
        background-color: #f0f0f0;
        width: 100%;
        max-width: 400px;
        height: 300px;
        border-radius: 12px;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 1.2em;
        color: #777;
        margin: auto;
        overflow: hidden;
        /* To contain the map if embedded */
    }

    .map-placeholder img {
        display: block;
        width: 100%;
        height: auto;
        object-fit: cover;
        /* Ensure the image covers the placeholder */
    }

    @media (max-width: 768px) {
        .hero {
            flex-direction: column;
            padding: 40px 20px;
            align-items: center;
            /* Center items on mobile */
            min-height: auto;
        }

        .contact-info {
            padding-right: 0;
            margin-bottom: 30px;
            text-align: center;
        }

        .contact-details {
            text-align: left;
        }

        .map-container {
            margin-top: 20px;
            width: 80%;
        }

        .map-placeholder {
            max-width: 100%;
            height: auto;
        }
    }
</style>

<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const navLinks = document.querySelectorAll('nav a');
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === '#contact') {
                link.classList.add('active');
            }
        });
    });
</script>