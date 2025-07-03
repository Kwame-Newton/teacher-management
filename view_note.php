<?php
session_start();
require '../includes/db.php';
if (!isset($_GET['id'])) {
    die('No note selected.');
}
$note_id = intval($_GET['id']);
$stmt = $conn->prepare('SELECT * FROM lesson_notes WHERE id=?');
$stmt->execute([$note_id]);
$note = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$note) die('Note not found.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Note</title>
  <link href="https://fonts.googleapis.com/css2?family=Rubik&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Rubik', sans-serif; background: #f4f6f8; margin: 0; }
    .main { max-width: 700px; margin: 40px auto; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(30,64,175,0.08); padding: 32px; }
    h1 { color: #1e3a8a; }
    .label { font-weight: bold; color: #374151; }
    .back-link { display: inline-block; margin-top: 20px; text-decoration: none; color: #2563eb; font-weight: normal; }
    .back-link:hover { text-decoration: underline; }
    .note-content { white-space: pre-line; margin: 1em 0; }
    .file-link { margin-top: 1em; }
  </style>
</head>
<body>
  <div class="main">
    <h1><?= htmlspecialchars($note['title']) ?></h1>
    <div class="label">Subject:</div> <?= htmlspecialchars($note['subject']) ?><br>
    <div class="label">Week Start:</div> <?= htmlspecialchars($note['week_start']) ?><br>
    <div class="label">Content:</div>
    <div class="note-content"><?= nl2br(htmlspecialchars($note['content'])) ?></div>
    <?php if ($note['file_path']): ?>
      <div class="file-link"><a href="../uploads/<?= htmlspecialchars($note['file_path']) ?>" target="_blank">Download File</a></div>
    <?php endif; ?>
    <a href="lesson_notes.php" class="back-link">&larr; Back to Lesson Notes</a>
  </div>
</body>
</html> 