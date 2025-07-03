<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['headmaster', 'teacher'])) {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_id'])) {
    $message_id = intval($_POST['message_id']);
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    if ($role === 'headmaster') {
        // Headmaster can delete any message
        $stmt = $conn->prepare('DELETE FROM messages WHERE id = ?');
        $redirect = 'message.php';
        $success = 'success=deleted';
        $error = 'error=delete_failed';
    } elseif ($role === 'teacher') {
        // Teacher can only delete messages sent to them
        $stmt = $conn->prepare('DELETE FROM messages WHERE id = ? AND receiver_id = ?');
        $redirect = 'teacher_messages.php';
        $success = 'success=deleted';
        $error = 'error=delete_failed';
    }
    
    if ($role === 'headmaster') {
        $result = $stmt->execute([$message_id]);
    } else {
        $result = $stmt->execute([$message_id, $user_id]);
    }
    if ($result) {
        header('Location: ' . $redirect . '?' . $success);
        exit();
    } else {
        header('Location: ' . $redirect . '?' . $error);
        exit();
    }
} else {
    $redirect = ($_SESSION['role'] === 'teacher') ? 'teacher_messages.php' : 'message.php';
    header('Location: ' . $redirect . '?error=invalid_request');
    exit();
} 