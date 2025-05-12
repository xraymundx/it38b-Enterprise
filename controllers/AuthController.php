<?php
require_once 'models/User.php';
require_once 'config/functions.php';

class AuthController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function attempt($email, $password)
    {
        if (!$email || !$password) {
            return "Please enter both email and password.";
        }

        $stmt = $this->conn->prepare("SELECT * FROM users u LEFT JOIN roles r ON u.role_id = r.role_id WHERE u.email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role_name'];
            $_SESSION['first_name'] = $user['first_name'];

            // Update login time & log event
            $this->conn->query("UPDATE users SET last_login = NOW() WHERE user_id = {$user['user_id']}");
            log_event($this->conn, $user['user_id'], 'login', "{$user['first_name']} logged in.");

            return true;
        }

        return "Invalid credentials.";
    }
}
