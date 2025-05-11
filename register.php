<?php
require 'config/config.php';

$errors = [];
$success = '';

// Retrieve appointment data from URL parameters
$appointmentFirstName = $_GET['first_name'] ?? '';
$appointmentLastName = $_GET['last_name'] ?? '';
$appointmentEmail = $_GET['email'] ?? '';
$appointmentDate = $_GET['appointment_date'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $user_type = $_POST['user_type'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (!$first_name || !$last_name || !$email || !$phone_number || !$user_type || !$password || !$confirm_password) {
        $errors[] = "All fields are required.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Check for duplicate email
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        $errors[] = "Email is already registered.";
    }

    if (empty($errors)) {
        // Hash password
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        // Insert user
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone_number, user_type, password_hash)
                                 VALUES (:first_name, :last_name, :email, :phone_number, :user_type, :password_hash)");
        $stmt->execute([
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone_number' => $phone_number,
            'user_type' => $user_type,
            'password_hash' => $password_hash
        ]);

        $success = "Registration successful! You can now <a href='login.php'>log in</a>.";

        // Optionally, you could also directly save the appointment here,
        // now that the user is registered. You'd need to access the
        // $appointmentDate as well.
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MF Clinic Register</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body,
        html {
            height: 100%;
            background-color: #fff;
        }

        .container {
            display: flex;
            height: 100vh;
        }

        .register-section,
        .image-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
        }

        .register-section {
            background: #fff;
        }

        .image-section {
            background: #fff;
            position: relative;
            overflow: hidden;
        }

        .logo {
            position: absolute;
            top: 20px;
            left: 30px;
            font-size: 24px;
            font-weight: bold;
        }

        .logo span {
            color: #00b0f0;
            /* Blue for 'MF' */
        }

        .register-form {
            width: 100%;
            max-width: 400px;
        }

        .register-form h2 {
            margin-bottom: 20px;
            font-size: 28px;
        }

        .input-group {
            display: flex;
            justify-content: space-between;
        }

        .input-group input {
            width: 48%;
        }

        .register-form input,
        .register-form select,
        .register-form button {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
        }

        .register-form button {
            background-color: #00004d;
            color: white;
            cursor: pointer;
        }

        .register-form button:hover {
            background-color: #00b0f0;
        }

        .login-link {
            margin-top: 15px;
            font-size: 14px;
            text-align: center;
        }

        .login-link a {
            color: #00b0f0;
            text-decoration: none;
        }

        .image-section img {
            width: 600px;
            max-width: 100%;
            transition: transform 0.5s ease-in-out;
        }

        .image-section:hover img {
            transform: scale(1.05);
            /* Zoom in effect */
        }
    </style>
</head>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MF Clinic Register</title>
    <style>
        /* ... your register.php styles ... */
    </style>
</head>

<body>
    <div class="container">
        <div class="register-section">
            <div class="logo"><span>MF</span> CLINIC</div>
            <div class="register-form">
                <h2>Sign Up</h2>

                <?php if (!empty($errors)): ?>
                    <div style="color: red;">
                        <?php foreach ($errors as $error): ?>
                            <p><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($success): ?>
                    <div style="color: green;">
                        <p><?= $success ?></p>
                    </div>
                <?php endif; ?>

                <form action="register.php" method="POST">
                    <div class="input-group">
                        <input type="text" name="first_name" placeholder="First Name"
                            value="<?= htmlspecialchars($appointmentFirstName) ?>" required>
                        <input type="text" name="last_name" placeholder="Last Name"
                            value="<?= htmlspecialchars($appointmentLastName) ?>" required>
                    </div>
                    <input type="email" name="email" placeholder="Enter Email"
                        value="<?= htmlspecialchars($appointmentEmail) ?>" required>
                    <input type="tel" name="phone_number" placeholder="Enter Phone Number" required>
                    <select name="user_type" required>
                        <option value="">Select User Type</option>
                        <option value="patient">Patient</option>
                        <option value="admin">Admin</option>
                        <option value="nurse">Nurse</option>
                        <option value="doctor">Doctor</option>
                    </select>
                    <input type="password" name="password" placeholder="Password" required>
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                    <button type="submit">Register</button>
                </form>

                <div class="login-link">
                    Already have an account? <a href="login.php">Log In here!</a>
                </div>
            </div>
        </div>

        <div class="image-section">
            <img src="resources\doctor.svg" alt="Doctor">
        </div>
    </div>
</body>

</html>