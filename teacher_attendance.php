<?php
session_start();
require '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit();
}
$teacher_id = $_SESSION['user_id'];
$date = date('Y-m-d');
$now = date('H:i:s');

// Fetch summary
$present_count = $conn->query("SELECT COUNT(*) FROM attendance WHERE teacher_id = $teacher_id AND status = 'Present'")->fetchColumn();
$absent_count = $conn->query("SELECT COUNT(*) FROM attendance WHERE teacher_id = $teacher_id AND status = 'Absent'")->fetchColumn();

// Fetch today's attendance
$stmt = $conn->prepare("SELECT * FROM attendance WHERE teacher_id = ? AND date = ?");
$stmt->execute([$teacher_id, $date]);
$today_attendance = $stmt->fetch();

// Check-in/out logic (fix warnings)
$can_check_in = !$today_attendance;
$can_check_out = $today_attendance
    && (!isset($today_attendance['check_out']) || !$today_attendance['check_out'])
    && (isset($today_attendance['check_in']) && $today_attendance['check_in']);

// Handle check-in
if (isset($_POST['check_in']) && $can_check_in) {
    $stmt = $conn->prepare("INSERT INTO attendance (teacher_id, date, check_in, status) VALUES (?, ?, ?, 'Present')");
    $stmt->execute([$teacher_id, $date, $now]);
    header("Location: teacher_attendance.php");
    exit();
}
// Handle check-out
if (isset($_POST['check_out']) && $can_check_out) {
    $stmt = $conn->prepare("UPDATE attendance SET check_out = ? WHERE id = ?");
    $stmt->execute([$now, $today_attendance['id']]);
    header("Location: teacher_attendance.php");
    exit();
}
// Handle delete attendance (not today)
if (isset($_POST['delete_attendance'])) {
    $del_id = intval($_POST['delete_attendance']);
    $stmt = $conn->prepare("DELETE FROM attendance WHERE id = ? AND teacher_id = ? AND date <> ?");
    $stmt->execute([$del_id, $teacher_id, $date]);
    header("Location: teacher_attendance.php");
    exit();
}
// Fetch full attendance history
$stmt = $conn->prepare("SELECT * FROM attendance WHERE teacher_id = ? ORDER BY date DESC");
$stmt->execute([$teacher_id]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle leave request
if (isset($_POST['leave_reason'], $_POST['leave_start'], $_POST['leave_end'])) {
    $reason = trim($_POST['leave_reason']);
    $start = $_POST['leave_start'];
    $end = $_POST['leave_end'];
    if ($reason && $start && $end) {
        $stmt = $conn->prepare("INSERT INTO leave_requests (teacher_id, reason, start_date, end_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$teacher_id, $reason, $start, $end]);
        header("Location: teacher_attendance.php");
        exit();
    }
}
// Handle delete leave request (not pending/current)
if (isset($_POST['delete_leave'])) {
    $del_id = intval($_POST['delete_leave']);
    $stmt = $conn->prepare("DELETE FROM leave_requests WHERE id = ? AND teacher_id = ? AND status <> 'Pending'");
    $stmt->execute([$del_id, $teacher_id]);
    header("Location: teacher_attendance.php");
    exit();
}
// Fetch leave requests
$stmt = $conn->prepare("SELECT * FROM leave_requests WHERE teacher_id = ? ORDER BY created_at DESC");
$stmt->execute([$teacher_id]);
$leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Teacher Attendance</title>
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
    table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); overflow: hidden; }
    th, td { padding: 16px; text-align: left; border-bottom: 1px solid #f0f0f0; }
    th { background: #1e40af; color: white; font-size: 1.1em; }
    tr:last-child td { border-bottom: none; }
    .status-present, .present { color: #10b981; font-weight: bold; }
    .status-absent, .absent { color: #ef4444; font-weight: bold; }
    .note { color: #374151; font-size: 13px; }
    .actions { margin-bottom: 32px; }
    .actions form { display: inline; }
    .actions button { background: #22c55e; color: #fff; border: none; padding: 10px 24px; border-radius: 6px; font-size: 1em; margin: 0 8px; cursor: pointer; font-weight: bold; }
    .actions button:disabled { background: #ccc; cursor: not-allowed; }
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
    .tab-buttons {
      display: flex;
      gap: 12px;
      justify-content: flex-start;
      margin-bottom: 24px;
    }
    .tab-btn {
      background: #e2e8f0;
      color: #1e40af;
      border: none;
      padding: 8px 24px;
      border-radius: 6px;
      font-weight: bold;
      font-size: 1em;
      cursor: pointer;
      transition: background 0.2s, color 0.2s;
      box-shadow: 0 1px 4px rgba(30,64,175,0.06);
      margin-bottom: 0;
    }
    .tab-btn.active, .tab-btn:hover {
      background: #1e40af;
      color: #fff;
    }
    .leave-form .form-group {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .leave-form .form-row {
      display: flex;
      gap: 32px;
    }
    .leave-form label {
      font-weight: 500;
      color: #1e3a8a;
      margin-bottom: 2px;
      font-size: 1.15em;
    }
    .leave-form textarea, .leave-form input[type="date"] {
      width: 100%;
      padding: 20px;
      border-radius: 10px;
      border: 1.5px solid #cbd5e1;
      font-size: 1.15em;
      background: #fff;
      transition: border 0.2s;
    }
    .leave-form textarea:focus, .leave-form input[type="date"]:focus {
      border: 1.5px solid #2563eb;
      outline: none;
    }
    .leave-btn {
      background: linear-gradient(90deg, #2563eb 0%, #1e40af 100%);
      color: #fff;
      border: none;
      padding: 16px 0;
      border-radius: 10px;
      font-weight: bold;
      font-size: 1.1em;
      cursor: pointer;
      box-shadow: 0 2px 12px rgba(30,64,175,0.13);
      transition: background 0.2s;
      margin-top: 18px;
    }
    .leave-btn:hover {
      background: linear-gradient(90deg, #1e40af 0%, #2563eb 100%);
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
    .action-btn, .leave-section button {
      background: #2563eb;
      color: #fff;
      border: none;
      padding: 10px 24px;
      border-radius: 6px;
      font-size: 1em;
      margin: 0 8px;
      cursor: pointer;
      font-weight: bold;
      transition: background 0.2s;
    }
    .action-btn:hover, .leave-section button:hover {
      background: #1e40af;
    }
    .action-btn[disabled], .leave-section button[disabled] {
      background: #ccc;
      cursor: not-allowed;
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2>TMS Teacher</h2>
    <a href="teacher.php">Dashboard</a>
    <a href="teacher_profile.php">Profile</a>
    <a href="teacher_messages.php">Messages</a>
    <a href="teacher_attendance.php" class="active">Attendance</a>
    <a href="submit_exams.php">Submit Exams</a>
    <a href="lesson_notes.php">Lesson Notes</a>
  </div>
  <div class="main">
    <div class="topbar">
      <h1>Attendance</h1>
      <form action="../logout.php" method="POST">
        <button class="logout-btn" type="submit">Logout</button>
      </form>
    </div>
    <div class="tab-buttons" style="display:flex; gap:12px; margin-bottom:24px;">
      <button type="button" class="tab-btn active" onclick="openTab(event, 'attendance-tab')">Attendance</button>
      <button type="button" class="tab-btn" onclick="openTab(event, 'leave-tab')">Leave</button>
    </div>
    <div id="attendance-tab" class="tab-content active">
      <div class="summary-cards">
        <div class="card present-card">
          <div>Days Present</div>
          <div style="font-size:2em; font-weight:bold;"><?= $present_count ?></div>
        </div>
        <div class="card absent-card">
          <div>Days Absent</div>
          <div style="font-size:2em; font-weight:bold;"><?= $absent_count ?></div>
        </div>
      </div>
      <div class="actions">
        <form method="POST" style="display:inline;">
          <button type="submit" name="check_in" <?= !$can_check_in ? 'disabled' : '' ?>>Check In</button>
        </form>
        <form method="POST" style="display:inline;">
          <button type="submit" name="check_out" <?= !$can_check_out ? 'disabled' : '' ?>>Check Out</button>
        </form>
      </div>
      <h2>Full Attendance History</h2>
      <table>
        <tr>
          <th>Date</th>
          <th>Check-In</th>
          <th>Check-Out</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
        <?php foreach ($history as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['date']) ?></td>
            <td><?= isset($row['check_in']) && $row['check_in'] ? htmlspecialchars($row['check_in']) : '' ?></td>
            <td><?= isset($row['check_out']) && $row['check_out'] ? htmlspecialchars($row['check_out']) : '' ?></td>
            <td class="status-<?= strtolower($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></td>
            <td>
              <form method="POST" style="display:inline;">
                <button type="submit" name="delete_attendance" value="<?= $row['id'] ?>" class="delete-btn">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
    <div id="leave-tab" class="tab-content" style="display:none;">
      <div class="leave-section">
        <h2>Request for Leave</h2>
        <form method="POST" class="leave-form">
          <div class="form-group">
            <label for="leave_reason">Reason for Leave</label>
            <textarea id="leave_reason" name="leave_reason" placeholder="Describe your reason..." required rows="3" maxlength="255"></textarea>
            <small style="color:#6b7280;">Max 255 characters</small>
          </div>
          <div class="form-row" style="display:flex; gap:16px;">
            <div class="form-group" style="flex:1;">
              <label for="leave_start">Start Date</label>
              <input id="leave_start" type="date" name="leave_start" required>
            </div>
            <div class="form-group" style="flex:1;">
              <label for="leave_end">End Date</label>
              <input id="leave_end" type="date" name="leave_end" required>
            </div>
          </div>
          <button type="submit" class="leave-btn">Submit Leave Request</button>
        </form>
        <h2 style="margin-top:32px;">My Leave Requests</h2>
        <table>
          <tr>
            <th>Reason</th>
            <th>Start</th>
            <th>End</th>
            <th>Status</th>
            <th>Supervisor Message</th>
            <th>Action</th>
          </tr>
          <?php if (empty($leaves)): ?>
            <tr><td colspan="6">No leave requests found.</td></tr>
          <?php else: ?>
            <?php foreach ($leaves as $leave): ?>
              <tr>
                <td><?= htmlspecialchars($leave['reason']) ?></td>
                <td><?= htmlspecialchars($leave['start_date']) ?></td>
                <td><?= htmlspecialchars($leave['end_date']) ?></td>
                <td><?= htmlspecialchars($leave['status']) ?></td>
                <td><?= htmlspecialchars($leave['supervisor_message']) ?></td>
                <td>
                  <form method="POST" style="display:inline;">
                    <button type="submit" name="delete_leave" value="<?= $leave['id'] ?>" class="delete-btn" <?= $leave['status'] == 'Pending' ? 'disabled' : '' ?>>Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </table>
      </div>
    </div>
    <script>
      function openTab(event, tabId) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = 'none');
        document.getElementById(tabId).style.display = 'block';
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
      }
    </script>
  </div>
</body>
</html> 