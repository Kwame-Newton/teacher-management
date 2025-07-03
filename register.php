<?php
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Common inputs
    $full_name = $_POST['full_name'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $phone     = $_POST['phone'] ?? '';
    $role      = $_POST['role'] ?? 'teacher'; // 'teacher' or 'headmaster'

    if (empty($full_name) || empty($username) || empty($password)) {
        die("Missing required fields.");
    }

    // Check if username already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $checkStmt->execute([$username]);
    if ($checkStmt->rowCount() > 0) {
        die("Username already exists. Try a different one.");
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Handle profile picture (optional)
    $profile_picture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $target_dir = "../uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir);
        $file_name = uniqid() . "_" . basename($_FILES["profile_picture"]["name"]);
        $target_file = $target_dir . $file_name;
        move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file);
        $profile_picture = $file_name;
    }

    // If role is headmaster, only insert simple fields
    if ($role === 'headmaster') {
        $stmt = $conn->prepare("INSERT INTO users (full_name, username, password, phone, role, profile_picture) VALUES (?, ?, ?, ?, 'headmaster', ?)");
        $stmt->execute([$full_name, $username, $hashedPassword, $phone, $profile_picture]);
    } else {
        // Teacher fields
        $email               = $_POST['email'] ?? null;
        $gender              = $_POST['gender'] ?? null;
        $date_of_birth       = $_POST['date_of_birth'] ?? null;
        $address             = $_POST['address'] ?? null;
        $assigned_class      = $_POST['assigned_class'] ?? null;
        $qualification       = $_POST['qualification'] ?? null;
        $years_of_experience = $_POST['years_of_experience'] ?? null;
        $subjects_taught     = $_POST['subjects_taught'] ?? null;
        $date_employed       = $_POST['date_employed'] ?? null;
        $employment_type     = $_POST['employment_type'] ?? null;

        $stmt = $conn->prepare("INSERT INTO users (
            full_name, username, password, phone, email, gender, date_of_birth, address, assigned_class,
            qualification, years_of_experience, subjects_taught, date_employed, employment_type, role, profile_picture
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'teacher', ?)");
        
        $stmt->execute([
            $full_name, $username, $hashedPassword, $phone, $email, $gender, $date_of_birth, $address,
            $assigned_class, $qualification, $years_of_experience, $subjects_taught, $date_employed,
            $employment_type, $profile_picture
        ]);
    }

    header("Location: ../index.php");
    exit();
} else {
    echo "Invalid request method.";
}
