<?php
session_start();
require_once('models/User.php');
$user = new User('nurse');
$_SESSION['user'] = $user;

if (isset($_SESSION['user'])) {
    $user = $_SESSION['user'];
    if ($user instanceof User) {
        include('views/nurse/index.php');
    }
} else {
    echo "No user logged in.";
}
?>
