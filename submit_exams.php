<?php
session_start();
require '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$upload_dir = '../uploads/';
$errors = [];
$success = '';

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM exam_submissions WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$del_id, $teacher_id]);
    $success = 'Submission deleted.';
}

// Handle edit action (update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_exam']) && isset($_POST['edit_id'])) {
    $edit_id = intval($_POST['edit_id']);
    $subject = $_POST['subject'] ?? '';
    $class = $_POST['class'] ?? '';
    $exam_type = $_POST['exam_type'] ?? '';
    $term = $_POST['term'] ?? '';
    $academic_year = $_POST['academic_year'] ?? '';
    $exam_date = $_POST['exam_date'] ?? null;
    $typed_questions = $_POST['typed_questions'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $status = isset($_POST['save_draft']) ? 'Draft' : 'Pending';

    // File uploads (optional)
    $question_file = $_POST['existing_question_file'] ?? null;
    if (isset($_FILES['question_file']) && $_FILES['question_file']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['question_file']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('qf_') . '.' . $ext;
        move_uploaded_file($_FILES['question_file']['tmp_name'], $upload_dir . $filename);
        $question_file = $filename;
    }
    $marking_scheme = $_POST['existing_marking_scheme'] ?? null;
    if (isset($_FILES['marking_scheme']) && $_FILES['marking_scheme']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['marking_scheme']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('ms_') . '.' . $ext;
        move_uploaded_file($_FILES['marking_scheme']['tmp_name'], $upload_dir . $filename);
        $marking_scheme = $filename;
    }

    // Validation
    if (!$subject || !$class || !$exam_type || !$term || !$academic_year) {
        $errors[] = 'Please fill in all required fields.';
    }
    if (!$question_file && !$typed_questions) {
        $errors[] = 'Please either upload a question file or type questions inline.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE exam_submissions SET subject=?, class=?, exam_type=?, term=?, academic_year=?, exam_date=?, question_file=?, typed_questions=?, marking_scheme=?, notes=?, status=? WHERE id=? AND teacher_id=?");
        $stmt->execute([
            $subject, $class, $exam_type, $term, $academic_year, $exam_date, $question_file, $typed_questions, $marking_scheme, $notes, $status, $edit_id, $teacher_id
        ]);
        $success = 'Exam submission updated successfully!';
        unset($_GET['edit']);
    }
}

// Handle form submission (new)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_exam'])) {
    $subject = $_POST['subject'] ?? '';
    $class = $_POST['class'] ?? '';
    $exam_type = $_POST['exam_type'] ?? '';
    $term = $_POST['term'] ?? '';
    $academic_year = $_POST['academic_year'] ?? '';
    $exam_date = $_POST['exam_date'] ?? null;
    $typed_questions = $_POST['typed_questions'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $status = isset($_POST['save_draft']) ? 'Draft' : 'Pending';

    // File uploads
    $question_file = null;
    if (isset($_FILES['question_file']) && $_FILES['question_file']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['question_file']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('qf_') . '.' . $ext;
        move_uploaded_file($_FILES['question_file']['tmp_name'], $upload_dir . $filename);
        $question_file = $filename;
    }
    $marking_scheme = null;
    if (isset($_FILES['marking_scheme']) && $_FILES['marking_scheme']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['marking_scheme']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('ms_') . '.' . $ext;
        move_uploaded_file($_FILES['marking_scheme']['tmp_name'], $upload_dir . $filename);
        $marking_scheme = $filename;
    }

    // Validation
    if (!$subject || !$class || !$exam_type || !$term || !$academic_year) {
        $errors[] = 'Please fill in all required fields.';
    }
    if (!$question_file && !$typed_questions) {
        $errors[] = 'Please either upload a question file or type questions inline.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO exam_submissions (teacher_id, subject, class, exam_type, term, academic_year, exam_date, question_file, typed_questions, marking_scheme, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $teacher_id, $subject, $class, $exam_type, $term, $academic_year, $exam_date, $question_file, $typed_questions, $marking_scheme, $notes, $status
        ]);
        $success = 'Exam submission saved successfully!';
    }
}

// Fetch teacher's submissions
$submissions = $conn->prepare("SELECT * FROM exam_submissions WHERE teacher_id = ? ORDER BY created_at DESC");
$submissions->execute([$teacher_id]);
$submissions = $submissions->fetchAll(PDO::FETCH_ASSOC);

