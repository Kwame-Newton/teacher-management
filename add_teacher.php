<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'headmaster') {
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add New Teacher</title>
  <link href="https://fonts.googleapis.com/css2?family=Rubik&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Rubik', sans-serif;
      background-color: #f4f6f8;
      margin: 0;
      padding: 0;
    }

    .container {
      width: 90%;
      max-width: 700px;
      margin: 30px auto;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    h2 {
      text-align: center;
      color: #1e3a8a;
      margin-bottom: 20px;
    }

    .step {
      display: none;
    }

    .step.active {
      display: block;
    }

    label {
      display: block;
      margin-top: 15px;
      font-weight: 500;
    }

    input, select {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border: 1px solid #d1d5db;
      border-radius: 5px;
    }

    .buttons {
      margin-top: 20px;
      display: flex;
      justify-content: space-between;
    }

    button {
      background-color: #1e40af;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
    }

    button:hover {
      background-color: #3749c1;
    }

    .submit-btn {
      background-color: #10b981;
    }

    .submit-btn:hover {
      background-color: #059669;
    }
  </style>
</head>
<body>

<div class="container">
  <h2>Add New Teacher</h2>
  <form action="../auth/register.php" method="POST" enctype="multipart/form-data" id="teacherForm">
    <!-- Step 1: Personal Info -->
    <div class="step active" id="step1">
      <label>Full Name</label>
      <input type="text" name="full_name" required>

      <label>Username</label>
      <input type="text" name="username" required>

      <label>Password</label>
      <input type="password" name="password" required>

      <label>Phone</label>
      <input type="text" name="phone">

      <label>Email</label>
      <input type="email" name="email">

      <label>Gender</label>
      <select name="gender">
        <option value="">-- Select Gender --</option>
        <option>Male</option>
        <option>Female</option>
      </select>

      <label>Date of Birth</label>
      <input type="date" name="date_of_birth">

      <label>Profile Picture (optional)</label>
      <input type="file" name="profile_picture">

      <div class="buttons">
        <span></span>
        <button type="button" onclick="nextStep()">Next</button>
      </div>
    </div>

    <!-- Step 2: Employment Info -->
    <div class="step" id="step2">
      <label>Address</label>
      <input type="text" name="address">

      <label>Assigned Class</label>
      <input type="text" name="assigned_class">

      <label>Qualification</label>
      <input type="text" name="qualification">

      <label>Years of Experience</label>
      <input type="number" name="years_of_experience">

      <label>Subjects Taught</label>
      <input type="text" name="subjects_taught">

      <label>Date Employed</label>
      <input type="date" name="date_employed">

      <label>Employment Type</label>
      <select name="employment_type">
        <option value="">-- Select Type --</option>
        <option value="Permanent">Permanent</option>
        <option value="Contract">Contract</option>
      </select>

      <input type="hidden" name="role" value="teacher">

      <div class="buttons">
        <button type="button" onclick="prevStep()">Back</button>
        <button type="submit" class="submit-btn">Register</button>
      </div>
    </div>
  </form>
</div>

<script>
  let currentStep = 1;

  function nextStep() {
    document.getElementById(`step${currentStep}`).classList.remove('active');
    currentStep++;
    document.getElementById(`step${currentStep}`).classList.add('active');
  }

  function prevStep() {
    document.getElementById(`step${currentStep}`).classList.remove('active');
    currentStep--;
    document.getElementById(`step${currentStep}`).classList.add('active');
  }
</script>

</body>
</html>
