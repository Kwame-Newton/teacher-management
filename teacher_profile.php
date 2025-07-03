<?php
session_start();
require '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit();
}
$teacher_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'teacher'");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch();
if (!$teacher) { echo "Profile not found."; exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Teacher Profile</title>
  <link href="https://fonts.googleapis.com/css2?family=Rubik&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Rubik', sans-serif; display: flex; height: 100vh; background-color: #f4f6f8; }
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
    .sidebar h2 { font-size: 20px; margin-bottom: 30px; }
    .sidebar a { display: block; color: white; text-decoration: none; margin: 15px 0; padding: 10px; border-radius: 5px; }
    .sidebar a.active, .sidebar a:hover { background-color: #3749c1; }
    .main {
      flex: 1;
      padding: 20px;
      margin-left: 220px;
      height: 100vh;
      overflow-y: auto;
    }
    .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
    .topbar h1 { font-size: 24px; color: #1e3a8a; }
    .logout-btn { background-color: #ef4444; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer; }
    .logout-btn:hover { background-color: #dc2626; }
    .profile-card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); max-width: 600px; margin: 0 auto; }
    .profile-image { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 20px; }
    h2 { color: #1e3a8a; margin-bottom: 20px; }
    p { font-size: 16px; margin-bottom: 10px; }
    .label { font-weight: bold; color: #374151; }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2>TMS Teacher</h2>
    <a href="teacher.php">Dashboard</a>
    <a href="teacher_profile.php" class="active">Profile</a>
    <a href="teacher_messages.php">Messages</a>
    <a href="teacher_attendance.php">Attendance</a>
    <a href="submit_exams.php">submit exams</a>
    <a href="lesson_notes.php">Lesson Notes</a>
  </div>
  <div class="main">
    <div class="topbar">
      <h1>My Profile</h1>
      <form action="../logout.php" method="POST">
        <button class="logout-btn" type="submit">Logout</button>
      </form>
    </div>
    <div class="profile-card">
      <img src="../uploads/<?= htmlspecialchars($teacher['profile_picture']) ?>" alt="Profile Photo" class="profile-image">
      <h2><?= htmlspecialchars($teacher['full_name']) ?></h2>
      <p><span class="label">Username:</span> <?= htmlspecialchars($teacher['username']) ?></p>
      <p><span class="label">Phone:</span> <?= htmlspecialchars($teacher['phone']) ?></p>
      <p><span class="label">Email:</span> <?= htmlspecialchars($teacher['email']) ?></p>
      <p><span class="label">Gender:</span> <?= htmlspecialchars($teacher['gender']) ?></p>
      <p><span class="label">Date of Birth:</span> <?= htmlspecialchars($teacher['date_of_birth']) ?></p>
      <p><span class="label">Address:</span> <?= htmlspecialchars($teacher['address']) ?></p>
      <p><span class="label">Assigned Class:</span> <?= htmlspecialchars($teacher['assigned_class']) ?></p>
      <p><span class="label">Qualification:</span> <?= htmlspecialchars($teacher['qualification']) ?></p>
      <p><span class="label">Years of Experience:</span> <?= htmlspecialchars($teacher['years_of_experience']) ?></p>
      <p><span class="label">Subjects Taught:</span> <?= htmlspecialchars($teacher['subjects_taught']) ?></p>
    </div>
  </div>
</body>
</html> 