// Fetch for view/edit
$view_data = null;
$edit_data = null;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $stmt = $conn->prepare("SELECT * FROM exam_submissions WHERE id = ? AND teacher_id = ?");
    $stmt->execute([intval($_GET['view']), $teacher_id]);
    $view_data = $stmt->fetch(PDO::FETCH_ASSOC);
}
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM exam_submissions WHERE id = ? AND teacher_id = ?");
    $stmt->execute([intval($_GET['edit']), $teacher_id]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch teacher's exam timetable
$exam_timetable = $conn->prepare("SELECT * FROM exam_timetable WHERE teacher_id = ? ORDER BY exam_date, start_time");
$exam_timetable->execute([$teacher_id]);
$exam_timetable = $exam_timetable->fetchAll(PDO::FETCH_ASSOC);
// Fetch teacher's class timetable
$class_timetable = $conn->prepare("SELECT * FROM class_timetable WHERE teacher_id = ? ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday'), start_time");
$class_timetable->execute([$teacher_id]);
$class_timetable = $class_timetable->fetchAll(PDO::FETCH_ASSOC);
// Handle add exam timetable
if (isset($_POST['add_exam_timetable'])) {
    $subject = $_POST['et_subject'] ?? '';
    $class = $_POST['et_class'] ?? '';
    $exam_date = $_POST['et_exam_date'] ?? '';
    $start_time = $_POST['et_start_time'] ?? '';
    $duration = $_POST['et_duration'] ?? '';
    $room = $_POST['et_room'] ?? '';
    if ($subject && $class && $exam_date && $start_time) {
        $stmt = $conn->prepare("INSERT INTO exam_timetable (teacher_id, subject, class, exam_date, start_time, duration, room, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$teacher_id, $subject, $class, $exam_date, $start_time, $duration, $room, $teacher_id]);
        $success = 'Exam timetable entry added.';
    } else {
        $errors[] = 'Please fill in all required fields for exam timetable.';
    }
}
// Handle add class timetable
if (isset($_POST['add_class_timetable'])) {
    $class = $_POST['ct_class'] ?? '';
    $subject = $_POST['ct_subject'] ?? '';
    $day_of_week = $_POST['ct_day_of_week'] ?? '';
    $start_time = $_POST['ct_start_time'] ?? '';
    $end_time = $_POST['ct_end_time'] ?? '';
    $room = $_POST['ct_room'] ?? '';
    if ($class && $subject && $day_of_week && $start_time && $end_time) {
        $stmt = $conn->prepare("INSERT INTO class_timetable (teacher_id, class, subject, day_of_week, start_time, end_time, room, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$teacher_id, $class, $subject, $day_of_week, $start_time, $end_time, $room, $teacher_id]);
        $success = 'Class timetable entry added.';
    } else {
        $errors[] = 'Please fill in all required fields for class timetable.';
    }
}

// Quick Stats calculations
// Submissions this term (current academic year and term)
$current_year = date('Y');
$academic_year = $current_year . '/' . ($current_year + 1);
$term = isset($_POST['term']) ? $_POST['term'] : '1st Term'; // fallback
$submissions_this_term = $conn->prepare("SELECT COUNT(*) FROM exam_submissions WHERE teacher_id = ? AND academic_year = ? AND term = ?");
$submissions_this_term->execute([$teacher_id, $academic_year, $term]);
$submissions_this_term = $submissions_this_term->fetchColumn();
// Classes teaching (distinct classes in class_timetable)
$classes_teaching = $conn->prepare("SELECT COUNT(DISTINCT class) FROM class_timetable WHERE teacher_id = ?");
$classes_teaching->execute([$teacher_id]);
$classes_teaching = $classes_teaching->fetchColumn();
// Upcoming exams (future dates in exam_timetable)
$upcoming_exams = $conn->prepare("SELECT COUNT(*) FROM exam_timetable WHERE teacher_id = ? AND exam_date >= CURDATE()");
$upcoming_exams->execute([$teacher_id]);
$upcoming_exams = $upcoming_exams->fetchColumn();
// Weekly teaching hours (sum of hours per week in class_timetable)
$weekly_hours = 0;
foreach ($class_timetable as $row) {
    $start = strtotime($row['start_time']);
    $end = strtotime($row['end_time']);
    if ($start && $end && $end > $start) {
        $weekly_hours += ($end - $start) / 3600;
    }
}
$weekly_hours = round($weekly_hours, 1);

// Handle delete/edit for exam timetable
if (isset($_GET['delete_exam_timetable']) && is_numeric($_GET['delete_exam_timetable'])) {
    $del_id = intval($_GET['delete_exam_timetable']);
    $stmt = $conn->prepare("DELETE FROM exam_timetable WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$del_id, $teacher_id]);
    $success = 'Exam timetable entry deleted.';
}
$edit_exam_data = null;
if (isset($_GET['edit_exam_timetable']) && is_numeric($_GET['edit_exam_timetable'])) {
    $stmt = $conn->prepare("SELECT * FROM exam_timetable WHERE id = ? AND teacher_id = ?");
    $stmt->execute([intval($_GET['edit_exam_timetable']), $teacher_id]);
    $edit_exam_data = $stmt->fetch(PDO::FETCH_ASSOC);
}
if (isset($_POST['update_exam_timetable']) && isset($_POST['edit_exam_id'])) {
    $edit_id = intval($_POST['edit_exam_id']);
    $subject = $_POST['et_subject'] ?? '';
    $class = $_POST['et_class'] ?? '';
    $exam_date = $_POST['et_exam_date'] ?? '';
    $start_time = $_POST['et_start_time'] ?? '';
    $duration = $_POST['et_duration'] ?? '';
    $room = $_POST['et_room'] ?? '';
    if ($subject && $class && $exam_date && $start_time) {
        $stmt = $conn->prepare("UPDATE exam_timetable SET subject=?, class=?, exam_date=?, start_time=?, duration=?, room=? WHERE id=? AND teacher_id=?");
        $stmt->execute([$subject, $class, $exam_date, $start_time, $duration, $room, $edit_id, $teacher_id]);
        $success = 'Exam timetable entry updated.';
        unset($_GET['edit_exam_timetable']);
    } else {
        $errors[] = 'Please fill in all required fields for exam timetable.';
    }
}
// Handle delete/edit for class timetable
if (isset($_GET['delete_class_timetable']) && is_numeric($_GET['delete_class_timetable'])) {
    $del_id = intval($_GET['delete_class_timetable']);
    $stmt = $conn->prepare("DELETE FROM class_timetable WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$del_id, $teacher_id]);
    $success = 'Class timetable entry deleted.';
}
$edit_class_data = null;
if (isset($_GET['edit_class_timetable']) && is_numeric($_GET['edit_class_timetable'])) {
    $stmt = $conn->prepare("SELECT * FROM class_timetable WHERE id = ? AND teacher_id = ?");
    $stmt->execute([intval($_GET['edit_class_timetable']), $teacher_id]);
    $edit_class_data = $stmt->fetch(PDO::FETCH_ASSOC);
}
if (isset($_POST['update_class_timetable']) && isset($_POST['edit_class_id'])) {
    $edit_id = intval($_POST['edit_class_id']);
    $class = $_POST['ct_class'] ?? '';
    $subject = $_POST['ct_subject'] ?? '';
    $day_of_week = $_POST['ct_day_of_week'] ?? '';
    $start_time = $_POST['ct_start_time'] ?? '';
    $end_time = $_POST['ct_end_time'] ?? '';
    $room = $_POST['ct_room'] ?? '';
    if ($class && $subject && $day_of_week && $start_time && $end_time) {
        $stmt = $conn->prepare("UPDATE class_timetable SET class=?, subject=?, day_of_week=?, start_time=?, end_time=?, room=? WHERE id=? AND teacher_id=?");
        $stmt->execute([$class, $subject, $day_of_week, $start_time, $end_time, $room, $edit_id, $teacher_id]);
        $success = 'Class timetable entry updated.';
        unset($_GET['edit_class_timetable']);
    } else {
        $errors[] = 'Please fill in all required fields for class timetable.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Exam Questions</title>
    <link href="https://fonts.googleapis.com/css2?family=Rubik&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Rubik', Arial, sans-serif; 
            background-color: #f4f6f8; 
            margin: 0; 
            padding: 0; 
            display: flex;
        }
        .sidebar {
            width: 220px;
             background-color: #1e40af;
            color: white;
             position: fixed;
             left: 0;
            top: 0;
            height: 100vh;
             z-index: 1000;
        }
        .sidebar h2 {
            font-size: 20px;
            margin-bottom: 30px;
            color:white;
            padding-right: 60px;
        }
        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            margin: 15px 0;
            padding: 10px;
            border-radius: 5px;
            padding-left: 30px;
        }
        .sidebar a.active, .sidebar a:hover {
            background-color: #3749c1;
        }
        .main {
            flex: 1;
            padding: 40px 20px 20px 20px;
            margin-left: 220px;
            min-height: 100vh;
            background: #f4f6f8;
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
        .main-content { 
            padding: 2rem; 
            max-width: 900px; 
            margin: auto; 
            background: white; 
            border-radius: 10px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
        }
        h2 { 
            text-align: center; 
            margin-bottom: 1rem; 
            color: #333;
        }
        form label { 
            display: block; 
            margin-top: 1rem; 
            font-weight: bold; 
            color: #555;
        }
        form select, form input, form textarea { 
            width: 100%; 
            padding: 0.5rem; 
            margin-top: 0.25rem; 
            border: 1px solid #ccc; 
            border-radius: 5px; 
            box-sizing: border-box;
        }
        form textarea { 
            height: 150px; 
            resize: vertical;
        }
        form button { 
            margin-top: 1.5rem; 
            padding: 0.75rem 1.5rem; 
            border: none; 
            border-radius: 5px; 
            background: #007bff; 
            color: white; 
            font-weight: bold; 
            cursor: pointer; 
            width: 100%;
        }
        form button:hover { 
            background: #0056b3; 
        }
        .recent-submissions { 
            margin-top: 2rem; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 1rem; 
        }
        table, th, td { 
            border: 1px solid #ccc; 
        }
        th, td { 
            padding: 0.75rem; 
            text-align: left; 
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .options {
            margin-top: 1rem;
        }
        .options input[type="checkbox"] { 
            margin-right: 0.5rem; 
            width: auto;
        }
        .options label {
            display: inline-block;
            margin-right: 1rem;
            font-weight: normal;
        }
        .radio-group {
            margin-top: 0.25rem;
        }
        .radio-group input[type="radio"] {
            width: auto;
            margin-right: 0.5rem;
            margin-left: 1rem;
        }
        .radio-group label {
            display: inline;
            font-weight: normal;
            margin-right: 1rem;
        }
        /* Dashboard Tabs */
        .dashboard-tabs {
            display: flex;
            margin-top: 2rem;
            border-bottom: 2px solid #e9ecef;
        }
        .tab-btn {
            padding: 12px 20px;
            border: none;
            background: none;
            cursor: pointer;
            font-weight: 500;
            color: #6c757d;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }
        .tab-btn.active {
            color: #007bff;
            border-bottom-color: #007bff;
        }
        .tab-btn:hover {
            color: #007bff;
        }
        .tab-content {
            display: none;
            margin-top: 1.5rem;
        }
        .tab-content.active {
            display: block;
        }
        /* Status badges */
        .status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status.approved {
            background-color: #d4edda;
            color: #155724;
        }
        .status.pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status.rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status.scheduled {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        /* Action buttons */
        .action-btn {
            padding: 4px 12px;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            font-weight: 500;
        }
        .action-btn.view {
            background-color: #007bff;
            color: white;
        }
        .action-btn.edit {
            background-color: #28a745;
            color: white;
        }
        .action-btn:hover {
            opacity: 0.8;
        }
        /* Timetable controls */
        .timetable-controls {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .timetable-controls select {
            width: auto;
            min-width: 150px;
        }
        .timetable {
            font-size: 14px;
        }
        /* Schedule Grid */
        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        .schedule-day {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        .schedule-day h4 {
            margin: 0 0 1rem 0;
            color: #495057;
            font-size: 16px;
        }
        .period {
            background: white;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border-radius: 6px;
            border-left: 3px solid #28a745;
        }
        .period:last-child {
            margin-bottom: 0;
        }
        .period .time {
            display: block;
            font-weight: bold;
            color: #007bff;
            font-size: 12px;
        }
        .period .subject {
            display: block;
            font-weight: 500;
            margin: 4px 0;
            color: #495057;
        }
        .period .room {
            display: block;
            font-size: 12px;
            color: #6c757d;
        }
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }
        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .stat-card:nth-child(4) {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        .stat-card h4 {
            margin: 0 0 0.5rem 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0.5rem 0;
        }
        .stat-detail {
            font-size: 12px;
            opacity: 0.8;
        }
        /* Notifications */
        .notifications {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
        }
        .notifications h4 {
            margin: 0 0 1rem 0;
            color: #495057;
        }
        .notification-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            background: white;
            border-radius: 6px;
            border-left: 3px solid #007bff;
        }
        .notification-item:last-child {
            margin-bottom: 0;
        }
        .notification-icon {
            font-size: 1.2rem;
            margin-right: 0.75rem;
        }
        .notification-content strong {
            display: block;
            color: #495057;
        }
        .notification-content small {
            color: #6c757d;
            font-size: 11px;
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
    <a href="submit_exams.php" class="active">Submit Exams</a>
    <a href="lesson_notes.php">Lesson Notes</a>
</div>
<div class="main">
    <div class="topbar">
        <h1>Submit Exam Questions</h1>
        <form action="../logout.php" method="POST">
            <button class="logout-btn" type="submit">Logout</button>
        </form>
    </div>
    <div class="main-content">
        <h2>Submit Exam Questions</h2>
        <form id="examForm" method="POST" enctype="multipart/form-data">
            <!-- Exam Details -->
            <label for="subject">Subject:</label>
            <select name="subject" id="subject" required>
                <option value="">-- Select Subject --</option>
                <option value="English">English</option>
                <option value="Mathematics">Mathematics</option>
                <option value="Science">Science</option>
                <option value="ICT">ICT</option>
                <option value="RME">RME</option>
            </select>
            <label for="class">Class:</label>
            <select name="class" id="class" required>
                <option value="">-- Select Class --</option>
                <option value="Basic 1">Basic 1</option>
                <option value="Basic 2">Basic 2</option>
                <option value="Basic 3">Basic 3</option>
                <option value="JHS 1">JHS 1</option>
                <option value="JHS 2">JHS 2</option>
                <option value="JHS 3">JHS 3</option>
                
            </select>
            <label>Exam Type:</label>
            <div class="radio-group">
                <input type="radio" name="exam_type" value="Midterm" id="midterm" required>
                <label for="midterm">Midterm</label>
                <input type="radio" name="exam_type" value="End of Term" id="endterm">
                <label for="endterm">End of Term</label>
            </div>
            <label for="term">Term:</label>
            <select name="term" id="term" required>
                <option value="1st Term">1st Term</option>
                <option value="2nd Term">2nd Term</option>
                <option value="3rd Term">3rd Term</option>
            </select>
            <label for="academic_year">Academic Year:</label>
            <input type="text" name="academic_year" id="academic_year" required>
            <label for="exam_date">Exam Date:</label>
            <input type="date" name="exam_date" id="exam_date">
            <!-- Upload/Type Questions -->
            <label for="question_file">Upload Question File (PDF/DOC):</label>
            <input type="file" name="question_file" id="question_file" accept=".pdf,.doc,.docx">
            <label for="typed_questions">OR Type Questions Inline:</label>
            <textarea name="typed_questions" id="typed_questions" placeholder="e.g. 1. What is the capital of Ghana?&#10;2. Define energy."></textarea>
            <!-- Marking Scheme -->
            <label for="marking_scheme">Upload Marking Scheme (Optional):</label>
            <input type="file" name="marking_scheme" id="marking_scheme" accept=".pdf,.doc,.docx">
            <label for="notes">Notes to Headmaster:</label>
            <textarea name="notes" id="notes" placeholder="Any instructions or clarifications..."></textarea>
            <!-- Options -->
            <div class="options">
                <label><input type="checkbox" name="save_draft" id="save_draft"> Save as Draft</label>
                <label><input type="checkbox" name="notify" id="notify"> Notify me when approved</label>
            </div>
            <!-- Submit -->
            <button type="submit" name="submit_exam">Submit Questions</button>
        </form>
        <!-- Dashboard Tabs -->
        <div class="dashboard-tabs">
            <button class="tab-btn active" onclick="showTab('submissions')">Recent Submissions</button>
            <button class="tab-btn" onclick="showTab('exam-timetable')">My Exam Timetable</button>
            <button class="tab-btn" onclick="showTab('class-schedule')">My Class Timetable</button>
            <button class="tab-btn" onclick="showTab('timetable-management')">Timetable Management</button>
            <button class="tab-btn" onclick="showTab('quick-stats')">Quick Stats</button>
        </div>
        <!-- Recent Submissions Tab -->
        <div id="submissions" class="tab-content active">
            <h3>Recent Submissions</h3>
            <?php if ($success): ?><div style="color:green; font-weight:bold; margin-bottom:10px;"> <?= htmlspecialchars($success) ?> </div><?php endif; ?>
            <?php if ($errors): ?><div style="color:red; font-weight:bold; margin-bottom:10px;"> <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?> </div><?php endif; ?>
            <?php if ($view_data): ?>
                <div style="background:#f8f9fa; border-radius:8px; padding:20px; margin-bottom:20px;">
                    <h4>Submission Details</h4>
                    <b>Subject:</b> <?= htmlspecialchars($view_data['subject']) ?><br>
                    <b>Class:</b> <?= htmlspecialchars($view_data['class']) ?><br>
                    <b>Exam Type:</b> <?= htmlspecialchars($view_data['exam_type']) ?><br>
                    <b>Term:</b> <?= htmlspecialchars($view_data['term']) ?><br>
                    <b>Academic Year:</b> <?= htmlspecialchars($view_data['academic_year']) ?><br>
                    <b>Exam Date:</b> <?= htmlspecialchars($view_data['exam_date']) ?><br>
                    <b>Status:</b> <?= htmlspecialchars($view_data['status']) ?><br>
                    <b>Notes:</b> <?= nl2br(htmlspecialchars($view_data['notes'])) ?><br>
                    <b>Question File:</b> <?php if ($view_data['question_file']): ?><a href="../uploads/<?= htmlspecialchars($view_data['question_file']) ?>" target="_blank">Download</a><?php else: ?>None<?php endif; ?><br>
                    <b>Marking Scheme:</b> <?php if ($view_data['marking_scheme']): ?><a href="../uploads/<?= htmlspecialchars($view_data['marking_scheme']) ?>" target="_blank">Download</a><?php else: ?>None<?php endif; ?><br>
                    <b>Typed Questions:</b><br><pre style="background:#fff; border:1px solid #ccc; border-radius:6px; padding:10px;"><?= htmlspecialchars($view_data['typed_questions']) ?></pre>
                    <a href="submit_exams.php" style="display:inline-block; margin-top:10px; color:#007bff;">Close</a>
                </div>
            <?php endif; ?>
            <?php if ($edit_data): ?>
                <div style="background:#f8f9fa; border-radius:8px; padding:20px; margin-bottom:20px;">
                    <h4>Edit Submission</h4>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="edit_id" value="<?= $edit_data['id'] ?>">
                        <label for="subject">Subject:</label>
                        <select name="subject" id="subject" required>
                            <option value="">-- Select Subject --</option>
                            <option value="English" <?= $edit_data['subject']==='English'?'selected':'' ?>>English</option>
                            <option value="Mathematics" <?= $edit_data['subject']==='Mathematics'?'selected':'' ?>>Mathematics</option>
                            <option value="Science" <?= $edit_data['subject']==='Science'?'selected':'' ?>>Science</option>
                            <option value="ICT" <?= $edit_data['subject']==='ICT'?'selected':'' ?>>ICT</option>
                            <option value="RME" <?= $edit_data['subject']==='RME'?'selected':'' ?>>RME</option>
                        </select>
                        <label for="class">Class:</label>
                        <select name="class" id="class" required>
                            <option value="">-- Select Class --</option>
                            <option value="Basic 1" <?= $edit_data['class']==='Basic 1'?'selected':'' ?>>Basic 1</option>
                            <option value="Basic 2" <?= $edit_data['class']==='Basic 2'?'selected':'' ?>>Basic 2</option>
                            <option value="Basic 3" <?= $edit_data['class']==='Basic 3'?'selected':'' ?>>Basic 3</option>
                            <option value="JHS 1" <?= $edit_data['class']==='JHS 1'?'selected':'' ?>>JHS 1</option>
                            <option value="JHS 2" <?= $edit_data['class']==='JHS 2'?'selected':'' ?>>JHS 2</option>
                        </select>
                        <label>Exam Type:</label>
                        <div class="radio-group">
                            <input type="radio" name="exam_type" value="Midterm" id="midterm" <?= $edit_data['exam_type']==='Midterm'?'checked':'' ?> required>
                            <label for="midterm">Midterm</label>
                            <input type="radio" name="exam_type" value="End of Term" id="endterm" <?= $edit_data['exam_type']==='End of Term'?'checked':'' ?> >
                            <label for="endterm">End of Term</label>
                        </div>
                        <label for="term">Term:</label>
                        <select name="term" id="term" required>
                            <option value="1st Term" <?= $edit_data['term']==='1st Term'?'selected':'' ?>>1st Term</option>
                            <option value="2nd Term" <?= $edit_data['term']==='2nd Term'?'selected':'' ?>>2nd Term</option>
                            <option value="3rd Term" <?= $edit_data['term']==='3rd Term'?'selected':'' ?>>3rd Term</option>
                        </select>
                        <label for="academic_year">Academic Year:</label>
                        <input type="text" name="academic_year" id="academic_year" value="<?= htmlspecialchars($edit_data['academic_year']) ?>" required>
                        <label for="exam_date">Exam Date:</label>
                        <input type="date" name="exam_date" id="exam_date" value="<?= htmlspecialchars($edit_data['exam_date']) ?>">
                        <label for="question_file">Upload Question File (PDF/DOC):</label>
                        <?php if ($edit_data['question_file']): ?>
                            <a href="../uploads/<?= htmlspecialchars($edit_data['question_file']) ?>" target="_blank">Current File</a>
                        <?php endif; ?>
                        <input type="file" name="question_file" id="question_file" accept=".pdf,.doc,.docx">
                        <input type="hidden" name="existing_question_file" value="<?= htmlspecialchars($edit_data['question_file']) ?>">
                        <label for="typed_questions">OR Type Questions Inline:</label>
                        <textarea name="typed_questions" id="typed_questions" placeholder="e.g. 1. What is the capital of Ghana?&#10;2. Define energy."><?= htmlspecialchars($edit_data['typed_questions']) ?></textarea>
                        <label for="marking_scheme">Upload Marking Scheme (Optional):</label>
                        <?php if ($edit_data['marking_scheme']): ?>
                            <a href="../uploads/<?= htmlspecialchars($edit_data['marking_scheme']) ?>" target="_blank">Current File</a>
                        <?php endif; ?>
                        <input type="file" name="marking_scheme" id="marking_scheme" accept=".pdf,.doc,.docx">
                        <input type="hidden" name="existing_marking_scheme" value="<?= htmlspecialchars($edit_data['marking_scheme']) ?>">
                        <label for="notes">Notes to Headmaster:</label>
                        <textarea name="notes" id="notes" placeholder="Any instructions or clarifications..."><?= htmlspecialchars($edit_data['notes']) ?></textarea>
                        <div class="options">
                            <label><input type="checkbox" name="save_draft" id="save_draft" <?= $edit_data['status']==='Draft'?'checked':'' ?>> Save as Draft</label>
                            <label><input type="checkbox" name="notify" id="notify"> Notify me when approved</label>
                        </div>
                        <button type="submit" name="edit_exam">Update Submission</button>
                        <a href="submit_exams.php" style="display:inline-block; margin-top:10px; color:#007bff;">Cancel</a>
                    </form>
                </div>
            <?php endif; ?>
            <table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Class</th>
                        <th>Term</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($submissions)): ?>
                    <tr><td colspan="6" style="text-align:center; color:#888;">No submissions yet.</td></tr>
                <?php else: foreach ($submissions as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['subject']) ?></td>
                        <td><?= htmlspecialchars($row['class']) ?></td>
                        <td><?= htmlspecialchars($row['term']) ?></td>
                        <td><span class="status <?= strtolower($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                        <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                        <td>
                            <a href="?view=<?= $row['id'] ?>" class="action-btn view">View</a>
                            <a href="?edit=<?= $row['id'] ?>" class="action-btn edit">Edit</a>
                            <a href="?delete=<?= $row['id'] ?>" class="action-btn" style="background:#dc3545;color:white;" onclick="return confirm('Delete this submission?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Exam Timetable Tab -->
        <div id="exam-timetable" class="tab-content">
            <h3>My Exam Timetable</h3>
            <?php if ($edit_exam_data): ?>
                <div style="background:#f8f9fa; border-radius:8px; padding:20px; margin-bottom:20px;">
                    <h4>Edit Exam Timetable Entry</h4>
                    <form method="POST">
                        <input type="hidden" name="edit_exam_id" value="<?= $edit_exam_data['id'] ?>">
                        <label>Subject:</label>
                        <input type="text" name="et_subject" value="<?= htmlspecialchars($edit_exam_data['subject']) ?>" required>
                        <label>Class:</label>
                        <input type="text" name="et_class" value="<?= htmlspecialchars($edit_exam_data['class']) ?>" required>
                        <label>Exam Date:</label>
                        <input type="date" name="et_exam_date" value="<?= htmlspecialchars($edit_exam_data['exam_date']) ?>" required>
                        <label>Start Time:</label>
                        <input type="time" name="et_start_time" value="<?= htmlspecialchars($edit_exam_data['start_time']) ?>" required>
                        <label>Duration:</label>
                        <input type="text" name="et_duration" value="<?= htmlspecialchars($edit_exam_data['duration']) ?>">
                        <label>Room:</label>
                        <input type="text" name="et_room" value="<?= htmlspecialchars($edit_exam_data['room']) ?>">
                        <button type="submit" name="update_exam_timetable">Update Exam Entry</button>
                        <a href="submit_exams.php#exam-timetable" style="margin-left:10px; color:#007bff;">Cancel</a>
                    </form>
                </div>
            <?php endif; ?>
            <?php
            $grouped_exams = [];
            foreach ($exam_timetable as $row) {
                $grouped_exams[$row['exam_date']][] = $row;
            }
            ksort($grouped_exams);
            if (empty($grouped_exams)) {
                echo '<div style="color:#888; text-align:center;">No exam timetable entries.</div>';
            } else {
                foreach ($grouped_exams as $date => $exams):
            ?>
                <h4 style="margin-top:2rem; color:#1e40af;"><?= htmlspecialchars($date) ?></h4>
                <table class="timetable exam-timetable-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Subject</th>
                            <th>Class</th>
                            <th>Duration</th>
                            <th>Room</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($exams as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars(substr($row['start_time'],0,5)) ?></td>
                            <td><?= htmlspecialchars($row['subject']) ?></td>
                            <td><?= htmlspecialchars($row['class']) ?></td>
                            <td><?= htmlspecialchars($row['duration']) ?></td>
                            <td><?= htmlspecialchars($row['room']) ?></td>
                            <td>
                                <a href="?edit_exam_timetable=<?= $row['id'] ?>#exam-timetable" class="action-btn edit">Edit</a>
                                <a href="?delete_exam_timetable=<?= $row['id'] ?>#exam-timetable" class="action-btn" style="background:#dc3545;color:white;" onclick="return confirm('Delete this exam entry?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; } ?>
            <div style="margin-top:2rem; text-align:right;">
                <button onclick="printTimetable('exam-timetable')" class="action-btn view">Print</button>
                <button onclick="downloadTimetablePDF('exam-timetable')" class="action-btn edit">Download as PDF</button>
            </div>
        </div>
        <!-- Class Schedule Tab -->
        <div id="class-schedule" class="tab-content">
            <h3>My Class Timetable</h3>
            <?php if ($edit_class_data): ?>
                <div style="background:#f8f9fa; border-radius:8px; padding:20px; margin-bottom:20px;">
                    <h4>Edit Class Timetable Entry</h4>
                    <form method="POST">
                        <input type="hidden" name="edit_class_id" value="<?= $edit_class_data['id'] ?>">
                        <label>Class:</label>
                        <input type="text" name="ct_class" value="<?= htmlspecialchars($edit_class_data['class']) ?>" required>
                        <label>Subject:</label>
                        <input type="text" name="ct_subject" value="<?= htmlspecialchars($edit_class_data['subject']) ?>" required>
                        <label>Day of Week:</label>
                        <select name="ct_day_of_week" required>
                            <?php $days = ['Monday','Tuesday','Wednesday','Thursday','Friday']; foreach ($days as $d): ?>
                                <option value="<?= $d ?>" <?= $edit_class_data['day_of_week']===$d?'selected':'' ?>><?= $d ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label>Start Time:</label>
                        <input type="time" name="ct_start_time" value="<?= htmlspecialchars($edit_class_data['start_time']) ?>" required>
                        <label>End Time:</label>
                        <input type="time" name="ct_end_time" value="<?= htmlspecialchars($edit_class_data['end_time']) ?>" required>
                        <label>Room:</label>
                        <input type="text" name="ct_room" value="<?= htmlspecialchars($edit_class_data['room']) ?>">
                        <button type="submit" name="update_class_timetable">Update Class Entry</button>
                        <a href="submit_exams.php#class-schedule" style="margin-left:10px; color:#007bff;">Cancel</a>
                    </form>
                </div>
            <?php endif; ?>
            <?php
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
            $grouped = [];
            foreach ($class_timetable as $row) {
                $grouped[$row['day_of_week']][] = $row;
            }
            foreach ($days as $day):
            ?>
                <h4 style="margin-top:2rem; color:#1e40af;"><?= $day ?></h4>
                <table class="timetable class-timetable-table">
                    <thead>
                        <tr>
                            <th>Start</th>
                            <th>End</th>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Room</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($grouped[$day])): ?>
                        <tr><td colspan="6" style="text-align:center; color:#888;">No classes scheduled.</td></tr>
                    <?php else: foreach ($grouped[$day] as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars(substr($row['start_time'],0,5)) ?></td>
                            <td><?= htmlspecialchars(substr($row['end_time'],0,5)) ?></td>
                            <td><?= htmlspecialchars($row['class']) ?></td>
                            <td><?= htmlspecialchars($row['subject']) ?></td>
                            <td><?= htmlspecialchars($row['room']) ?></td>
                            <td>
                                <a href="?edit_class_timetable=<?= $row['id'] ?>#class-schedule" class="action-btn edit">Edit</a>
                                <a href="?delete_class_timetable=<?= $row['id'] ?>#class-schedule" class="action-btn" style="background:#dc3545;color:white;" onclick="return confirm('Delete this class entry?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
            <div style="margin-top:2rem; text-align:right;">
                <button onclick="printTimetable('class-schedule')" class="action-btn view">Print</button>
                <button onclick="downloadTimetablePDF('class-schedule')" class="action-btn edit">Download as PDF</button>
            </div>
        </div>
        <!-- Timetable Management Tab -->
        <div id="timetable-management" class="tab-content">
            <h3>Timetable Management</h3>
            <div style="margin-bottom:2rem;">
                <h4>Add Exam Timetable Entry</h4>
                <form method="POST">
                    <label>Subject:</label>
                    <input type="text" name="et_subject" required>
                    <label>Class:</label>
                    <input type="text" name="et_class" required>
                    <label>Exam Date:</label>
                    <input type="date" name="et_exam_date" required>
                    <label>Start Time:</label>
                    <input type="time" name="et_start_time" required>
                    <label>Duration:</label>
                    <input type="text" name="et_duration" placeholder="e.g. 2 hours">
                    <label>Room:</label>
                    <input type="text" name="et_room">
                    <button type="submit" name="add_exam_timetable">Add Exam Entry</button>
                </form>
            </div>
            <div style="margin-bottom:2rem;">
                <h4>Add Class Timetable Entry</h4>
                <form method="POST">
                    <label>Class:</label>
                    <input type="text" name="ct_class" required>
                    <label>Subject:</label>
                    <input type="text" name="ct_subject" required>
                    <label>Day of Week:</label>
                    <select name="ct_day_of_week" required>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                    </select>
                    <label>Start Time:</label>
                    <input type="time" name="ct_start_time" required>
                    <label>End Time:</label>
                    <input type="time" name="ct_end_time" required>
                    <label>Room:</label>
                    <input type="text" name="ct_room">
                    <button type="submit" name="add_class_timetable">Add Class Entry</button>
                </form>
            </div>
        </div>
        <!-- Quick Stats Tab -->
        <div id="quick-stats" class="tab-content">
            <h3>Quick Statistics</h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <h4>Submissions This Term</h4>
                    <div class="stat-number"><?= $submissions_this_term ?></div>
                    <div class="stat-detail">Academic Year: <?= htmlspecialchars($academic_year) ?>, Term: <?= htmlspecialchars($term) ?></div>
                </div>
                <div class="stat-card">
                    <h4>Classes Teaching</h4>
                    <div class="stat-number"><?= $classes_teaching ?></div>
                    <div class="stat-detail">Unique classes in your timetable</div>
                </div>
                <div class="stat-card">
                    <h4>Upcoming Exams</h4>
                    <div class="stat-number"><?= $upcoming_exams ?></div>
                    <div class="stat-detail">Exams scheduled from today</div>
                </div>
                <div class="stat-card">
                    <h4>Weekly Teaching Hours</h4>
                    <div class="stat-number"><?= $weekly_hours ?></div>
                    <div class="stat-detail">Sum of all class periods per week</div>
                </div>
            </div>
            <div class="notifications">
                <h4>Recent Notifications</h4>
                <!-- Real notifications will be loaded here -->
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
    // Tab switching functionality
    function showTab(tabName) {
        // Hide all tab contents
        const tabContents = document.querySelectorAll('.tab-content');
        tabContents.forEach(tab => tab.classList.remove('active'));
        // Remove active class from all buttons
        const tabButtons = document.querySelectorAll('.tab-btn');
        tabButtons.forEach(btn => btn.classList.remove('active'));
        // Show selected tab and mark button as active
        document.getElementById(tabName).classList.add('active');
        event.target.classList.add('active');
    }
    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
        // Set default academic year
        const currentYear = new Date().getFullYear();
        const academicYearInput = document.getElementById('academic_year');
        academicYearInput.value = `${currentYear}/${currentYear + 1}`;
    });
    function printTimetable(tabId) {
        var tab = document.getElementById(tabId);
        var printContents = tab.innerHTML;
        var originalContents = document.body.innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        location.reload();
    }
    function downloadTimetablePDF(tabId) {
        var tab = document.getElementById(tabId);
        var doc = new window.jspdf.jsPDF('p', 'pt', 'a4');
        doc.html(tab, {
            callback: function (doc) {
                doc.save(tabId + '_timetable.pdf');
            },
            margin: [20, 20, 20, 20],
            autoPaging: 'text',
            x: 0,
            y: 0
        });
    }
</script>
</body>
</html>