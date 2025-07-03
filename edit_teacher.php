<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'headmaster') {
    header("Location: ../index.php");
    exit();
}

$teacherId = $_GET['id'] ?? null;
if (!$teacherId) {
    die("Invalid request.");
}

// Fetch teacher data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'teacher'");
$stmt->execute([$teacherId]);
$teacher = $stmt->fetch();

if (!$teacher) {
    die("Teacher not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $username = $_POST['username'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $address = $_POST['address'] ?? '';
    $assigned_class = $_POST['assigned_class'] ?? '';
    $qualification = $_POST['qualification'] ?? '';
    $years_of_experience = $_POST['years_of_experience'] ?? '';
    $subjects_taught = $_POST['subjects_taught'] ?? '';
    $date_employed = $_POST['date_employed'] ?? '';
    $employment_type = $_POST['employment_type'] ?? '';
    $status = $_POST['status'] ?? 'Active';

    // Handle password update
    $passwordClause = '';
    $params = [
        $full_name, $username, $phone, $email, $gender, $date_of_birth,
        $address, $assigned_class, $qualification, $years_of_experience,
        $subjects_taught, $date_employed, $employment_type, $status
    ];

    if (!empty($_POST['password'])) {
        $hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $passwordClause = ", password = ?";
        $params[] = $hashed;
    }

    // Handle profile picture update
    $pictureClause = '';
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $target_dir = "../uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir);
        $file_name = uniqid() . "_" . basename($_FILES["profile_picture"]["name"]);
        $target_file = $target_dir . $file_name;
        move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file);
        $pictureClause = ", profile_picture = ?";
        $params[] = $file_name;
    }

    $params[] = $teacherId;

    $sql = "
        UPDATE users SET 
        full_name = ?, username = ?, phone = ?, email = ?, gender = ?, date_of_birth = ?, address = ?, 
        assigned_class = ?, qualification = ?, years_of_experience = ?, subjects_taught = ?, 
        date_employed = ?, employment_type = ?, status = ?
        $passwordClause
        $pictureClause
        WHERE id = ?
    ";

    $update = $conn->prepare($sql);
    $update->execute($params);

    header("Location: view_teachers.php?updated=true");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Teacher</title>
  <link href="https://fonts.googleapis.com/css2?family=Rubik&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Rubik', sans-serif;
      margin: 0;
      background-color: #f4f6f8;
      display: flex;
      height: 100vh;
    }
    .sidebar {
      width: 220px;
      background-color: #1e40af;
      color: white;
      padding: 20px;
    }
    .sidebar h2 {
      font-size: 20px;
      margin-bottom: 30px;
    }
    .sidebar a {
      display: block;
      color: white;
      text-decoration: none;
      margin: 15px 0;
      padding: 10px;
      border-radius: 5px;
    }
    .sidebar a:hover {
      background-color: #3749c1;
    }
    .main {
      flex: 1;
      padding: 30px;
      overflow-y: auto;
    }
    h1 {
      color: #1e3a8a;
    }
    form {
      background-color: white;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      max-width: 700px;
    }
    .form-group {
      margin-bottom: 15px;
    }
    label {
      display: block;
      font-weight: bold;
      margin-bottom: 5px;
    }
    input, select {
      width: 100%;
      padding: 10px;
      border-radius: 6px;
      border: 1px solid #ccc;
    }
    button {
      background-color: #2563eb;
      color: white;
      padding: 12px 20px;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
      margin-top: 15px;
    }
    button:hover {
      background-color: #1e40af;
    }
  </style>
</head>
<body>

  <div class="sidebar">
    <h2>TMS Headmaster</h2>
    <a href="headmaster.php">Dashboard</a>
    <a href="view_teachers.php">View Teachers</a>
    <a href="#">Attendance Records</a>
    <a href="attendance_records.php">Attendance Records</a>
    <a href="#">Submitted Exams</a>
    <a href="#">Teaching Notes</a>
    <a href="#">Payroll</a>
    <a href="#">Performance</a>
    <a href="#">Messages</a>
    <a href="#">Settings</a>
  </div>

  <div class="main">
    <h1>Edit Teacher Profile</h1>
    <form method="POST" enctype="multipart/form-data">
      <div class="form-group"><label>Full Name</label><input type="text" name="full_name" value="<?= htmlspecialchars($teacher['full_name']) ?>" required></div>
      <div class="form-group"><label>Username</label><input type="text" name="username" value="<?= htmlspecialchars($teacher['username']) ?>" required></div>
      <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?= htmlspecialchars($teacher['phone']) ?>"></div>
      <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($teacher['email']) ?>"></div>
      <div class="form-group"><label>Gender</label>
        <select name="gender">
          <option value="male" <?= $teacher['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
          <option value="female" <?= $teacher['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
          <option value="other" <?= $teacher['gender'] === 'other' ? 'selected' : '' ?>>Other</option>
        </select>
      </div>
      <div class="form-group"><label>Date of Birth</label><input type="date" name="date_of_birth" value="<?= $teacher['date_of_birth'] ?>"></div>
      <div class="form-group"><label>Address</label><input type="text" name="address" value="<?= htmlspecialchars($teacher['address']) ?>"></div>
      <div class="form-group"><label>Assigned Class</label><input type="text" name="assigned_class" value="<?= htmlspecialchars($teacher['assigned_class']) ?>"></div>
      <div class="form-group"><label>Qualification</label><input type="text" name="qualification" value="<?= htmlspecialchars($teacher['qualification']) ?>"></div>
      <div class="form-group"><label>Years of Experience</label><input type="number" name="years_of_experience" value="<?= htmlspecialchars($teacher['years_of_experience']) ?>"></div>
      <div class="form-group"><label>Subjects Taught</label><input type="text" name="subjects_taught" value="<?= htmlspecialchars($teacher['subjects_taught']) ?>"></div>
      <div class="form-group"><label>Date Employed</label><input type="date" name="date_employed" value="<?= $teacher['date_employed'] ?>"></div>
      <div class="form-group"><label>Employment Type</label>
        <select name="employment_type">
          <option value="permanent" <?= $teacher['employment_type'] === 'permanent' ? 'selected' : '' ?>>Permanent</option>
          <option value="part-time" <?= $teacher['employment_type'] === 'part-time' ? 'selected' : '' ?>>Part-time</option>
          <option value="contract" <?= $teacher['employment_type'] === 'contract' ? 'selected' : '' ?>>Contract</option>
        </select>
      </div>
      <div class="form-group"><label>Status</label>
        <select name="status">
          <option value="Active" <?= $teacher['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
          <option value="Inactive" <?= $teacher['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
      <div class="form-group"><label>Change Password (leave blank to keep current)</label><input type="password" name="password"></div>
      <div class="form-group"><label>Profile Picture (optional)</label><input type="file" name="profile_picture"></div>
      <button type="submit">Save Changes</button>
    </form>
  </div>

</body>
</html>
