<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'headmaster') {
    header("Location: ../index.php");
    exit();
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Feedback messages
$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

// GROUP inbox messages by sender
$grouped_messages = [];
$stmt = $conn->prepare("SELECT m.id, m.message, m.timestamp, m.is_emergency, m.status, u.username AS sender_name, u.id AS sender_id 
                        FROM messages m 
                        JOIN users u ON m.sender_id = u.id 
                        WHERE m.receiver_id = ? 
                        ORDER BY u.username, m.timestamp DESC");
$stmt->execute([$user_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($messages as $msg) {
    $grouped_messages[$msg['sender_id']]['name'] = $msg['sender_name'];
    $grouped_messages[$msg['sender_id']]['messages'][] = $msg;
}

// Fetch announcements
$stmt2 = $conn->prepare("SELECT id, message, timestamp, is_emergency FROM messages WHERE receiver_group IN ('all', 'all_headmasters', 'all_teachers', 'all_parents') ORDER BY timestamp DESC");
$stmt2->execute();
$announcements = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Fetch teachers (for dropdown)
$teachers = [];
$stmt3 = $conn->prepare("SELECT id, username FROM users WHERE role = 'teacher'");
$stmt3->execute();
$teachers = $stmt3->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Messages</title>
  <link href="https://fonts.googleapis.com/css2?family=Rubik&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Rubik', sans-serif;
      display: flex;
      margin: 0;
      height: 100vh;
      background: #f9fafb;
    }
    .sidebar {
      width: 220px;
      background: #1e40af;
      color: white;
      padding: 20px;
      height: 100vh;
    }
    .sidebar h2 {
      margin-bottom: 25px;
      font-size: 18px;
    }
    .sidebar a {
      display: block;
      color: white;
      text-decoration: none;
      margin: 12px 0;
      padding: 10px;
      border-radius: 5px;
    }
    .sidebar a.active,
    .sidebar a:hover {
      background-color: #3749c1;
    }
    .main {
      flex-grow: 1;
      padding: 30px;
    }
    .topbar {
      display: flex;
      justify-content: space-between;
      margin-bottom: 25px;
    }
    .topbar h1 {
      color: #1e3a8a;
    }
    .logout-btn {
      background-color: #ef4444;
      color: white;
      padding: 8px 16px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    .tab-buttons {
      margin-bottom: 20px;
    }
    .tab-buttons button {
      padding: 10px 20px;
      border: none;
      background: #e2e8f0;
      margin-right: 5px;
      border-radius: 5px;
      cursor: pointer;
    }
    .tab-buttons button.active {
      background: #1e40af;
      color: white;
    }
    .tab { display: none; }
    .tab.active { display: block; }

    .message-box {
      background: white;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 15px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }
    .message-box.emergency {
      border-left: 4px solid red;
      background: #fff5f5;
    }
    .message-box.unread {
      background: #fef9c3;
    }
    textarea, select {
      width: 100%;
      padding: 12px;
      border-radius: 5px;
      margin-top: 8px;
      font-size: 15px;
      border: 1px solid #cbd5e1;
    }
    button[type="submit"] {
      margin-top: 10px;
      background-color: #2563eb;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
    }
    hr {
      margin: 25px 0;
      border: none;
      border-top: 1px solid #e5e7eb;
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2>TMS Headmaster</h2>
    <a href="headmaster.php">Dashboard</a>
    <a href="view_teachers.php">View Teachers</a>
    <a href="message.php" class="active">Messages</a>
    <a href="attendance_records.php">Attendance Records</a>
    <a href="submitted_exams.php">Submitted Exams</a>
    <a href="#">Teaching Notes</a>
    <a href="#">Payroll</a>
    <a href="#">Performance</a>
    <a href="#">Settings</a>
  </div>

  <div class="main">
    <div class="topbar">
      <h1>📨 Messages</h1>
      <form action="../logout.php" method="POST">
        <button class="logout-btn">Logout</button>
      </form>
    </div>

    <div class="tab-buttons">
      <button type="button" onclick="openTab(event, 'inbox')" class="active">Inbox</button>
      <button type="button" onclick="openTab(event, 'announcements')">Announcements</button>
      <button type="button" onclick="openTab(event, 'send')">Send Message</button>
    </div>

    <?php if ($success): ?>
      <div style="color: green; margin-bottom: 10px; font-weight: bold;">Message sent successfully!</div>
    <?php elseif ($error): ?>
      <div style="color: red; margin-bottom: 10px; font-weight: bold;">Error: <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div id="inbox" class="tab active">
      <?php if (empty($grouped_messages)): ?>
        <p>No messages in your inbox.</p>
      <?php else: ?>
        <?php foreach ($grouped_messages as $sender_id => $data): ?>
          <h3><?= htmlspecialchars($data['name']) ?></h3>
          <?php foreach ($data['messages'] as $msg): ?>
            <div class="message-box <?= $msg['is_emergency'] ? 'emergency' : '' ?> <?= $msg['status'] === 'unread' ? 'unread' : '' ?>">
              <p><?= htmlspecialchars($msg['message']) ?></p>
              <small><?= $msg['timestamp'] ?> | <?= $msg['status'] === 'unread' ? 'Unread' : 'Read' ?> <?= $msg['is_emergency'] ? '🚨' : '' ?></small>
              <form method="POST" action="mark_read_message.php" style="display:inline;">
                <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                <?php if ($msg['status'] === 'unread'): ?>
                  <button type="submit" style="margin-left:10px;">Mark as Read</button>
                <?php endif; ?>
              </form>
              <form method="POST" action="delete_message.php" style="display:inline;">
                <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                <button type="submit" style="margin-left:10px; color:red;">Delete</button>
              </form>
            </div>
          <?php endforeach; ?>
          <form method="POST" action="send_message.php">
            <input type="hidden" name="receiver_id" value="<?= $sender_id ?>">
            <input type="hidden" name="mark_read_sender" value="<?= $sender_id ?>">
            <textarea name="message" rows="3" placeholder="Reply to <?= htmlspecialchars($data['name']) ?>" required></textarea>
            <button type="submit">Reply</button>
          </form>
          <hr>
        <?php endforeach; ?>
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
            <small>Posted: <?= $note['timestamp'] ?> <?= $note['is_emergency'] ? '🚨' : '' ?></small>
            <?php if (isset($note['id'])): ?>
              <form method="POST" action="delete_announcement.php" style="display:inline;">
                <input type="hidden" name="message_id" value="<?= $note['id'] ?>">
                <button type="submit" style="margin-left:10px; color:red;">Delete</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
      <hr>
      <h4>Write New Announcement</h4>
      <form method="POST" action="send_message.php">
        <label>Send To:</label>
        <select name="receiver" required>
          <option value="all_teachers">All Teachers</option>
          <option value="all_parents">All Parents</option>
        </select>
        <label>Announcement:</label>
        <textarea name="message" rows="4" required></textarea>
        <label><input type="checkbox" name="is_emergency"> Mark as Emergency 🚨</label><br>
        <button type="submit">Send Announcement</button>
      </form>
    </div>

    <div id="send" class="tab">
      <h3>Send New Message</h3>
      <form method="POST" action="send_message.php">
        <label>Send To:</label>
        <select name="receiver" required>
          <option value="all_teachers">All Teachers</option>
          <option value="all_parents">All Parents</option>
          <optgroup label="Individual Teachers">
            <?php foreach ($teachers as $teacher): ?>
              <option value="teacher:<?= $teacher['id'] ?>">To <?= htmlspecialchars($teacher['username']) ?></option>
            <?php endforeach; ?>
          </optgroup>
        </select>
        <label>Message:</label>
        <textarea name="message" rows="4" required></textarea>
        <label><input type="checkbox" name="is_emergency"> Mark as Emergency 🚨</label><br>
        <button type="submit">Send Message</button>
      </form>
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
