<?php
session_start();
require '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'headmaster') {
    header("Location: ../index.php");
    exit();
}
// Fetch total teachers
$total_teachers = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn();
// Fetch unread messages
$unread_messages = $conn->query("SELECT COUNT(*) FROM messages WHERE receiver_id = " . intval($_SESSION['user_id']) . " AND status = 'unread'")->fetchColumn();
// Fetch lesson notes submitted (dummy value for now)
$lesson_notes = 18; // Replace with real query if available
// Fetch alerts (dummy value for now)
$alerts = 3; // Replace with real query if available
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Headmaster Dashboard</title>
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
      overflow-y: auto;
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
      overflow-y: auto;
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
    .dashboard-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
    }
    .card {
      background-color: white;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.08);
      text-align: center;
      font-family: 'Rubik', sans-serif;
    }
    .card h3 {
      color: #1e3a8a;
      margin-bottom: 10px;
      font-family: 'Rubik', sans-serif;
      font-weight: normal;
    }
    .card p {
      font-size: 22px;
      font-weight: normal;
      color: #2563eb;
      font-family: 'Rubik', sans-serif;
    }
    .content-placeholder {
      background-color: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 8px rgba(0,0,0,0.1);
      font-family: 'Rubik', sans-serif;
    }
  </style>
</head>
<body>

  <div class="sidebar">
    <h2>TMS Headmaster</h2>
    <a href="headmaster.php">Dashboard</a>
    <a href="view_teachers.php">View Teachers</a>
    <a href="message.php">Messages</a>
    <a href="attendance_records.php">Attendance Records</a>
    <a href="submitted_exams.php">Submitted Exams</a>
    <a href="#">Settings</a>
  </div>

  <div class="main">
    <div class="topbar">
      <h1>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></h1>
      <form action="../logout.php" method="POST">
        <button class="logout-btn" type="submit">Logout</button>
      </form>
    </div>
<!-- Dashboard Overview Cards -->
<div class="dashboard-cards">
  <div class="card" onclick="window.location.href='view_teachers.php'" style="cursor:pointer;">
    <div style="font-size:2em;"></div>
    <h3>Total Teachers</h3>
    <p><?= $total_teachers ?></p>
  </div>
  <div class="card">
    <div style="font-size:2em;"></div>
    <h3>Lesson Notes Submitted</h3>
    <p><?= $lesson_notes ?> this week</p>
  </div>
  <div class="card">
    <div style="font-size:2em;"></div>
    <h3>Alerts</h3>
    <p><?= $alerts ?> missing reports</p>
  </div>
  <div class="card" onclick="window.location.href='message.php'" style="cursor:pointer;">
    <div style="font-size:2em;"></div>
    <h3>Unread Messages</h3>
    <p><?= $unread_messages ?></p>
  </div>
</div>

</body>
</html>
