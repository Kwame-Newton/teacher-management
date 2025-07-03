<?php
session_start();
require '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit();
}
$teacher_id = $_SESSION['user_id'];
// Handle leave request
if (isset($_POST['leave_reason'], $_POST['leave_start'], $_POST['leave_end'])) {
    $reason = trim($_POST['leave_reason']);
    $start = $_POST['leave_start'];
    $end = $_POST['leave_end'];
    if ($reason && $start && $end) {
        $stmt = $conn->prepare("INSERT INTO leave_requests (teacher_id, reason, start_date, end_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$teacher_id, $reason, $start, $end]);
        header("Location: teacher_leave.php");
        exit();
    }
}
// Handle delete leave request (not pending/current)
if (isset($_POST['delete_leave'])) {
    $del_id = intval($_POST['delete_leave']);
    $stmt = $conn->prepare("DELETE FROM leave_requests WHERE id = ? AND teacher_id = ? AND status <> 'Pending'");
    $stmt->execute([$del_id, $teacher_id]);
    header("Location: teacher_leave.php");
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
  <title>Leave Requests</title>
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
    .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
    .topbar h1 { font-size: 24px; color: #1e3a8a; }
    .logout-btn { background-color: #ef4444; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer; }
    .logout-btn:hover { background-color: #dc2626; }
    .leave-section {
      margin: 0 auto 24px auto;
      padding: 20px 12px 16px 12px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(30,64,175,0.08);
      max-width: 600px;
    }
    .tab-buttons { gap: 10px; margin-bottom: 16px; }
    .tab-btn { padding: 8px 18px; border-radius: 6px; font-size: 0.98em; }
    .leave-tab { display: none; }
    .leave-tab.active { display: block; }
    .leave-form .form-group { gap: 5px; margin-bottom: 10px; }
    .leave-form .form-row { gap: 12px; }
    .leave-form label { font-size: 1em; }
    .leave-form textarea, .leave-form input[type="date"] {
      padding: 8px;
      border-radius: 6px;
      font-size: 1em;
    }
    .leave-btn {
      padding: 10px 0;
      border-radius: 6px;
      font-size: 1em;
      margin-top: 4px;
    }
    .leave-list-section {
      margin-top: 18px;
      border-radius: 10px;
      box-shadow: 0 1px 4px rgba(30,64,175,0.05);
      padding: 14px 4px 10px 4px;
    }
    .leave-list-section h2 {
      font-size: 1em;
      margin-bottom: 10px;
    }
    table { border-radius: 6px; }
    th, td { padding: 7px; font-size: 0.98em; }
    th { font-size: 1em; }
    .delete-btn { padding: 4px 8px; font-size: 0.9em; border-radius: 3px; }
    @media (max-width: 900px) {
      .leave-section, .leave-list-section { max-width: 98vw; padding: 18px 4vw; }
      .main { padding: 8px; }
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2>TMS Teacher</h2>
    <a href="teacher.php">Dashboard</a>
    <a href="teacher_profile.php">Profile</a>
    <a href="teacher_messages.php">Messages</a>
    <a href="teacher_attendance.php">Attendance</a>
    <a href="teacher_leave.php" class="active">Leave</a>
  </div>
  <div class="main">
    <div class="topbar">
      <h1>Leave Management</h1>
      <form action="../logout.php" method="POST">
        <button class="logout-btn" type="submit">Logout</button>
      </form>
    </div>
    <div class="leave-section">
      <div class="tab-buttons">
        <button type="button" class="tab-btn active" onclick="openLeaveTab(event, 'request-leave')">Request Leave</button>
        <button type="button" class="tab-btn" onclick="openLeaveTab(event, 'my-leaves')">My Leave Requests</button>
      </div>
      <div id="request-leave" class="leave-tab active">
        <form method="POST" class="leave-form">
          <div class="form-group">
            <label for="leave_reason">Reason for Leave</label>
            <textarea id="leave_reason" name="leave_reason" placeholder="Describe your reason..." required rows="3" maxlength="255"></textarea>
            <small style="color:#6b7280;">Max 255 characters</small>
          </div>
          <div class="form-row">
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
      </div>
      <div id="my-leaves" class="leave-tab">
        <div class="leave-list-section">
          <h2>My Leave Requests</h2>
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
    </div>
  </div>
  <script>
    function openLeaveTab(event, tabId) {
      document.querySelectorAll('.leave-tab').forEach(tab => tab.classList.remove('active'));
      document.getElementById(tabId).classList.add('active');
      document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
      event.target.classList.add('active');
    }
  </script>
</body>
</html> 