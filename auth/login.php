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
                <button type="button" class="toggle-password" onclick="togglePassword()">
                    <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path class="eye-open" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle class="eye-open" cx="12" cy="12" r="3"></circle>
                        <path class="eye-closed" d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                        <line class="eye-closed" x1="1" y1="1" x2="23" y2="23"></line>
                    </svg>
                </button>
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
