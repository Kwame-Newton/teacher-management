<?php
session_start();
// For demo: Assume teacher_id is 1 and name is set
$teacher_id = 1;
$teacher_name = 'Mr. Kwame Asante';
$school_name = 'Kumasi Primary School';

require_once '../includes/db.php';

// Helper: Get current week number (1-based, for the term)
function getCurrentWeek() {
    $term_start = strtotime('2024-06-03'); // Set your term start date here
    $now = time();
    $week = floor(($now - $term_start) / (7 * 24 * 60 * 60)) + 1;
    return max(1, $week);
}

// Helper: Get all subjects (replace with DB query if needed)
$subjects = [
    'Mathematics', 'English', 'Science', 'Social Studies',
    'ICT', 'RME', 'French', 'Creative Arts', 'Physical Education', 'History', 'Geography', 'Home Economics'
];

// Determine selected week/subject
$selected_week = isset($_POST['week']) ? intval($_POST['week']) : (isset($_GET['week']) ? intval($_GET['week']) : getCurrentWeek());
$selected_subject = isset($_POST['subject']) ? $_POST['subject'] : (isset($_GET['subject']) ? $_GET['subject'] : $subjects[0]);

// Fetch note for selected week/subject
$note = null;
$stmt = $conn->prepare("SELECT * FROM lesson_notes WHERE teacher_id=? AND subject=? AND week_start=?");
$week_start = date('Y-m-d', strtotime('+'.($selected_week-1).' weeks', strtotime('2024-06-03')));
$stmt->execute([$teacher_id, $selected_subject, $week_start]);
$note = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle note submission
$alert = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_note'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $file_path = $note ? $note['file_path'] : null;
    // Handle file upload
    if (isset($_FILES['note_file']) && $_FILES['note_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        $filename = uniqid() . '_' . basename($_FILES['note_file']['name']);
        $target_path = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['note_file']['tmp_name'], $target_path)) {
            $file_path = $filename;
        }
    }
    if ($note) {
        $created_at = strtotime($note['created_at']);
        if (time() - $created_at < 7*24*60*60) {
            $update = $conn->prepare("UPDATE lesson_notes SET title=?, content=?, file_path=?, updated_at=NOW() WHERE id=?");
            $update->execute([$title, $content, $file_path, $note['id']]);
            $alert = '<div class="alert alert-warning">Note updated successfully.</div>';
        } else {
            $alert = '<div class="alert alert-warning">Cannot edit note after a week.</div>';
        }
    } else {
        $insert = $conn->prepare("INSERT INTO lesson_notes (teacher_id, subject, title, content, file_path, week_start) VALUES (?, ?, ?, ?, ?, ?)");
        $insert->execute([$teacher_id, $selected_subject, $title, $content, $file_path, $week_start]);
        $alert = '<div class="alert alert-warning">Note saved successfully.</div>';
    }
    // Refresh note after save
    header("Location: lesson_notes.php?week=$selected_week&subject=".urlencode($selected_subject));
    exit();
}

// Fetch submitted notes for this teacher
$notes = [];
$stmt = $conn->prepare("SELECT * FROM lesson_notes WHERE teacher_id=? ORDER BY week_start DESC, subject");
$stmt->execute([$teacher_id]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $notes[] = $row;
}

// Calculate progress for each subject
$progress = [];
foreach ($subjects as $subj) {
    $count = 0;
    foreach ($notes as $n) {
        if ($n['subject'] === $subj) $count++;
    }
    $progress[$subj] = min(100, round($count / 12 * 100)); // 12 weeks
}

$current_week = getCurrentWeek();
$locked = $note && (time() - strtotime($note['created_at']) >= 7*24*60*60);

