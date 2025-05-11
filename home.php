<style>
    /* Hero Section Styles (as provided) */
    .hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 80px 60px;
        background-color: rgb(255, 255, 255);
        min-height: calc(100vh - 200px);
        width: 100%;
        flex-wrap: wrap;
        position: relative;
        margin: 0;
    }

    .setCol {
        flex-direction: column;
        /* Force one column layout */
    }

    .hero-text {
        flex: 1 1 400px;
        padding-right: 40px;
        margin-top: -120px;
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
        }

        .hero-text {
            padding: 0;
            margin-top: 0;
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

        .hero-image-container {
            margin-top: -139px;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }

        .hero-image-placeholder {
            width: 100%;
            height: auto;
            /* Adjust height for responsiveness */
            max-height: 400px;
            /* Optional: set a maximum height */
        }

        .hero-button {
            font-size: 1em;
            padding: 14px 28px;
        }
    }

    /* Our Process Section Styles */
    .process-div {
        /* Changed selector name */
        padding: 60px;
        background-color: rgb(255, 255, 255);
        text-align: center;
    }

    .process-div h2 {
        /* Changed selector name */
        font-size: 2.5em;
        color: #333;
        margin-bottom: 40px;
    }

    .process-steps {
        display: flex;
        justify-content: space-around;
        flex-wrap: wrap;
        margin-top: 30px;
    }

    /* Process Step Numbers */
    .step-number {
        font-family: 'Anton', sans-serif;
        font-size: 4em;
        color: transparent;
        -webkit-text-stroke: 2px #00bcd4;
        text-stroke: 2px #00bcd4;
        margin-bottom: 15px;
        line-height: 1;
    }

    .step {
        flex: 1 1 300px;
        padding: 30px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        margin: 20px;
        text-align: center;
    }

    /* Updated Step Style */
    .step h3 {
        font-size: 1.5em;
        margin-bottom: 10px;
        color: #333;
    }

    .step p {
        color: #666;
        line-height: 1.6;
    }

    @media (max-width: 768px) {
        .process-div {
            /* Changed selector name */
            padding: 40px 20px;
        }

        .process-div h2 {
            /* Changed selector name */
            font-size: 2em;
            margin-bottom: 30px;
        }

        .process-steps {
            flex-direction: column;
            align-items: center;
        }

        .step {
            width: 90%;
            margin: 15px 0;
        }
    }


    /* Appointment Form Styles */
    .appointment-form {
        max-width: 600px;
        margin: 0 auto;
        background-color: #ffffff;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        text-align: left;
    }

    .appointment-form label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
        color: #333;
    }

    .appointment-form input,
    .appointment-form select {
        width: 100%;
        padding: 12px;
        margin-bottom: 20px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 1em;
    }

    .appointment-form button {
        background-color: #1a237e;
        color: white;
        padding: 14px 28px;
        border: none;
        border-radius: 8px;
        font-size: 1.1em;
        cursor: pointer;
        width: 100%;
    }

    .appointment-form button:hover {
        background-color: #0d114d;
    }

    .appointment-div {
        /* Changed selector name */
        padding: 60px;
        background-color: #ffffff;
        text-align: center;
    }

    .appointment-div h2 {
        /* Changed selector name */
        font-size: 2.5em;
        color: #333;
        margin-bottom: 20px;
    }

    .appointment-cta {
        font-size: 1.3em;
        color: #555;
        margin-bottom: 30px;
    }

    @media (max-width: 768px) {
        .appointment-div {
            /* Changed selector name */
            padding: 40px 20px;
        }

        .appointment-div h2 {
            /* Changed selector name */
            font-size: 2em;
            margin-bottom: 30px;
        }

        .appointment-cta {
            font-size: 1.2em;
            margin-bottom: 20px;
        }

        .appointment-button {
            font-size: 1.1em;
            padding: 16px 32px;
        }

        .step-number {
            font-size: 3em;
        }

        .appointment-form {
            padding: 20px;
        }
    }
</style>


<div class="hero">
    <div class="hero-text">
        <h1><span>MF</span> CLINIC</h1>
        <p>We're always ready to help you. You care, we care.</p>
        <a href="#appointment" class="hero-button">Book an appointment!</a>
    </div>
    <div class="hero-image-container">
        <img src="resources/doctor.svg" alt="Hero Image" class="hero-image-placeholder">
    </div>
</div>
<div class="setCol">
    <div class="process-div">
        <h2>Our Process</h2>
        <div class="process-steps">
            <div class="step">
                <div class="step-number">1</div>
                <h3>Initial Consultation</h3>
                <p>We start with a thorough consultation to understand your health concerns and needs.</p>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <h3>Diagnosis & Treatment Plan</h3>
                <p>Our experienced medical professionals will provide accurate diagnosis and a personalized treatment
                    plan.
                </p>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <h3>Ongoing Care & Support</h3>
                <p>We offer continuous care and support throughout your treatment and recovery journey.</p>
            </div>
        </div>
    </div>

    <div id="appointment" class="appointment-div">
        <h2>Book an Appointment</h2>
        <p class="appointment-cta">Ready to take the next step? Schedule your appointment today!</p>

        <form class="appointment-form" id="appointmentForm">
            <label for="first-name">First Name</label>
            <input type="text" id="first-name" name="first_name" required>

            <label for="last-name">Last Name</label>
            <input type="text" id="last-name" name="last_name" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>

            <label for="appointment-date">Preferred Date</label>
            <input type="date" id="appointment-date" name="date" required>

            <button type="button" onclick="redirectToRegister()">Confirm Appointment</button>
        </form>
    </div>
</div>

<script>
    function redirectToRegister() {
        const form = document.getElementById('appointmentForm');
        const firstName = form.querySelector('#first-name').value;
        const lastName = form.querySelector('#last-name').value;
        const email = form.querySelector('#email').value;
        const appointmentDate = form.querySelector('#appointment-date').value;

        const registerUrl = `register.php?first_name=${encodeURIComponent(firstName)}&last_name=${encodeURIComponent(lastName)}&email=${encodeURIComponent(email)}&appointment_date=${encodeURIComponent(appointmentDate)}`;

        window.location.href = registerUrl;
    }

    // In a real application, you would likely check if the user is logged in
    // on the server-side before allowing the appointment confirmation.
    // This JavaScript function is for demonstration purposes of redirecting
    // with pre-filled data.
</script>