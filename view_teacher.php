<?php
// Start session and check for headmaster access
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'headmaster') {
    header("Location: ../index.php");
    exit();
}

// Validate teacher ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Invalid teacher ID.";
    exit();
}

// Fetch teacher by ID
$teacher_id = $_GET['id'];
$stmt = $conn->prepare("SELECT full_name, username, phone, assigned_class, profile_picture FROM users WHERE id = ? AND role = 'teacher'");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch();

if (!$teacher) {
    echo "Teacher not found.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Teacher Profile</title>
  <link href="https://fonts.googleapis.com/css2?family=Rubik&display=swap" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    body {
      font-family: 'Rubik', sans-serif;
      display: flex;
      height: 100vh;
      background-color: #f4f6f8;
    }
    .sidebar {
      width: 220px;
      background-color: #1e40af;
      color: white;
      padding: 20px;
      position: fixed;
      left: 0;
      top: 0;
      height: 100vh;
      z-index: 1000;
    }
    .sidebar h2 {
      font-size: 20px;
      margin-bottom: 30px;
      color: white;
      font-family: 'Rubik', sans-serif;
      font-weight: normal;
    }
    .sidebar a {
      display: block;
      color: white;
      text-decoration: none;
      margin: 15px 0;
      padding: 10px;
      border-radius: 5px;
    }
    .sidebar a:hover, .sidebar a.active {
      background-color: #3749c1;
      color: white;
    }
    .main {
      flex: 1;
      padding: 20px;
      margin-left: 220px;
      height: 100vh;
      background-color: #f4f6f8;
    }
    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
    }
    .topbar h1 {
      font-size: 24px;
      color: #1e3a8a;
      font-family: 'Rubik', sans-serif;
      font-weight: normal;
    }
    .logout-btn {
      background-color: #ef4444;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 5px;
      cursor: pointer;
    }
    .logout-btn:hover {
      background-color: #dc2626;
    }
    .card {
      background-color: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      text-align: center;
      font-family: 'Rubik', sans-serif;
    }
    .profile-image {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      object-fit: cover;
      margin-bottom: 20px;
      border: 2px solid #1e40af;
    }
    h2 {
      color: #1e3a8a;
      margin-bottom: 20px;
      font-family: 'Rubik', sans-serif;
      font-weight: normal;
    }
    p {
      font-size: 16px;
      margin-bottom: 10px;
      font-family: 'Rubik', sans-serif;
      font-weight: normal;
    }
    .label {
      font-weight: bold;
      color: #374151;
      font-family: 'Rubik', sans-serif;
    }
    .back-link {
      display: inline-block;
      margin-top: 20px;
      text-decoration: none;
      color: #2563eb;
      font-weight: normal;
      font-family: 'Rubik', sans-serif;
    }
    .back-link:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <!-- Sidebar -->

  <div class="sidebar">
    <h2>TMS Headmaster</h2>
    <a href="headmaster.php">Dashboard</a>
    <a href="view_teachers.php">View Teachers</a>
    <a href="register_teacher.php">Message</a>
    <a href="attendance_records.php">Attendance Records</a>
    <a href="#">Submitted Exams</a>
    <a href="#">Teaching Notes</a>
    <a href="#">Payroll</a>
    <a href="#">Performance</a>
    <a href="#">Settings</a>
  </div>

  <!-- Main Content -->
  <div class="main">
    <div class="topbar">
      <h1>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></h1>
      <form action="../auth/logout.php" method="POST">
        <button class="logout-btn" type="submit">Logout</button>
      </form>
    </div>

    <div class="card">
      <img src="../uploads/<?= htmlspecialchars($teacher['profile_picture']) ?>" alt="Teacher Photo" class="profile-image">
      <h2>Teacher Profile</h2>
      <p><span class="label">Full Name:</span> <?= htmlspecialchars($teacher['full_name']) ?></p>
      <p><span class="label">Username:</span> <?= htmlspecialchars($teacher['username']) ?></p>
      <p><span class="label">Phone:</span> <?= htmlspecialchars($teacher['phone']) ?></p>
      <p><span class="label">Assigned Class:</span> <?= htmlspecialchars($teacher['assigned_class']) ?></p>
      <a href="view_teachers.php" class="back-link">← Back to Teacher List</a>
    </div>
  </div>

</body>
</html>