// Handle delete action
if (isset($_POST['delete_note_id'])) {
    $delete_id = intval($_POST['delete_note_id']);
    // Only allow delete if within a week
    $stmt = $conn->prepare("SELECT created_at FROM lesson_notes WHERE id=? AND teacher_id=?");
    $stmt->execute([$delete_id, $teacher_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && (time() - strtotime($row['created_at']) < 7*24*60*60)) {
        $del = $conn->prepare("DELETE FROM lesson_notes WHERE id=?");
        $del->execute([$delete_id]);
        $alert = '<div class="alert alert-warning">Note deleted successfully.</div>';
        // Refresh to clear form
        header("Location: lesson_notes.php");
        exit();
    } else {
        $alert = '<div class="alert alert-warning">Cannot delete note after a week.</div>';
    }
}

// Add handler for edit-in-modal POST
if (isset($_POST['modal_edit_note_id'])) {
    $edit_id = intval($_POST['modal_edit_note_id']);
    $title = trim($_POST['modal_title']);
    $content = trim($_POST['modal_content']);
    $file_path = $_POST['modal_existing_file'];
    // Handle file upload
    if (isset($_FILES['modal_note_file']) && $_FILES['modal_note_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        $filename = uniqid() . '_' . basename($_FILES['modal_note_file']['name']);
        $target_path = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['modal_note_file']['tmp_name'], $target_path)) {
            $file_path = $filename;
        }
    }
    // Only allow edit if within a week
    $stmt = $conn->prepare("SELECT created_at FROM lesson_notes WHERE id=? AND teacher_id=?");
    $stmt->execute([$edit_id, $teacher_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && (time() - strtotime($row['created_at']) < 7*24*60*60)) {
        $update = $conn->prepare("UPDATE lesson_notes SET title=?, content=?, file_path=?, updated_at=NOW() WHERE id=?");
        $update->execute([$title, $content, $file_path, $edit_id]);
        header('Location: lesson_notes.php');
        exit();
    } else {
        $alert = '<div=\'alert alert-warning\'>Cannot edit note after a week.</div>';
    }
}

// Determine active tab
$active_tab = isset($_GET['tab']) && $_GET['tab'] === 'curriculum' ? 'curriculum' : 'notes';

// For curriculum section: fetch topics for the teacher
$curriculum_subjects = $subjects; // Use same subjects array
$selected_curr_subject = isset($_GET['curr_subject']) ? $_GET['curr_subject'] : $curriculum_subjects[0];
$curriculum_topics = [];
if ($active_tab === 'curriculum') {
    $stmt = $conn->prepare('SELECT * FROM curriculum_progress WHERE teacher_id=? AND subject=? ORDER BY semester, id');
    $stmt->execute([$teacher_id, $selected_curr_subject]);
    $curriculum_topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// Handle mark as covered (for curriculum tab)
if (isset($_POST['cover_topic_id'])) {
    $topic_id = intval($_POST['cover_topic_id']);
    $stmt = $conn->prepare('UPDATE curriculum_progress SET status="covered", covered_on=CURDATE() WHERE id=? AND teacher_id=?');
    $stmt->execute([$topic_id, $teacher_id]);
    header('Location: lesson_notes.php?tab=curriculum&curr_subject=' . urlencode($selected_curr_subject));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lesson Notes - Teacher Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Rubik&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
          min-height: 100vh;
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
        /* Additional styles for lesson notes content */
        .page-title {
            color: #2c5aa0;
            margin-bottom: 2rem;
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .tabs {
            display: flex;
            background: white;
            border-radius: 10px;
            padding: 0.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .tab {
            flex: 1;
            padding: 1rem;
            text-align: center;
            background: transparent;
            border: none;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .tab.active {
            background: #2c5aa0;
            color: white;
        }
        .tab:hover:not(.active) {
            background: #f0f4ff;
        }
        .content-area {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        .card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e2e8f0;
        }
        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c5aa0;
        }
        .week-selector {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .week-btn {
            padding: 0.5rem 1rem;
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .week-btn.active {
            background: #2c5aa0;
            color: white;
            border-color: #2c5aa0;
        }
        .week-btn.locked {
            background: #f1f5f9;
            color: #64748b;
            cursor: not-allowed;
        }
        .subject-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .subject-card {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .subject-card:hover {
            border-color: #2c5aa0;
            transform: scale(1.05);
        }
        .subject-card.active {
            background: linear-gradient(135deg, #2c5aa0, #1e3a8a);
            color: white;
            border-color: #2c5aa0;
        }
        .progress-bar {
            background: #e2e8f0;
            height: 8px;
            border-radius: 4px;
            margin: 0.5rem 0;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #059669);
            transition: width 0.3s ease;
        }
        .textarea {
            width: 100%;
            min-height: 120px;
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-family: inherit;
            resize: vertical;
        }
        .textarea:focus {
            border-color: #2c5aa0;
            outline: none;
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #f59e0b;
        }
        @media (max-width: 900px) {
            .content-area { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .main { padding: 10px; }
            .content-area { grid-template-columns: 1fr; }
            .container { padding: 1rem; }
        }
        .btn.table-btn {
          padding: 6px 18px;
          font-size: 1em;
          margin-right: 6px;
          margin-bottom: 4px;
          border-radius: 6px;
          text-decoration: none;
          display: inline-block;
          transition: background 0.2s, color 0.2s;
        }
        .btn.btn-primary.table-btn,
        .btn.btn-primary[type="submit"] {
          background: #2563eb;
          color: #fff;
          border: none;
          padding: 12px 32px;
          font-size: 1.1em;
          border-radius: 8px;
          box-shadow: 0 2px 8px rgba(37,99,235,0.08);
          font-weight: 600;
          letter-spacing: 0.5px;
          transition: background 0.2s, box-shadow 0.2s, transform 0.1s;
        }
        .btn.btn-primary.table-btn:hover,
        .btn.btn-primary[type="submit"]:hover {
          background: #1e40af;
          box-shadow: 0 4px 16px rgba(37,99,235,0.15);
          transform: translateY(-2px) scale(1.03);
        }
        .btn-secondary.table-btn {
          background: #f1f5f9;
          color: #2563eb;
          border: 1px solid #cbd5e1;
        }
        .btn-secondary.table-btn:hover {
          background: #e0e7ef;
          color: #1e40af;
        }
        .btn-danger.table-btn {
          background: #ef4444;
          color: #fff;
          border: none;
        }
        .btn-danger.table-btn:hover {
          background: #b91c1c;
        }
        .btn-cover { background: #2563eb; color: #fff; border: none; padding: 6px 18px; border-radius: 6px; }
        .btn-cover:hover { background: #1e40af; }
        .btn-disabled { background: #e5e7eb; color: #a1a1aa; cursor: not-allowed; border: none; padding: 6px 18px; border-radius: 6px; }
    </style>
</head>
<body>
  <div class="sidebar">
    <h2>TMS Teacher</h2>
    <a href="teacher.php">Dashboard</a>
    <a href="teacher_profile.php">Profile</a>
    <a href="teacher_messages.php">Messages</a>
    <a href="teacher_attendance.php">Attendance</a>
    <a href="submit_exams.php">Submit Exams</a>
    <a href="lesson_notes.php" class="active">Lesson Notes</a>
  </div>
  <div class="main">
    <div class="topbar">
      <h1>Lesson Notes</h1>
      <form action="../logout.php" method="POST">
        <button class="logout-btn" type="submit">Logout</button>
      </form>
    </div>
    <div class="page-title">📝 Teacher Notes</div>
    <?= $alert ?>
    <div class="tabs">
        <a href="lesson_notes.php?tab=notes" class="tab<?= $active_tab === 'notes' ? ' active' : '' ?>" style="text-decoration:none;">Weekly Lesson Notes</a>
        <a href="lesson_notes.php?tab=curriculum" class="tab<?= $active_tab === 'curriculum' ? ' active' : '' ?>" style="text-decoration:none;">Curriculum Progress</a>
    </div>
    <div class="content-area">
        <?php if ($active_tab === 'notes'): ?>
            <!-- Weekly Lesson Notes Section -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Weekly Lesson Notes</h3>
                </div>
                <form method="post" enctype="multipart/form-data" id="noteForm">
                    <div class="week-selector">
                        <?php for ($w = 1; $w <= 12; $w++): ?>
                            <?php
                                $is_locked = $w < $current_week;
                                $is_active = $w == $selected_week;
                            ?>
                            <button type="button" class="week-btn<?= $is_locked ? ' locked' : '' ?><?= $is_active ? ' active' : '' ?>" onclick="changeWeek(<?= $w ?>)">Week <?= $w ?></button>
                        <?php endfor; ?>
                        <input type="hidden" name="week" value="<?= $selected_week ?>">
                    </div>
                    <div class="alert alert-warning">
                        ⚠️ Notes will be locked after 1 week and cannot be deleted
                    </div>
                    <div class="subject-grid">
                        <?php foreach ($subjects as $subj): ?>
                        <div class="subject-card<?= $subj === $selected_subject ? ' active' : '' ?>" onclick="changeSubject('<?= htmlspecialchars($subj) ?>')">
                            <strong><?= htmlspecialchars($subj) ?></strong>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?= $progress[$subj] ?>%"></div>
                            </div>
                            <small>
                              <?php
                                $total_weeks = 12;
                                $weeks_with_notes = 0;
                                foreach ($notes as $n) {
                                  if ($n['subject'] === $subj) $weeks_with_notes++;
                                }
                                echo $weeks_with_notes . '/' . $total_weeks . ' Weeks';
                              ?>
                            </small>
                        </div>
                        <?php endforeach; ?>
                        <input type="hidden" name="subject" value="<?= htmlspecialchars($selected_subject) ?>">
                    </div>
                    <input type="text" name="title" placeholder="Note Title" style="width:100%;margin-bottom:1rem;padding:0.5rem;border-radius:8px;border:2px solid #e2e8f0;" value="<?= $note ? htmlspecialchars($note['title']) : '' ?>" <?= $locked ? 'readonly' : '' ?>>
                    <textarea class="textarea" name="content" placeholder="Write your lesson notes..." <?= $locked ? 'readonly' : '' ?>><?= $note ? htmlspecialchars($note['content']) : '' ?></textarea>
                    <div style="margin-top: 1rem; display: flex; gap: 1rem; align-items:center;">
                        <button class="btn btn-primary" type="submit" name="save_note" <?= $locked ? 'disabled' : '' ?>>
                            <i class="fa fa-save"></i> Save Notes
                        </button>
                        <input type="file" name="note_file" style="display:none;" id="note_file" <?= $locked ? 'disabled' : '' ?>>
                        <label for="note_file" class="btn btn-secondary" style="cursor:pointer;<?= $locked ? 'opacity:0.5;pointer-events:none;' : '' ?>">
                            <i class="fa fa-upload"></i> Upload File
                        </label>
                        <?php if ($note && $note['file_path']): ?>
                            <span style="margin-left:10px;">Current file: <a href="../uploads/<?= htmlspecialchars($note['file_path']) ?>" target="_blank">Download</a></span>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <!-- List of Submitted Notes -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Your Submitted Notes</h3>
                </div>
                <div style="max-height:350px;overflow-y:auto;">
                    <?php if (count($notes) === 0): ?>
                        <div>No notes submitted yet.</div>
                    <?php else: ?>
                        <table style="width:100%;border-collapse:collapse;">
                            <tr style="background:#f1f5f9;">
                                <th style="padding:0.5rem;">Week</th>
                                <th>Subject</th>
                                <th>Title</th>
                                <th>File</th>
                                <th>Created</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                            <?php foreach ($notes as $n): ?>
                                <?php
                                    $week_num = floor((strtotime($n['week_start']) - strtotime('2024-06-03'))/(7*24*60*60)) + 1;
                                    if ($week_num < 1 || $week_num > 12) $week_num = 'N/A';
                                    $can_edit = (time() - strtotime($n['created_at']) < 7*24*60*60);
                                ?>
                                <tr>
                                    <td style="padding:0.5rem;">Week <?= $week_num ?></td>
                                    <td><?= htmlspecialchars($n['subject']) ?></td>
                                    <td><?= htmlspecialchars($n['title']) ?></td>
                                    <td>
                                        <?php if ($n['file_path']): ?>
                                            <a href="../uploads/<?= htmlspecialchars($n['file_path']) ?>" target="_blank">Download</a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars(date('Y-m-d', strtotime($n['created_at']))) ?></td>
                                    <td style="text-align:center;">
                                        <a href="edit_note.php?id=<?= $n['id'] ?>" class="btn btn-primary table-btn"><i class="fa fa-pen"></i> Edit</a>
                                        <a href="view_note.php?id=<?= $n['id'] ?>" class="btn btn-secondary table-btn"><i class="fa fa-eye"></i> View</a>
                                        <?php if ($can_edit): ?>
                                            <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this note?');">
                                                <input type="hidden" name="delete_note_id" value="<?= $n['id'] ?>">
                                                <button type="submit" class="btn btn-danger table-btn"><i class="fa fa-trash"></i> Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Curriculum Progress Section -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Curriculum Progress</h3>
                </div>
                <form method="get" style="margin-bottom:1.5em;">
                    <input type="hidden" name="tab" value="curriculum">
                    <label><strong>Select Subject:</strong></label>
                    <select name="curr_subject" onchange="this.form.submit()" style="width: 100%; padding: 0.5rem; margin-top: 0.5rem; border-radius: 6px; border: 2px solid #e2e8f0;">
                        <?php foreach ($curriculum_subjects as $subj): ?>
                            <option value="<?= htmlspecialchars($subj) ?>"<?= $selected_curr_subject === $subj ? ' selected' : '' ?>><?= htmlspecialchars($subj) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <?php
                $total = count($curriculum_topics);
                $covered = 0;
                foreach ($curriculum_topics as $t) if ($t['status'] === 'covered') $covered++;
                $progress = $total ? round($covered / $total * 100) : 0;
                ?>
                <div style="margin-bottom:1em;">
                    <strong>Progress: <?= $progress ?>%</strong>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $progress ?>%"></div>
                    </div>
                </div>
                <div style="max-height: 350px; overflow-y: auto;">
                    <?php if ($total === 0): ?>
                        <div>No topics found for this subject.</div>
                    <?php else: ?>
                        <table style="width:100%;border-collapse:collapse;">
                            <tr style="background:#f1f5f9;">
                                <th>Topic</th>
                                <th>Status</th>
                                <th>Covered On</th>
                                <th>Action</th>
                            </tr>
                            <?php foreach ($curriculum_topics as $topic): ?>
                                <tr class="<?= $topic['status'] ?>">
                                    <td><?= htmlspecialchars($topic['topic']) ?></td>
                                    <td><?= ucfirst($topic['status']) ?></td>
                                    <td><?= $topic['covered_on'] ? htmlspecialchars($topic['covered_on']) : '-' ?></td>
                                    <td>
                                        <?php if ($topic['status'] === 'remaining'): ?>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="cover_topic_id" value="<?= $topic['id'] ?>">
                                                <button type="submit" class="btn btn-cover"><i class="fa fa-check"></i> Mark as Covered</button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-disabled" disabled><i class="fa fa-check"></i> Covered</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
  </div>
  <!-- Modal for viewing note -->
  <div id="viewModal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:white;padding:2rem;border-radius:10px;max-width:800px;width:95vw;max-height:80vh;overflow-y:auto;position:relative;">
      <button onclick="document.getElementById('viewModal').style.display='none'" style="position:absolute;top:10px;right:10px;font-size:1.2em;">&times;</button>
      <h2 id="modalTitle"></h2>
      <div id="modalContent" style="margin:1em 0;white-space:pre-line;max-height:60vh;overflow-y:auto;"></div>
      <div id="modalFile"></div>
    </div>
  </div>
  <!-- Edit Modal -->
  <div id="editModal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);z-index:2100;align-items:center;justify-content:center;">
    <div style="background:white;padding:2rem;border-radius:10px;max-width:500px;width:90%;position:relative;">
      <button onclick="document.getElementById('editModal').style.display='none'" style="position:absolute;top:10px;right:10px;font-size:1.2em;">&times;</button>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="modal_edit_note_id" id="modal_edit_note_id">
        <input type="hidden" name="modal_existing_file" id="modal_existing_file">
        <div style="margin-bottom:1em;">
          <label>Title:</label>
          <input type="text" name="modal_title" id="modal_title" style="width:100%;padding:0.5em;" required>
        </div>
        <div style="margin-bottom:1em;">
          <label>Content:</label>
          <textarea name="modal_content" id="modal_content" style="width:100%;min-height:100px;padding:0.5em;" required></textarea>
        </div>
        <div style="margin-bottom:1em;">
          <label>File:</label>
          <input type="file" name="modal_note_file" id="modal_note_file">
          <div id="modal_file_link" style="margin-top:0.5em;"></div>
        </div>
        <button type="submit" class="btn btn-primary" id="modal_save_btn">Save Changes</button>
      </form>
    </div>
  </div>
  <script>
    function changeWeek(week) {
        const url = new URL(window.location.href);
        url.searchParams.set('week', week);
        url.searchParams.set('subject', document.querySelector('input[name=subject]').value);
        window.location.href = url.toString();
    }
    function changeSubject(subject) {
        const url = new URL(window.location.href);
        url.searchParams.set('subject', subject);
        url.searchParams.set('week', document.querySelector('input[name=week]').value);
        window.location.href = url.toString();
    }
    function viewNote(title, content, file) {
      document.getElementById('modalTitle').innerText = title;
      document.getElementById('modalContent').innerText = content;
      if (file) {
        document.getElementById('modalFile').innerHTML = '<a href="'+file+'" target="_blank">Download File</a>';
      } else {
        document.getElementById('modalFile').innerHTML = '';
      }
      document.getElementById('viewModal').style.display = 'flex';
    }
    function editNoteModal(id, title, content, fileUrl, fileName, canEdit) {
      document.getElementById('editModal').style.display = 'flex';
      document.getElementById('modal_edit_note_id').value = id;
      document.getElementById('modal_title').value = title;
      document.getElementById('modal_content').value = content;
      document.getElementById('modal_existing_file').value = fileName;
      if (fileUrl) {
        document.getElementById('modal_file_link').innerHTML = '<a href="'+fileUrl+'" target="_blank">Download Current File</a>';
      } else {
        document.getElementById('modal_file_link').innerHTML = '';
      }
      document.getElementById('modal_title').readOnly = !canEdit;
      document.getElementById('modal_content').readOnly = !canEdit;
      document.getElementById('modal_note_file').disabled = !canEdit;
      document.getElementById('modal_save_btn').disabled = !canEdit;
    }
  </script>
</body>
</html> 