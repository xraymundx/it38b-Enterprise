<style>
    .hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 80px 60px;
        background-color: rgb(255, 255, 255);
        min-height: calc(100vh - 120px);
        width: 100%;
        flex-wrap: wrap;
        position: relative;

        /* Set this to allow positioning of child elements */
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
            /* Center the hero image */
            align-items: center;
            width: 100%;
            /* Ensure the container takes up full width */
        }

        .hero-image-placeholder {
            background-color: #f0f0f0;
            width: 100%;
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

        .hero-button {
            font-size: 1em;
            padding: 14px 28px;
        }
    }
</style>

<div class="hero-text">
    <h1><span>MF</span> CLINIC</h1>
    <p>We're always ready to help you. You care, we care.</p>
    <a href="#" class="hero-button">Book an appointment!</a>
</div>
<div class="hero-image-container">
    <img src="resources/doctor.svg" alt="Hero Image" class="hero-image-placeholder">
</div>