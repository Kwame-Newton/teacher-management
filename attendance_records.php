<?php
session_start();
require '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'headmaster') {
    header("Location: ../index.php");
    exit();
}
// Handle leave request actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Approve or deny leave
  if (isset($_POST['leave_id']) && (isset($_POST['approve_leave']) || isset($_POST['deny_leave']))) {
    $leave_id = intval($_POST['leave_id']);
    $msg = trim($_POST['supervisor_message'] ?? '');
    $status = isset($_POST['approve_leave']) ? 'Approved' : 'Denied';
    $stmt = $conn->prepare("UPDATE leave_requests SET status = ?, supervisor_message = ? WHERE id = ?");
    $stmt->execute([$status, $msg, $leave_id]);
    header('Location: attendance_records.php');
    exit();
  }
  // Delete leave
  if (isset($_POST['delete_leave_id'])) {
    $leave_id = intval($_POST['delete_leave_id']);
    $stmt = $conn->prepare("DELETE FROM leave_requests WHERE id = ?");
    $stmt->execute([$leave_id]);
    header('Location: attendance_records.php');
    exit();
  }
  // Mark absents for unchecked teachers
  if (isset($_POST['mark_absents'])) {
    $date = $_POST['mark_absents_date'] ?? date('Y-m-d');
    // Get all teachers
    $teachers = $conn->query("SELECT id FROM users WHERE role = 'teacher'")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($teachers as $t) {
      $tid = $t['id'];
      // Check if attendance exists for this date
      $stmt = $conn->prepare("SELECT id FROM attendance WHERE teacher_id = ? AND date = ?");
      $stmt->execute([$tid, $date]);
      if (!$stmt->fetch()) {
        $stmt2 = $conn->prepare("INSERT INTO attendance (teacher_id, date, status) VALUES (?, ?, 'Absent')");
        $stmt2->execute([$tid, $date]);
      }
    }
    header('Location: attendance_records.php?success=absents_marked');
    exit();
  }
  // Delete attendance record
  if (isset($_POST['delete_attendance_id'])) {
    $attendance_id = intval($_POST['delete_attendance_id']);
    $stmt = $conn->prepare("DELETE FROM attendance WHERE id = ?");
    $stmt->execute([$attendance_id]);
    header('Location: attendance_records.php');
    exit();
  }
}
// Fetch teachers for filter dropdown
$teachers = $conn->query("SELECT id, full_name FROM users WHERE role = 'teacher' ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
// Fetch leave requests for all teachers
$leave_requests = $conn->query("SELECT lr.*, u.full_name FROM leave_requests lr JOIN users u ON lr.teacher_id = u.id ORDER BY lr.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
// Handle filters
$filter_teacher = $_GET['teacher_id'] ?? '';
$filter_date = $_GET['date'] ?? '';
$where = [];
$params = [];
if ($filter_teacher) {
    $where[] = 'a.teacher_id = ?';
    $params[] = $filter_teacher;
}
if ($filter_date) {
    $where[] = 'a.date = ?';
    $params[] = $filter_date;
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = "SELECT a.*, u.full_name FROM attendance a JOIN users u ON a.teacher_id = u.id $where_sql ORDER BY a.date DESC, u.full_name";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance Records</title>
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
    .summary-cards { display: flex; gap: 32px; margin-bottom: 32px; }
    .card { flex: 1; padding: 32px; border-radius: 12px; text-align: center; font-size: 1.2em; background: #fff; box-shadow: 0 2px 8px rgba(30,64,175,0.08);}
    .card.present-card { border-left: 6px solid #10b981; }
    .card.absent-card { border-left: 6px solid #ef4444; }
    .filter-form { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); margin-bottom: 20px; display: flex; gap: 20px; align-items: flex-end; }
    .filter-form label { font-weight: bold; }
    .filter-form select, .filter-form input[type="date"] { padding: 8px; border-radius: 5px; border: 1px solid #ccc; }
    table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); overflow: hidden; }
    th, td { padding: 16px; text-align: left; border-bottom: 1px solid #f0f0f0; }
    th { background: #1e40af; color: white; font-size: 1.1em; }
    tr:last-child td { border-bottom: none; }
    .present { color: #10b981; font-weight: bold; }
    .absent { color: #ef4444; font-weight: bold; }
    .note { color: #374151; font-size: 13px; }
    .leave-section {
      margin-bottom: 32px;
      padding: 48px 48px 40px 48px;
      background: #f9fafb;
      border-radius: 20px;
      box-shadow: 0 6px 24px rgba(30,64,175,0.13);
      max-width: 1200px;
      width: 100%;
      margin-left: auto;
      margin-right: auto;
    }
    .leave-section h2 {
      margin-bottom: 24px;
      color: #1e3a8a;
      font-size: 1.7em;
      text-align: center;
      letter-spacing: 0.5px;
    }
    .leave-section table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 40px;
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 2px 12px rgba(30,64,175,0.10);
      overflow: hidden;
    }
    .leave-section th, .leave-section td {
      padding: 22px;
      text-align: center;
      border-bottom: 1px solid #f0f0f0;
      font-size: 1.12em;
    }
    .leave-section th {
      background: #1e40af;
      color: #fff;
      font-size: 1.18em;
    }
    .leave-section tr:last-child td {
      border-bottom: none;
    }
    .action-btn, .leave-section button, .filter-form button {
      background: #ef4444;
      color: #fff;
      border: none;
      padding: 4px 12px;
      border-radius: 4px;
      font-size: 0.95em;
      margin: 0 4px;
      cursor: pointer;
      font-weight: bold;
      transition: background 0.2s;
    }
    .action-btn:hover, .leave-section button:hover, .filter-form button:hover {
      background: #dc2626;
    }
    .action-btn[disabled], .leave-section button[disabled] {
      background: #ccc;
      cursor: not-allowed;
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2>TMS Headmaster</h2>
    <a href="headmaster.php">Dashboard</a>
    <a href="view_teachers.php">View Teachers</a>
    <a href="message.php">Messages</a>
    <a href="attendance_records.php" class="active">Attendance Records</a>
    <a href="submitted_exams.php">Submitted Exams</a>
    
    <a href="#">Settings</a>
  </div>
  <div class="main">
    <div class="topbar">
      <h1>Attendance Records</h1>
      <form action="../logout.php" method="POST">
        <button class="logout-btn" type="submit">Logout</button>
      </form>
    </div>
    <form class="filter-form" method="GET">
      <div>
        <label for="teacher_id">Teacher:</label><br>
        <select name="teacher_id" id="teacher_id">
          <option value="">All</option>
          <?php foreach ($teachers as $t): ?>
            <option value="<?= $t['id'] ?>" <?= $filter_teacher == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="date">Date:</label><br>
        <input type="date" name="date" id="date" value="<?= htmlspecialchars($filter_date) ?>">
      </div>
      <div>
        <button type="submit">Filter</button>
      </div>
    </form>
    <table>
      <tr>
        <th>Date</th>
        <th>Teacher</th>
        <th>Status</th>
        <th>Note</th>
        <th>Marked At</th>
        <th>Action</th>
      </tr>
      <?php if (empty($records)): ?>
        <tr><td colspan="6">No attendance records found.</td></tr>
      <?php else: ?>
        <?php foreach ($records as $rec): ?>
          <tr>
            <td><?= htmlspecialchars($rec['date']) ?></td>
            <td><?= htmlspecialchars($rec['full_name']) ?></td>
            <td class="<?= strtolower($rec['status']) ?>"><?= htmlspecialchars($rec['status']) ?></td>
            <td class="note"><?= htmlspecialchars($rec['note']) ?></td>
            <td><?= htmlspecialchars($rec['timestamp']) ?></td>
            <td>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="delete_attendance_id" value="<?= $rec['id'] ?>">
                <button type="submit" class="action-btn">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </table>
    <hr style="margin:40px 0;">
    <h2>Leave Requests</h2>
    <table>
      <tr>
        <th>Teacher</th>
        <th>Reason</th>
        <th>Start</th>
        <th>End</th>
        <th>Status</th>
        <th>Supervisor Reply</th>
        <th>Actions</th>
      </tr>
      <?php if (empty($leave_requests)): ?>
        <tr><td colspan="7">No leave requests found.</td></tr>
      <?php else: ?>
        <?php foreach ($leave_requests as $leave): ?>
          <tr>
            <td><?= htmlspecialchars($leave['full_name']) ?></td>
            <td><?= htmlspecialchars($leave['reason']) ?></td>
            <td><?= htmlspecialchars($leave['start_date']) ?></td>
            <td><?= htmlspecialchars($leave['end_date']) ?></td>
            <td><?= htmlspecialchars($leave['status']) ?></td>
            <td><?= htmlspecialchars($leave['supervisor_message']) ?></td>
            <td>
              <?php if ($leave['status'] === 'Pending'): ?>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="leave_id" value="<?= $leave['id'] ?>">
                  <input type="text" name="supervisor_message" placeholder="Reply..." style="width:120px;">
                  <button type="submit" name="approve_leave">Approve</button>
                  <button type="submit" name="deny_leave">Deny</button>
                </form>
              <?php endif; ?>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="delete_leave_id" value="<?= $leave['id'] ?>">
                <button type="submit" style="color:red;">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </table>
    <hr style="margin:40px 0;">
    <form method="POST" style="margin-bottom:40px;">
      <input type="hidden" name="mark_absents_date" value="<?= htmlspecialchars($filter_date ?: date('Y-m-d')) ?>">
      <button type="submit" name="mark_absents" style="background:#ef4444;color:#fff;padding:12px 28px;border:none;border-radius:6px;font-weight:bold;cursor:pointer;">Mark Absents for Unchecked Teachers (<?= htmlspecialchars($filter_date ?: date('Y-m-d')) ?>)</button>
    </form>
  </div>
</body>
</html> 