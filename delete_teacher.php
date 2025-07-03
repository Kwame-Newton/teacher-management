<?php
// Start session and check access
session_start();
require_once '../includes/db.php';

// Allow only headmasters
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'headmaster') {
    header("Location: ../index.php");
    exit();
}

// Check if teacher ID is passed
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view_teachers.php");
    exit();
}

$teacherId = $_GET['id'];

// Fetch teacher to check if exists (and get photo to possibly delete)
$stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ? AND role = 'teacher'");
$stmt->execute([$teacherId]);
$teacher = $stmt->fetch();

if (!$teacher) {
    header("Location: view_teachers.php");
    exit();
}

// Optional: Delete profile image if not default
if (!empty($teacher['profile_picture']) && $teacher['profile_picture'] !== 'default.jpg') {
    $imagePath = '../uploads/' . $teacher['profile_picture'];
    if (file_exists($imagePath)) {
        unlink($imagePath); // delete the image from server
    }
}

// Delete teacher record
$deleteStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$deleteStmt->execute([$teacherId]);

// Redirect back to teacher list
header("Location: view_teachers.php");
exit();
?>
