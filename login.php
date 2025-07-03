<?php
session_start();
require '../includes/db.php'; // Make sure path is correct

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === 'headmaster') {
            header("Location: ../dashboard/headmaster.php");
        } else {
            header("Location: ../dashboard/teacher.php");
        }
        exit();
    } else {
        echo "Invalid credentials.";
    }
}
?>
