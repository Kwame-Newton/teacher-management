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
$can_edit = (time() - strtotime($note['created_at']) < 7*24*60*60);
$alert = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_edit) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $file_path = $note['file_path'];
    if (isset($_FILES['note_file']) && $_FILES['note_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        $filename = uniqid() . '_' . basename($_FILES['note_file']['name']);
        $target_path = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['note_file']['tmp_name'], $target_path)) {
            $file_path = $filename;
        }
    }
    $update = $conn->prepare('UPDATE lesson_notes SET title=?, content=?, file_path=?, updated_at=NOW() WHERE id=?');
    $update->execute([$title, $content, $file_path, $note_id]);
    header('Location: lesson_notes.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Note</title>
  <link href="https://fonts.googleapis.com/css2?family=Rubik&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Rubik', sans-serif; background: #f4f6f8; margin: 0; }
    .main { max-width: 700px; margin: 40px auto; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(30,64,175,0.08); padding: 32px; }
    h1 { color: #1e3a8a; }
    .label { font-weight: bold; color: #374151; }
    .back-link { display: inline-block; margin-top: 20px; text-decoration: none; color: #2563eb; font-weight: normal; }
    .back-link:hover { text-decoration: underline; }
    .file-link { margin-top: 1em; }
    .alert { padding: 1em; background: #fef3c7; color: #92400e; border: 1px solid #f59e0b; border-radius: 8px; margin-bottom: 1em; }
    input[type="text"], textarea { width: 100%; padding: 0.5em; border-radius: 8px; border: 2px solid #e2e8f0; margin-bottom: 1em; }
    textarea { min-height: 120px; }
    button { padding: 0.8rem 1.5rem; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; background: #2c5aa0; color: white; }
    button:disabled { background: #ccc; cursor: not-allowed; }
  </style>
</head>
<body>
  <div class="main">
    <h1>Edit Note</h1>
    <?php if (!$can_edit): ?>
      <div class="alert">This note can no longer be edited (more than a week old).</div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
      <div class="label">Title:</div>
      <input type="text" name="title" value="<?= htmlspecialchars($note['title']) ?>" required <?= !$can_edit ? 'readonly' : '' ?>>
      <div class="label">Content:</div>
      <textarea name="content" required <?= !$can_edit ? 'readonly' : '' ?>><?= htmlspecialchars($note['content']) ?></textarea>
      <div class="label">File:</div>
      <input type="file" name="note_file" <?= !$can_edit ? 'disabled' : '' ?>>
      <?php if ($note['file_path']): ?>
        <div class="file-link"><a href="../uploads/<?= htmlspecialchars($note['file_path']) ?>" target="_blank">Download Current File</a></div>
      <?php endif; ?>
      <button type="submit" <?= !$can_edit ? 'disabled' : '' ?>>Save Changes</button>
    </form>
    <a href="lesson_notes.php" class="back-link">&larr; Back to Lesson Notes</a>
  </div>
</body>
</html> 