<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$sender_id = $_SESSION['user_id'];
$message = $_POST['message'] ?? '';
$receiver = $_POST['receiver'] ?? '';
$receiver_id = $_POST['receiver_id'] ?? null;
$group = null;
$is_emergency = isset($_POST['is_emergency']) ? 1 : 0;

// Validate message
if (empty($message) || (empty($receiver) && empty($receiver_id))) {
    header("Location: messages.php?error=empty_fields");
    exit();
}

// ✅ Mark previous messages as read (if replying)
if (isset($_POST['mark_read_sender'])) {
    $markSenderId = $_POST['mark_read_sender'];
    $stmt = $conn->prepare("UPDATE messages SET status = 'read' WHERE sender_id = ? AND receiver_id = ?");
    $stmt->execute([$markSenderId, $sender_id]);
}

// ✅ Handle group messages
if ($receiver === 'all_teachers' || $receiver === 'all_parents') {
    $group = $receiver;
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_group, message, is_emergency, status, timestamp) 
                            VALUES (?, ?, ?, ?, 'unread', NOW())");
    $stmt->execute([$sender_id, $group, $message, $is_emergency]);

// ✅ Handle individual teacher message (receiver = 'teacher:ID')
} elseif (str_starts_with($receiver, 'teacher:')) {
    $receiver_id = explode(':', $receiver)[1];
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, is_emergency, status, timestamp) 
                            VALUES (?, ?, ?, ?, 'unread', NOW())");
    $stmt->execute([$sender_id, $receiver_id, $message, $is_emergency]);

// ✅ Handle reply to headmaster (receiver = 'headmaster')
} elseif ($receiver === 'headmaster') {
    $stmt = $conn->prepare("SELECT id FROM users WHERE role = 'headmaster' LIMIT 1");
    $stmt->execute();
    $headmaster = $stmt->fetch();
    if ($headmaster) {
        $receiver_id = $headmaster['id'];
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, is_emergency, status, timestamp) 
                                VALUES (?, ?, ?, ?, 'unread', NOW())");
        $stmt->execute([$sender_id, $receiver_id, $message, $is_emergency]);
    } else {
        header("Location: messages.php?error=headmaster_not_found");
        exit();
    }

// ✅ Direct message via hidden receiver_id (used in reply form)
} elseif (!empty($receiver_id)) {
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, is_emergency, status, timestamp) 
                            VALUES (?, ?, ?, ?, 'unread', NOW())");
    $stmt->execute([$sender_id, $receiver_id, $message, $is_emergency]);

} else {
    header("Location: messages.php?error=invalid_receiver");
    exit();
}

// ✅ Redirect after success
if (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher') {
    header("Location: teacher_messages.php?success=sent");
} else {
    header("Location: message.php?success=sent");
}
exit();
