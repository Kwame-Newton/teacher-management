<?php
session_start();
require '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit();
}
// Fetch unread announcements/messages
$unread_announcements = $conn->query("SELECT COUNT(*) FROM messages WHERE receiver_group IN ('all', 'all_teachers') AND status = 'unread'")->fetchColumn();
// Fetch unread messages
$unread_messages = $conn->query("SELECT COUNT(*) FROM messages WHERE receiver_id = " . intval($_SESSION['user_id']) . " AND status = 'unread'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Teacher Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Rubik&display=swap" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    body {
      font-family: 'Rubik', sans-serif;
      background-color: #f4f6f8;
      display: flex;
      height: 100vh;
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
    }
    .card h3 {
      color: #1e3a8a;
      margin-bottom: 10px;
    }
    .card p {
      font-size: 22px;
      font-weight: bold;
      color: #2563eb;
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2>TMS Teacher</h2>
    <a href="teacher.php" class="active">Dashboard</a>
    <a href="teacher_profile.php">Profile</a>
    <a href="teacher_messages.php">Messages</a>
    <a href="teacher_attendance.php">Attendance</a>
    <a href="submit_exams.php">Submit Exams</a>
    <a href="lesson_notes.php">Lesson Notes</a>
  </div>
  <div class="main">
    <div class="topbar">
      <h1>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></h1>
      <form action="../logout.php" method="POST">
        <button class="logout-btn" type="submit">Logout</button>
      </form>
    </div>
    <div class="dashboard-cards">
      <div class="card" onclick="window.location.href='teacher_messages.php'" style="cursor:pointer;">
        <div style="font-size:2em;"></div>
        <h3>Announcements</h3>
        <p><?= $unread_announcements ?> unread</p>
      </div>
      <div class="card" onclick="window.location.href='teacher_profile.php'" style="cursor:pointer;">
        <div style="font-size:2em;"></div>
        <h3>Profile</h3>
        <p>View & update your info</p>
      </div>
      <div class="card" onclick="window.location.href='teacher_messages.php'" style="cursor:pointer;">
        <div style="font-size:2em;"></div>
        <h3>Unread Messages</h3>
        <p><?= $unread_messages ?></p>
      </div>
      <div class="card">
        <div style="font-size:2em;"></div>
        <h3>Support</h3>
        <p>Contact headmaster for help</p>
      </div>
    </div>
  </div>
</body>
</html>
