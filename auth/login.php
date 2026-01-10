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

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active">Login</button>
            <a href="register.php" class="tab">Register</a>
        </div>

        <form action="login_process.php" method="POST">

            <label>Role</label>
            <select name="role" required>
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
