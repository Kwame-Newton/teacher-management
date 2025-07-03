<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Teacher Management System</title>
  <link href="https://fonts.googleapis.com/css2?family=Rubik&display=swap" rel="stylesheet">
  <style>
    body {
      margin: 0;
      font-family: 'Rubik', sans-serif;
      background: linear-gradient(rgba(0, 0, 50, 0.7), rgba(0, 0, 80, 0.7)), url('https://images.unsplash.com/photo-1577896851231-70ef18881754?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
      color: #111827;
    }
    .container {
      background-color: rgba(255, 255, 255, 0.95);
      padding: 30px;
      border-radius: 15px;
      max-width: 400px;
      width: 90%;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
    }
    .container img {
      display: block;
      margin: 0 auto 10px;
      width: 60px;
      height: 60px;
    }
    .container h2 {
      text-align: center;
      color: #1e40af;
    }
    .container p {
      text-align: center;
      color: #6b7280;
      font-size: 14px;
    }
    .form-group {
      margin-bottom: 15px;
    }
    .form-group label {
      display: block;
      font-weight: bold;
      margin-bottom: 5px;
      color: #374151;
    }
    .form-group input {
      width: 100%;
      padding: 10px;
      border: 1px solid #d1d5db;
      border-radius: 5px;
      font-size: 16px;
    }
    .btn {
      width: 100%;
      background-color: #2563eb;
      color: white;
      padding: 10px;
      font-weight: bold;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    .btn:hover {
      background-color: #1d4ed8;
    }
    .register-link, .footer {
      text-align: center;
      margin-top: 15px;
      font-size: 14px;
      color: #6b7280;
    }
    .register-link a {
      color: #2563eb;
      text-decoration: none;
      font-weight: bold;
    }
    .register-link a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="container">
    <img src="https://cdn-icons-png.flaticon.com/512/3135/3135773.png" alt="Teacher Icon">
    <h2>Welcome to the West-End International School</h2>
    <p>Login to continue</p>

    <form action="auth/login.php" method="POST">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>
      <button type="submit" class="btn">Login</button>
    </form>

    <div class="register-link">
      Don't have an account? <a href="register.php">Register</a>
    </div>

    <div class="footer">
      &copy; 2025 Teacher Management System. All rights reserved.
    </div>
  </div>
</body>
</html>
