<?php
ob_start();
require_once "../db/dbcon.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';
    $selectedRole = strtolower(trim($_POST['role'] ?? ''));

    // 1. Fetch user from DB
    $stmt = $conn->prepare("SELECT username, password, role, fullname FROM user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $dbRole = strtolower(trim($user['role']));

        // 2. Verify Password
        if (password_verify($password, $user['password'])) {

            // 3. Role validation (Matches dropdown selection)
            if ($selectedRole !== $dbRole) {
                $_SESSION['login_error'] = "Role mismatch: This account is registered as a " . ucfirst($dbRole);
                header("Location: login.php");
                exit;
            }

            // 4. Set Sessions
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $dbRole;
            $_SESSION['fullname'] = $user['fullname'];

            // 5. Redirect based on role
            switch ($dbRole) {
                case "staff":
                    header("Location: ../appointment/manageAppointment.php");
                    break;

                case "doctor":
                    // ✅ FIXED: correct redirect to doctor module
                    header("Location: ../modules/doctor/dashboard.php");
                    break;

                case "patient":
                    header("Location: ../patient/patientDashboard.php");
                    break;

                default:
                    header("Location: login.php");
            }
            exit;
        }
    }

    // Generic error for security
    $_SESSION['login_error'] = "Invalid username or password";
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SGHMS Login</title>
    <link rel="stylesheet" href="../css/login.css">
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