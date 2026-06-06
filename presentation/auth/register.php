<?php
require_once __DIR__ . '/../../includes/init.php';

$message = '';
$msg_class = '';

if (isset($_POST['register'])) {
    $auth = new AuthController($conn);
    $contactNumber = normalizeContactNumber($_POST['contactNumber'] ?? '');
    $result = $auth->register([
        'username' => $_POST['username'] ?? '',
        'password' => $_POST['password'] ?? '',
        'contactNumber' => $contactNumber,
        'role' => $_POST['role'] ?? '',
        'fullname' => $_POST['fullname'] ?? '',
    ]);

    $message = $result['message'];
    $msg_class = ($result['status'] === 'Success') ? 'success' : 'error';

    if ($result['status'] === 'Success') {
        $username = strtolower(trim($_POST['username'] ?? ''));
        $userModel = new UserModel($conn);
        $role = strtolower(trim($_POST['role'] ?? ''));

        if ($role === 'patient') {
            $patientModel = new PatientModel($conn);
            $patientResult = $patientModel->validateAndCreate([
                'name' => trim($_POST['fullname'] ?? ''),
                'age' => 1,
                'gender' => 'Not specified',
                'contactNumber' => $contactNumber,
                'medicalHistory' => 'None recorded',
            ]);
            if ($patientResult['status'] === 'Success') {
                $userModel->updateRefId($username, $patientResult['patientID']);
            } else {
                $patientResult = $patientModel->validateAndCreate([
                    'name' => trim($_POST['fullname'] ?? '') . ' (' . $username . ')',
                    'age' => 1,
                    'gender' => 'Not specified',
                    'contactNumber' => $contactNumber,
                    'medicalHistory' => 'None recorded',
                ]);
                if ($patientResult['status'] === 'Success') {
                    $userModel->updateRefId($username, $patientResult['patientID']);
                }
            }
        }

        if ($role === 'doctor') {
            $doctorModel = new DoctorModel($conn);
            $doctorResult = $doctorModel->createFromRegistration(
                trim($_POST['fullname'] ?? ''),
                $contactNumber
            );
            if ($doctorResult['status'] === 'Success') {
                $userModel->updateRefId($username, $doctorResult['doctorID']);
            }
        }
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

            <label>Contact Number</label>
            <input type="tel" name="contactNumber" id="contactNumber"
                   inputmode="numeric" pattern="0[0-9]{9,10}"
                   maxlength="11" placeholder="e.g. 0123456789" required>
            <small class="field-hint">Numbers only. Malaysian format: 10–11 digits starting with 0.</small>

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

const contactInput = document.getElementById('contactNumber');
contactInput.addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 11);
});
contactInput.addEventListener('keydown', function (e) {
    const allowed = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End'];
    if (allowed.includes(e.key)) {
        return;
    }
    if (!/^\d$/.test(e.key)) {
        e.preventDefault();
    }
});
</script>

</body>
</html>
