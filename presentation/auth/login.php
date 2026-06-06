<?php
ob_start();
require_once __DIR__ . '/../../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new AuthController($conn);
    $result = $auth->validateLogin(
        $_POST['username'] ?? '',
        $_POST['password'] ?? '',
        $_POST['role'] ?? ''
    );

    if ($result['status'] === 'Success') {
        if ($result['role'] === 'patient') {
            ensurePatientRefLink($conn, $_SESSION['username']);
        } elseif ($result['role'] === 'doctor') {
            ensureDoctorRefLink($conn, $_SESSION['username']);
        }
        AuthController::redirectForRole($result['role']);
    }

    $_SESSION['login_error'] = $result['message'];
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SGHMS Login</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(pres_asset('css/login.css')) ?>">
</head>
<body>

<div class="container">
    <div class="card">
        <h1>Sarawak General Hospital</h1>
        <p class="subtitle">Hospital Management System</p>

        <div class="tabs">
            <button class="tab active">Login</button>
            <a href="register.php" class="tab">Register</a>
        </div>

        <?php if (isset($_SESSION['login_error'])): ?>
            <p style="color:red; text-align:center; margin-bottom:10px;">
                <?= htmlspecialchars($_SESSION['login_error']) ?>
            </p>
            <?php unset($_SESSION['login_error']); ?>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <label>Role</label>
            <select name="role" required>
                <option value="">-- Select Role --</option>
                <option value="patient">Patient</option>
                <option value="doctor">Doctor</option>
                <option value="staff">Staff</option>
            </select>

            <label>Username</label>
            <input type="text" name="username" placeholder="Enter your username" required>

            <label>Password</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="password" required>
                <button type="button" class="toggle-password" onclick="togglePassword()"></button>
            </div>

            <button type="submit" class="login-btn">Login</button>
        </form>
    </div>
</div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.querySelector('.toggle-password');
    passwordInput.type = (passwordInput.type === 'password') ? 'text' : 'password';
    toggleBtn.classList.toggle('showing');
}
</script>

</body>
</html>
