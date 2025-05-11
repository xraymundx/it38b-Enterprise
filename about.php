<div class="hero-text">
    <h2>About Us</h2>
    <p>
        <strong>MF</strong> Clinic offers fast, reliable, and patient-centered healthcare services using a smart
        system that simplifies appointments, records, and billing for a better clinic experience.
    </p>
    <p>
        Our dedicated team of healthcare professionals is committed to providing high-quality care and ensuring
        the well-being of our patients. We believe in a patient-first approach, where your comfort and health
        are our top priorities.
    </p>
    <p>
        Utilizing modern technology, we strive to make your healthcare journey seamless and efficient. From
        easy online appointment booking to secure digital records, we aim to provide a hassle-free experience.
    </p>
</div>
<div class="hero-image-container">
    <img src="resources/doctor.svg" alt="Hero Image" class="hero-image-placeholder">
</div>

<style>
    .hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background-color: rgb(255, 255, 255);
        padding: 80px 60px;
        min-height: calc(100vh - 80px);
        width: 100%;
        flex-wrap: wrap;
        transition: all 0.5s ease-in-out;
    }

    .hero-text {
        flex: 1 1 400px;
        padding-right: 40px;
    }

    .hero-text h2 {
        font-size: 3em;
        margin-bottom: 20px;
        color: #333;
    }

    .hero-text p {
        font-size: 1.2em;
        color: #555;
        line-height: 1.6;
        margin-bottom: 20px;
    }

    .hero-image-container {
        flex: 1 1 300px;
        text-align: center;
        margin-top: 30px;
    }

    .hero-image-placeholder {
        background-color: #f0f0f0;
        width: 100%;
        max-width: 853px;
        height: 643px;
        border-radius: 12px;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 1.5em;
        color: #777;
        margin: auto;
        background-color: white;
        overflow: hidden;
    }

    .hero-image-placeholder img {
        max-width: 100%;
        height: auto;
        display: block;
    }

    @media (max-width: 768px) {
        .hero {
            flex-direction: column;
            padding: 40px 20px;
            text-align: center;
            min-height: auto;
            /* Adjust min-height for mobile */
        }

        .hero-text {
            padding-right: 0;
            margin-bottom: 30px;
        }

        .hero-text h2 {
            font-size: 2.4em;
        }

        .hero-image-container {
            margin-top: 20px;
        }

        .hero-image-placeholder {
            max-width: 80%;
            height: auto;
        }
    }
</style>

<script>
    // Update the active link in the navigation
    document.addEventListener('DOMContentLoaded', function () {
        const navLinks = document.querySelectorAll('nav a');
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === '#about') {
                link.classList.add('active');
            }
        });
    });
</script>