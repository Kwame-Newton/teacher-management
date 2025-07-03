<?php
session_start();
require '../includes/db.php'; // This defines $conn

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'headmaster') {
    header("Location: ../index.php");
    exit();
}

// Search logic
$search = $_GET['search'] ?? '';
$searchTerm = '%' . $search . '%';

$stmt = $conn->prepare("SELECT id, full_name, username, phone, email, assigned_class, qualification, subjects_taught, employment_type, profile_picture 
                        FROM users 
                        WHERE role = 'teacher' AND full_name LIKE ?");
$stmt->execute([$searchTerm]);
$teachers = $stmt->fetchAll();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Teachers</title>
  <link href="https://fonts.googleapis.com/css2?family=Rubik&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Rubik', sans-serif;
      margin: 0;
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

    .actions a {
      margin-right: 10px;
      text-decoration: none;
      font-weight: normal;
    }
    .actions a.view { color: #2563eb; }
    .actions a.edit { color: #10b981; }
    .actions a.delete { color: #ef4444; }

    .search-bar {
      margin-top: 20px;
      display: flex;
      justify-content: space-between;
      gap: 10px;
    }
    .search-bar input[type="text"] {
      padding: 10px;
      flex: 1;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-family: 'Rubik', sans-serif;
    }
    .add-btn {
      padding: 10px 15px;
      background-color: #1e40af;
      color: white;
      border-radius: 5px;
      text-decoration: none;
      font-weight: normal;
      font-family: 'Rubik', sans-serif;
    }
    .add-btn:hover {
      background-color: #3749c1;
      color: white;
    }
    .teacher-list {
      margin-top: 30px;
    }
    .teacher {
      background: white;
      padding: 20px;
      margin-bottom: 15px;
      border-radius: 10px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.08);
      display: flex;
      gap: 20px;
      align-items: center;
      font-family: 'Rubik', sans-serif;
    }
    .teacher img {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #1e40af;
    }
    .info {
      flex: 1;
    }
    .info h3 {
      margin: 0;
      color: #1e3a8a;
      font-family: 'Rubik', sans-serif;
      font-weight: normal;
    }
    .info p {
      margin: 3px 0;
      font-size: 14px;
      font-family: 'Rubik', sans-serif;
      font-weight: normal;
    }
  </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <h2>TMS Headmaster</h2>
  <a href="headmaster.php">Dashboard</a>
  <a href="view_teachers.php" class="active">View Teachers</a>
  <a href="message.php">Messages</a>
  <a href="attendance_records.php">Attendance Records</a>
  <a href="submitted_exams.php" class="<?= basename($_SERVER['PHP_SELF'])==='submitted_exams.php'?'active':'' ?>">Submitted Exams</a>
  <a href="#">Settings</a>
</div>

<!-- Main -->
<div class="main">
  <div class="topbar">
    <h1>View Teachers</h1>
    <form action="../logout.php" method="POST">
      <button class="logout-btn" type="submit">Logout</button>
    </form>
  </div>

  <!-- Search and Add -->
  <div class="search-bar">
    <form method="GET" action="view_teachers.php" style="flex: 1; display: flex; gap: 10px;">
      <input type="text" name="search" placeholder="Search by teacher name" value="<?= htmlspecialchars($search) ?>">
      <button type="submit" class="add-btn" style="background: #10b981;">Search</button>
    </form>
    <a class="add-btn" href="add_teacher.php">+ Add Teacher</a>
  </div>

  <!-- Teacher List -->
  <div class="teacher-list">
    <?php if (count($teachers) === 0): ?>
      <p>No teachers found.</p>
    <?php else: ?>
      <?php foreach ($teachers as $teacher): ?>
        <div class="teacher">
          <img src="../uploads/<?= htmlspecialchars($teacher['profile_picture'] ?? 'default.jpg') ?>" alt="Profile Picture">
          <div class="info">
            <h3><?= htmlspecialchars($teacher['full_name']) ?></h3>
            <p><strong>Username:</strong> <?= htmlspecialchars($teacher['username']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($teacher['email']) ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($teacher['phone']) ?></p>
            <p><strong>Class:</strong> <?= htmlspecialchars($teacher['assigned_class']) ?></p>
            <p><strong>Qualification:</strong> <?= htmlspecialchars($teacher['qualification']) ?></p>
            <p><strong>Subjects:</strong> <?= htmlspecialchars($teacher['subjects_taught']) ?></p>
            <p><strong>Type:</strong> <?= htmlspecialchars($teacher['employment_type']) ?></p>
            <div class="actions">
              <a href="view_teacher.php?id=<?= $teacher['id'] ?>" class="view">View</a>
              <a href="edit_teacher.php?id=<?= $teacher['id'] ?>" class="edit">Edit</a>
              <a href="delete_teacher.php?id=<?= $teacher['id'] ?>" class="delete" onclick="return confirm('Are you sure?')">Delete</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
