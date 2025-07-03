<?php
session_start();
require '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit();
}
$teacher_id = $_SESSION['user_id'];
// Fetch messages from headmaster
$stmt = $conn->prepare("SELECT m.id, m.message, m.timestamp, m.is_emergency, m.status, u.username AS sender_name, u.id AS sender_id FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.receiver_id = ? AND u.role = 'headmaster' ORDER BY m.timestamp DESC");
$stmt->execute([$teacher_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Fetch announcements
$stmt2 = $conn->prepare("SELECT id, message, timestamp, is_emergency FROM messages WHERE receiver_group IN ('all', 'all_teachers') ORDER BY timestamp DESC");
$stmt2->execute();
$announcements = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Teacher Messages</title>
  <link href="https://fonts.googleapis.com/css2?family=Rubik&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Rubik', sans-serif; display: flex; height: 100vh; background-color: #f4f6f8; }
    .sidebar { width: 220px; background-color: #1e40af; color: white; padding: 20px; }
    .sidebar h2 { font-size: 20px; margin-bottom: 30px; }
    .sidebar a { display: block; color: white; text-decoration: none; margin: 15px 0; padding: 10px; border-radius: 5px; }
    .sidebar a.active, .sidebar a:hover { background-color: #3749c1; }
    .main { flex: 1; padding: 20px; }
    .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
    .topbar h1 { font-size: 24px; color: #1e3a8a; }
    .logout-btn { background-color: #ef4444; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer; }
    .logout-btn:hover { background-color: #dc2626; }
    .tab-buttons { margin-bottom: 20px; }
    .tab-buttons button { padding: 10px 20px; border: none; background: #e2e8f0; margin-right: 5px; border-radius: 5px; cursor: pointer; }
    .tab-buttons button.active { background: #1e40af; color: white; }
    .tab { display: none; }
    .tab.active { display: block; }
    .message-box { background: white; border-radius: 8px; padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
    .message-box.emergency { border-left: 4px solid #ff0000; background: #fff5f5; }
    .message-box.unread { background: #fef9c3; }
    textarea { width: 100%; padding: 12px; border-radius: 5px; margin-top: 8px; font-size: 15px; border: 1px solid #cbd5e1; }
    button[type="submit"] { margin-top: 10px; background-color: #2563eb; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
    hr { margin: 25px 0; border: none; border-top: 1px solid #e5e7eb; }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2>TMS Teacher</h2>
    <a href="teacher.php">Dashboard</a>
    <a href="teacher_profile.php">Profile</a>
    <a href="teacher_messages.php" class="active">Messages</a>
    <a href="teacher_attendance.php">Attendance</a>
    <a href="submit_exams.php">Submit Exams</a>
    <a href="lesson_notes.php">Lesson Notes</a>
  </div>
  <div class="main">
    <div class="topbar">
      <h1>Messages</h1>
      <form action="../logout.php" method="POST">
        <button class="logout-btn">Logout</button>
      </form>
    </div>
    <div class="tab-buttons">
      <button type="button" onclick="openTab(event, 'inbox')" class="active">Inbox</button>
      <button type="button" onclick="openTab(event, 'announcements')">Announcements</button>
    </div>
    <div id="inbox" class="tab active">
      <?php if (empty($messages)): ?>
        <p>No messages from the headmaster.</p>
      <?php else: ?>
        <?php foreach ($messages as $msg): ?>
          <div class="message-box <?= $msg['is_emergency'] ? 'emergency' : '' ?> <?= $msg['status'] === 'unread' ? 'unread' : '' ?>">
            <p><?= htmlspecialchars($msg['message']) ?></p>
            <small><?= $msg['timestamp'] ?> | <?= $msg['status'] === 'unread' ? 'Unread' : 'Read' ?></small>
            <form method="POST" action="delete_message.php" style="display:inline;">
              <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
              <button type="submit" style="margin-left:10px; color:red;">Delete</button>
            </form>
          </div>
        <?php endforeach; ?>
        <hr>
        <h4>Reply to Headmaster</h4>
        <form method="POST" action="send_message.php">
          <input type="hidden" name="receiver" value="headmaster">
          <textarea name="message" rows="3" placeholder="Type your reply to the headmaster..." required></textarea>
          <button type="submit">Send Reply</button>
        </form>
      <?php endif; ?>
    </div>
    <div id="announcements" class="tab">
      <h3>School Announcements</h3>
      <?php if (empty($announcements)): ?>
        <p>No announcements yet.</p>
      <?php else: ?>
        <?php foreach ($announcements as $note): ?>
          <div class="message-box <?= $note['is_emergency'] ? 'emergency' : '' ?>">
            <?= htmlspecialchars($note['message']) ?><br>
            <small>Posted: <?= $note['timestamp'] ?></small>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
  <script>
    function openTab(event, tabId) {
      document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
      document.getElementById(tabId).classList.add('active');
      document.querySelectorAll('.tab-buttons button').forEach(btn => btn.classList.remove('active'));
      event.target.classList.add('active');
    }
  </script>
</body>
</html> 