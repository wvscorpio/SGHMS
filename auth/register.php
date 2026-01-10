<?php
require_once "../db/dbcon.php";

$message = "";
$msg_class = "";

if (isset($_POST['register'])) {

    // ✅ NORMALIZE INPUT
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password_raw = $_POST['password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $role = strtolower(trim($_POST['role'] ?? ''));
    $fullname = trim($_POST['fullname'] ?? '');

    if ($username === '' || $password_raw === '' || $email === '' || $role === '' || $fullname === '') {
        $message = "All fields are required";
        $msg_class = "error";
    } else {

        // ✅ HASH PASSWORD ONCE (CORRECT)
        $password = password_hash($password_raw, PASSWORD_DEFAULT);

        // ✅ CHECK USERNAME
        $check = $conn->prepare("SELECT username FROM user WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "Username already exists";
            $msg_class = "error";
        } else {

            // ✅ INSERT CLEAN DATA
            $insert = $conn->prepare(
                "INSERT INTO user (username, password, email, role, fullname)
                 VALUES (?, ?, ?, ?, ?)"
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
    <link rel="stylesheet" href="../css/register.css">
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
                <button type="button" class="toggle-password" onclick="togglePassword()"></button>
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