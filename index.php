<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Landing Page</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <img src="logo.png" alt="My Website Logo" class="logo">
            <h1>Welcome to My Website</h1>
            <p>Your success starts here.</p>
            <a href="#contact" class="btn">Get Started</a>
        </div>
    </header>

    <section id="features">
        <h2>Features</h2>
        <ul>
            <li>Fast & Reliable</li>
            <li>Responsive Design</li>
            <li>Easy to Use</li>
        </ul>
    </section>

    <section id="contact">
        <h2>Contact Us</h2>
        <form action="index.php" method="POST">
            <input type="text" name="name" placeholder="Your Name" required>
            <input type="email" name="email" placeholder="Your Email" required>
            <button type="submit" name="submit">Send</button>
        </form>
        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
            $name = htmlspecialchars($_POST['name']);
            $email = htmlspecialchars($_POST['email']);
            echo "<p>Thank you, $name! We'll contact you at $email soon.</p>";
        }
        ?>
    </section>

    <footer>
        <p>&copy; 2025 My Website</p>
    </footer>
</body>
</html>