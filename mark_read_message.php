<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'headmaster') {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_id'])) {
    $message_id = intval($_POST['message_id']);
    $stmt = $conn->prepare('UPDATE messages SET status = "read" WHERE id = ?');
    if ($stmt->execute([$message_id])) {
        header('Location: message.php?success=marked_read');
        exit();
    } else {
        header('Location: message.php?error=mark_read_failed');
        exit();
    }
} else {
    header('Location: message.php?error=invalid_request');
    exit();
} 