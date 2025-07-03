<?php
session_start();
require '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'headmaster') {
    header("Location: ../index.php");
    exit();
}

// Approve/Reject logic
if (isset($_GET['action'], $_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE exam_submissions SET status = 'Approved' WHERE id = ?");
        $stmt->execute([$id]);
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE exam_submissions SET status = 'Rejected' WHERE id = ?");
        $stmt->execute([$id]);
    }
    header("Location: submitted_exams.php");
    exit();
}

// Fetch all submissions with teacher info
$stmt = $conn->query("SELECT es.*, u.full_name FROM exam_submissions es JOIN users u ON es.teacher_id = u.id ORDER BY es.created_at DESC");
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// View details
$view_data = null;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $stmt = $conn->prepare("SELECT es.*, u.full_name FROM exam_submissions es JOIN users u ON es.teacher_id = u.id WHERE es.id = ?");
    $stmt->execute([intval($_GET['view'])]);
    $view_data = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submitted Exams</title>
    <link href="https://fonts.googleapis.com/css2?family=Rubik&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Rubik', sans-serif;
            background: #f4f8fb;
            margin: 0;
            min-height: 100vh;
        }
        .sidebar {
            width: 220px;
            background: #1e40af;
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
            font-weight: normal;
            color: white;
            font-family: 'Rubik', sans-serif;
        }
        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            margin: 15px 0;
            padding: 10px;
            border-radius: 5px;
        }
        .sidebar a.active, .sidebar a:hover {
            background: #3749c1;
            color: white;
        }
        .main {
            margin-left: 240px;
            padding: 0;
            min-height: 100vh;
            background: #f4f8fb;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 32px 40px 0 40px;
        }
        .topbar h1, h2, h3 {
            font-family: 'Rubik', sans-serif;
            font-weight: normal;
        }
        .topbar h1 {
            font-size: 28px;
            color: #1e3a8a;
        }
        .logout-btn {
            background: #ef4444;
            color: #fff;
            border: none;
            padding: 10px 22px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        .logout-btn:hover {
            background: #dc2626;
        }
        .main-content {
            max-width: 1200px;
            margin: 32px auto 0 auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(30,64,175,0.07);
            padding: 36px 32px 32px 32px;
        }
        .dashboard-tabs {
            display: flex;
            gap: 0;
            border-bottom: 2px solid #e5eaf2;
            margin-bottom: 32px;
        }
        .tab-btn {
            background: none;
            border: none;
            outline: none;
            font-size: 18px;
            font-weight: normal;
            color: #6b7280;
            padding: 16px 36px 14px 36px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: color 0.2s, border-bottom 0.2s;
        }
        .tab-btn.active {
            color: #2563eb;
            border-bottom: 3px solid #2563eb;
            background: #f4f8fb;
        }
        .tab-content {
            display: none;
            animation: fadeIn 0.3s;
        }
        .tab-content.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        h2 {
            font-size: 22px;
            color: #1e3a8a;
            margin-bottom: 18px;
        }
        h3 {
            font-size: 18px;
            color: #2563eb;
            margin-top: 32px;
            margin-bottom: 12px;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 24px;
            background: #f9fbfd;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(30,64,175,0.04);
        }
        th, td {
            padding: 14px 12px;
            text-align: left;
        }
        th {
            background: #e5eaf2;
            color: #1e3a8a;
            font-weight: normal;
            font-size: 15px;
        }
        tr:not(:last-child) td {
            border-bottom: 1px solid #e5eaf2;
        }
        .status {
            padding: 5px 14px;
            border-radius: 16px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }
        .status.Approved { background: #d1fae5; color: #065f46; }
        .status.Pending { background: #fef3c7; color: #92400e; }
        .status.Rejected { background: #fee2e2; color: #991b1b; }
        .action-btn {
            padding: 6px 18px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: normal;
            cursor: pointer;
            text-decoration: none;
            margin-right: 6px;
            transition: background 0.2s, color 0.2s;
        }
        .action-btn.view { background: #2563eb; color: #fff; }
        .action-btn.approve { background: #10b981; color: #fff; }
        .action-btn.reject { background: #ef4444; color: #fff; }
        .action-btn:hover { opacity: 0.85; }
        .details-box {
            background: #f4f8fb;
            border-radius: 12px;
            padding: 24px 20px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(30,64,175,0.04);
        }
        .details-box pre {
            background: #fff;
            border: 1px solid #e5eaf2;
            border-radius: 8px;
            padding: 12px;
            font-size: 15px;
        }
        form label, form select {
            font-size: 16px;
            font-weight: normal;
            color: #1e3a8a;
        }
        form select {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #e5eaf2;
            margin-left: 8px;
            margin-bottom: 18px;
        }
        @media (max-width: 900px) {
            .main-content { padding: 18px 6px; }
            .topbar { padding: 24px 10px 0 10px; }
            .sidebar { width: 100px; padding: 18px 6px; }
            .main { margin-left: 100px; }
            .tab-btn { font-size: 15px; padding: 10px 10px; }
        }
        @media (max-width: 600px) {
            .main-content { padding: 6px 2px; }
            .sidebar { display: none; }
            .main { margin-left: 0; }
            .topbar { padding: 12px 2px 0 2px; }
        }
        .main-content, .details-box, table, .tab-btn, .action-btn {
            font-family: 'Rubik', sans-serif;
            font-weight: normal;
        }
    </style>
</head>
<body>
<div class="sidebar">
    <h2>TMS Headmaster</h2>
    <a href="headmaster.php">Dashboard</a>
    <a href="view_teachers.php">View Teachers</a>
    <a href="message.php">Messages</a>
    <a href="attendance_records.php">Attendance Records</a>
    <a href="submitted_exams.php" class="active">Submitted Exams</a>
    <a href="#">Settings</a>
</div>
<div class="main">
    <div class="topbar">
        <h1>Submitted Exams</h1>
        <form action="../logout.php" method="POST">
            <button class="logout-btn" type="submit">Logout</button>
        </form>
    </div>
    <div class="main-content">
        <div class="dashboard-tabs">
            <button class="tab-btn active" onclick="showTab('exams')">Submitted Exams</button>
            <button class="tab-btn" onclick="showTab('timetables')">Teacher Timetables</button>
            <button class="tab-btn" onclick="showTab('examstimetable')">Exams Timetable</button>
        </div>
        <!-- Submitted Exams Tab -->
        <div id="exams" class="tab-content active">
        <h2>All Submitted Exams</h2>
        <?php
        // Group submissions by teacher
        $grouped = [];
        foreach ($submissions as $row) {
            $grouped[$row['full_name']][] = $row;
        }
        ?>
        <?php if (empty($grouped)): ?>
            <div style="text-align:center; color:#888;">No submissions yet.</div>
        <?php else: foreach ($grouped as $teacher_name => $teacher_subs): ?>
            <h3 style="margin-top:2rem; color:#1e40af;"><?= htmlspecialchars($teacher_name) ?></h3>
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
                <?php foreach ($teacher_subs as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['subject']) ?></td>
                        <td><?= htmlspecialchars($row['class']) ?></td>
                        <td><?= htmlspecialchars($row['term']) ?></td>
                        <td><span class="status <?= htmlspecialchars($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                        <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                        <td>
                            <a href="?view=<?= $row['id'] ?>" class="action-btn view">View</a>
                            <?php if ($row['status'] === 'Pending'): ?>
                                <a href="?action=approve&id=<?= $row['id'] ?>" class="action-btn approve" onclick="return confirm('Approve this submission?')">Approve</a>
                                <a href="?action=reject&id=<?= $row['id'] ?>" class="action-btn reject" onclick="return confirm('Reject this submission?')">Reject</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; endif; ?>
        <?php if ($view_data): ?>
            <div class="details-box">
                <h3>Submission Details</h3>
                <b>Teacher:</b> <?= htmlspecialchars($view_data['full_name']) ?><br>
                <b>Subject:</b> <?= htmlspecialchars($view_data['subject']) ?><br>
                <b>Class:</b> <?= htmlspecialchars($view_data['class']) ?><br>
                <b>Exam Type:</b> <?= htmlspecialchars($view_data['exam_type']) ?><br>
                <b>Term:</b> <?= htmlspecialchars($view_data['term']) ?><br>
                <b>Academic Year:</b> <?= htmlspecialchars($view_data['academic_year']) ?><br>
                <b>Exam Date:</b> <?= htmlspecialchars($view_data['exam_date']) ?><br>
                <b>Status:</b> <span class="status <?= htmlspecialchars($view_data['status']) ?>"><?= htmlspecialchars($view_data['status']) ?></span><br>
                <b>Notes:</b> <?= nl2br(htmlspecialchars($view_data['notes'])) ?><br>
                <b>Question File:</b> <?php if ($view_data['question_file']): ?><a href="../uploads/<?= htmlspecialchars($view_data['question_file']) ?>" target="_blank">Download</a><?php else: ?>None<?php endif; ?><br>
                <b>Marking Scheme:</b> <?php if ($view_data['marking_scheme']): ?><a href="../uploads/<?= htmlspecialchars($view_data['marking_scheme']) ?>" target="_blank">Download</a><?php else: ?>None<?php endif; ?><br>
                <b>Typed Questions:</b><br><pre><?= htmlspecialchars($view_data['typed_questions']) ?></pre>
                <a href="submitted_exams.php" style="display:inline-block; margin-top:10px; color:#007bff;">Close</a>
            </div>
        <?php endif; ?>
        </div>
        <!-- Teacher Timetables Tab -->
        <div id="timetables" class="tab-content">
            <h2>Teacher Timetables</h2>
            <?php
            // Fetch all teachers for dropdown
            $teachers = $conn->query("SELECT id, full_name FROM users WHERE role = 'teacher' ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
            $selected_teacher_id = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : ($teachers[0]['id'] ?? null);
            ?>
            <form method="get" style="margin-bottom:20px;">
                <label for="teacher_id"><b>Select Teacher:</b></label>
                <select name="teacher_id" id="teacher_id" onchange="this.form.submit()">
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $selected_teacher_id==$t['id']?'selected':'' ?>><?= htmlspecialchars($t['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php
            if ($selected_teacher_id) {
                // Fetch class timetable
                $class_timetable = $conn->prepare("SELECT * FROM class_timetable WHERE teacher_id = ? ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday'), start_time");
                $class_timetable->execute([$selected_teacher_id]);
                $class_timetable = $class_timetable->fetchAll(PDO::FETCH_ASSOC);
                // Fetch exam timetable
                $exam_timetable = $conn->prepare("SELECT * FROM exam_timetable WHERE teacher_id = ? ORDER BY exam_date, start_time");
                $exam_timetable->execute([$selected_teacher_id]);
                $exam_timetable = $exam_timetable->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <h3>Class Timetable</h3>
            <table>
                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Class</th>
                        <th>Subject</th>
                        <th>Room</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($class_timetable)): ?>
                    <tr><td colspan="6" style="text-align:center; color:#888;">No classes scheduled.</td></tr>
                <?php else: foreach ($class_timetable as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['day_of_week']) ?></td>
                        <td><?= htmlspecialchars(substr($row['start_time'],0,5)) ?></td>
                        <td><?= htmlspecialchars(substr($row['end_time'],0,5)) ?></td>
                        <td><?= htmlspecialchars($row['class']) ?></td>
                        <td><?= htmlspecialchars($row['subject']) ?></td>
                        <td><?= htmlspecialchars($row['room']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
            <h3 style="margin-top:2rem;">Exam Timetable</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Start</th>
                        <th>Class</th>
                        <th>Subject</th>
                        <th>Duration</th>
                        <th>Room</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($exam_timetable)): ?>
                    <tr><td colspan="6" style="text-align:center; color:#888;">No exams scheduled.</td></tr>
                <?php else: foreach ($exam_timetable as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['exam_date']) ?></td>
                        <td><?= htmlspecialchars(substr($row['start_time'],0,5)) ?></td>
                        <td><?= htmlspecialchars($row['class']) ?></td>
                        <td><?= htmlspecialchars($row['subject']) ?></td>
                        <td><?= htmlspecialchars($row['duration']) ?></td>
                        <td><?= htmlspecialchars($row['room']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
            <?php } ?>
        </div>
        <!-- Exams Timetable Tab -->
        <div id="examstimetable" class="tab-content">
            <h2>Exams Timetable</h2>
            <?php
            // Fetch all exam timetable entries, join with teacher name
            $all_exams = $conn->query("SELECT et.*, u.full_name FROM exam_timetable et JOIN users u ON et.teacher_id = u.id ORDER BY et.exam_date, et.start_time")->fetchAll(PDO::FETCH_ASSOC);
            $grouped_exams = [];
            foreach ($all_exams as $exam) {
                $grouped_exams[$exam['exam_date']][] = $exam;
            }
            ?>
            <?php if (empty($grouped_exams)): ?>
                <div style="text-align:center; color:#888;">No exams scheduled.</div>
            <?php else: foreach ($grouped_exams as $date => $exams): ?>
                <h3 style="margin-top:2rem; color:#1e40af;"><?= htmlspecialchars($date) ?></h3>
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Teacher</th>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Duration</th>
                            <th>Room</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($exams as $exam): ?>
                        <tr>
                            <td><?= htmlspecialchars(substr($exam['start_time'],0,5)) ?></td>
                            <td><?= htmlspecialchars($exam['full_name']) ?></td>
                            <td><?= htmlspecialchars($exam['class']) ?></td>
                            <td><?= htmlspecialchars($exam['subject']) ?></td>
                            <td><?= htmlspecialchars($exam['duration']) ?></td>
                            <td><?= htmlspecialchars($exam['room']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>
<script>
    // Tab switching functionality
    function showTab(tabName) {
        const tabContents = document.querySelectorAll('.tab-content');
        tabContents.forEach(tab => tab.classList.remove('active'));
        const tabButtons = document.querySelectorAll('.tab-btn');
        tabButtons.forEach(btn => btn.classList.remove('active'));
        document.getElementById(tabName).classList.add('active');
        event.target.classList.add('active');
    }
</script>
</body>
</html> 