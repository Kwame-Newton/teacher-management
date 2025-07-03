<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_id'])) {
    $message_id = intval($_POST['message_id']);
    // Only delete if it's a group message (announcement)
    $stmt = $conn->prepare('DELETE FROM messages WHERE id = ? AND receiver_group IS NOT NULL');
    if ($stmt->execute([$message_id])) {
        // Redirect to the correct messages page based on role
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher') {
            header('Location: teacher_messages.php?success=announcement_deleted');
        } else {
            header('Location: message.php?success=announcement_deleted');
        }
        exit();
    } else {
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher') {
            header('Location: teacher_messages.php?error=announcement_delete_failed');
        } else {
            header('Location: message.php?error=announcement_delete_failed');
        }
        exit();
    }
} else {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher') {
        header('Location: teacher_messages.php?error=invalid_request');
    } else {
        header('Location: message.php?error=invalid_request');
    }
    exit();
} 