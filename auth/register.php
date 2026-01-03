<?php
require_once "../../db/dbcon.php";

$message = "";
$msg_class = "";

if (isset($_POST['register'])) {

    $username = trim($_POST['username'] ?? '');
    $password_raw = $_POST['password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');

    if ($username === '' || $password_raw === '' || $email === '' || $role === '' || $fullname === '') {
        $message = "All fields are required";
        $msg_class = "error";
    } else {

        $password = password_hash($password_raw, PASSWORD_DEFAULT);

        $check = $conn->prepare("SELECT username FROM user WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "Username already exists";
            $msg_class = "error";
        } else {

            $insert = $conn->prepare(
                "INSERT INTO user (username, password, email, role, fullname)
                 VALUES (?, ?, ?, ?)"
            );

            $insert->bind_param(
                "sssss",
                $username,
                $password,
                $email,
                $role,
                $fullname
            );

            if ($insert->execute()) {
                $message = "Registration successful. Please login.";
                $msg_class = "success";
            } else {
                $message = "Registration failed.";
                $msg_class = "error";
            }

            $insert->close();
        }

        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SGHMS | Register</title>
    <link rel="stylesheet" href="../../css/register.css">
</head>
<body>

<div class="container">
    <div class="card">

        <h1>Sarawak General Hospital</h1>
        <p class="subtitle">Hospital Management System</p>

        <div class="tabs">
            <a href="login.php" class="tab">Login</a>
            <div class="tab active">Register</div>
        </div>

        <form method="POST">

            <?php if ($message): ?>
                <p class="message <?= $msg_class ?>">
                    <?= htmlspecialchars($message) ?>
                </p>
            <?php endif; ?>

            <label>Role</label>
            <select name="role" required>
                <option value="">Select role</option>
                <option value="patient">Patient</option>
                <option value="doctor">Doctor</option>
                <option value="staff">Staff</option>
            </select>

            <label>Username</label>
            <input type="text" name="username" required>

            <label>Full Name</label>
            <input type="text" name="fullname" required>

            <label>Email</label>
            <input type="email" name="email" required>

            <label>Password</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="password" required>
                <button type="button" class="toggle-password" onclick="togglePassword()">
                    <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path class="eye-open" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle class="eye-open" cx="12" cy="12" r="3"></circle>
                        <path class="eye-closed" d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                        <line class="eye-closed" x1="1" y1="1" x2="23" y2="23"></line>
                    </svg>
                </button>
            </div>

            <button type="submit" name="register" class="action-btn">
                Register
            </button>

        </form>

    </div>
</div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.querySelector('.toggle-password');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleBtn.classList.add('showing');
    } else {
        passwordInput.type = 'password';
        toggleBtn.classList.remove('showing');
    }
}
</script>

</body>
</html>