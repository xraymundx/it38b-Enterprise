<?php
session_start();
require_once('config/config.php');

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate inputs
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Use MySQLi for database connection
        global $conn;

        // Prepare statement to prevent SQL injection
        $stmt = $conn->prepare("
            SELECT
                u.user_id,
                u.email,
                u.password_hash,
                r.role_name,
                u.first_name,
                u.last_name,
                d.doctor_id
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.role_id
            LEFT JOIN doctors d ON u.user_id = d.user_id
            WHERE u.email = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role_name'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];

                // Set role-specific identifier
                switch ($_SESSION['role']) {
                    case 'doctor':
                        $_SESSION['doctor_id'] = $user['doctor_id']; // Store the doctor_id
                        break;
                    case 'admin':
                        $_SESSION['admin_id'] = $user['user_id'];
                        break;
                    case 'patient':
                        $_SESSION['patient_id'] = $user['user_id'];
                        break;
                    case 'nurse':
                        $_SESSION['nurse_id'] = $user['user_id'];
                        break;
                }
                // Update last login time
                $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                $updateStmt->bind_param("i", $user['user_id']);
                $updateStmt->execute();
                $updateStmt->close();

                // Log the login event
                $eventType = 'login';
                $description = "{$user['first_name']} logged in.";
                $logStmt = $conn->prepare("INSERT INTO logs (user_id, event_type, description, timestamp) VALUES (?, ?, ?, NOW())");
                $logStmt->bind_param("iss", $user['user_id'], $eventType, $description);
                $logStmt->execute();
                $logStmt->close();


                // Redirect to dashboard
                header('Location: routes/dashboard_router.php');
                exit;
            } else {
                $error = "Invalid credentials.";
            }
        } else {
            $error = "Invalid credentials.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MF Clinic Login</title>
    <style>
        /* Global Styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body,
        html {
            height: 100%;
            background: #fff;
        }

        /* Container Styles */
        .container {
            display: flex;
            height: 100vh;
        }

        /* Login Section Styles */
        .login-section,
        .image-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
        }

        .login-section {
            background: #fff;
        }

        /* Logo Styles */
        .logo {
            position: absolute;
            top: 20px;
            left: 30px;
            font-size: 24px;
            font-weight: bold;
            text-decoration: none;
            /* Remove default link styling */
            color: black;
            /* Default color for 'CLINIC' */
        }

        .logo span {
            color: #00b0f0;
            /* Blue for 'MF' */
        }

        /* Login Form Styles */
        .login-form {
            width: 100%;
            max-width: 400px;
        }

        .login-form h2 {
            margin-bottom: 20px;
            font-size: 28px;
        }

        .login-form input {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
        }

        .login-form button {
            width: 100%;
            padding: 12px;
            background-color: #00004d;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        /* Signup Link Styles */
        .signup-link {
            margin-top: 15px;
            font-size: 14px;
            text-align: center;
        }

        .signup-link a {
            color: #00b0f0;
            text-decoration: none;
        }

        /* Image Section Styles */
        .image-section img {
            width: 600px;
            max-width: 100%;
            transition: transform 0.5s ease-in-out;
        }

        .image-section:hover img {
            transform: scale(1.05);
            /* Zoom in effect */
        }

        /* Bottom Logo Styles */
        .image-section .logo-bottom {
            position: absolute;
            bottom: 100px;
            font-size: 50px;
            font-weight: bold;
            color: black;
            /* Default color for 'CLINIC' */
        }

        .image-section .logo-bottom span {
            color: #00b0f0;
            /* Blue for 'MF' */
        }
    </style>

</head>


<body>
    <div class="container">
        <div class="login-section">
            <a href="guest.php" class="logo"><span>MF</span> CLINIC</a>
            <div class="login-form">
                <h2>LOGIN</h2>
                <?php if (!empty($error)): ?>
                    <div class="error-message" style="color: red; margin-bottom: 10px;">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                <form action="login.php" method="POST">
                    <input type="text" name="email" placeholder="Enter Email" required>
                    <input type="password" name="password" placeholder="Enter Password" required>
                    <button type="submit">Log in</button>
                </form>
                <div class="signup-link">
                    You don't have an account? <a href="register.php">Sign up here!</a>
                </div>
            </div>
        </div>

        <div class="image-section">
            <img src="resources/doctor.svg" alt="Doctor">
            <div class="logo-bottom"><span>MF</span> CLINIC</div>
        </div>
    </div>
</body>

</html>