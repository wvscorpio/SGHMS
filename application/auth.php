<?php

function requireLogin(): void
{
    if (empty($_SESSION['username']) || empty($_SESSION['role'])) {
        header('Location: ' . login_redirect_path());
        exit;
    }
}

function requireRole(string ...$roles): void
{
    requireLogin();
    if (!in_array($_SESSION['role'], $roles, true)) {
        http_response_code(403);
        die('Access denied.');
    }
}

function login_redirect_path(): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (strpos($script, '/application/') !== false) {
        return '../../presentation/auth/login.php';
    }
    if (strpos($script, '/presentation/modules/') !== false) {
        return '../../auth/login.php';
    }
    if (strpos($script, '/presentation/patient/') !== false) {
        return '../auth/login.php';
    }
    return 'login.php';
}

function logout_redirect_path(): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (strpos($script, '/application/') !== false) {
        return '../../presentation/auth/logout.php';
    }
    if (strpos($script, '/presentation/modules/') !== false) {
        return '../../auth/logout.php';
    }
    if (strpos($script, '/presentation/patient/') !== false) {
        return '../auth/logout.php';
    }
    return 'logout.php';
}

function getLinkedRefId(mysqli $conn, string $role, string $username): ?string
{
    $stmt = $conn->prepare('SELECT refID FROM user WHERE username = ? AND role = ?');
    $stmt->bind_param('ss', $username, $role);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row && !empty($row['refID'])) {
        return $row['refID'];
    }

    return null;
}

function ensurePatientRefLink(mysqli $conn, string $username): ?string
{
    $stmt = $conn->prepare('SELECT fullname, email, refID FROM user WHERE username = ? AND role = ?');
    $role = 'patient';
    $stmt->bind_param('ss', $username, $role);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        return null;
    }

    $patientModel = new PatientModel($conn);
    $userModel = new UserModel($conn);

    if (!empty($user['refID']) && $patientModel->getById($user['refID'])) {
        $_SESSION['refID'] = $user['refID'];
        return $user['refID'];
    }

    if (!empty($user['refID']) && !$patientModel->getById($user['refID'])) {
        $userModel->updateRefId($username, null);
        $_SESSION['refID'] = null;
    }

    $fullname = trim($user['fullname'] ?? $username);
    $contact = trim($user['email'] ?? '');
    if ($contact === '') {
        $contact = '0000000000';
    }

    $nameOptions = array_values(array_unique([
        $fullname . ' (' . $username . ')',
        $username,
        $fullname,
    ]));

    foreach ($nameOptions as $name) {
        if ($name === '') {
            continue;
        }
        $result = $patientModel->validateAndCreate([
            'name' => $name,
            'age' => 1,
            'gender' => 'Not specified',
            'contactNumber' => $contact,
            'medicalHistory' => 'None recorded',
        ]);
        if ($result['status'] === 'Success') {
            $patientID = $result['patientID'];
            $userModel->updateRefId($username, $patientID);
            $_SESSION['refID'] = $patientID;
            return $patientID;
        }
    }

    return null;
}

function resolvePatientId(mysqli $conn): string
{
    $username = $_SESSION['username'] ?? '';
    if ($username === '' || ($_SESSION['role'] ?? '') !== 'patient') {
        http_response_code(403);
        die('Patient account not identified.');
    }

    if (!empty($_SESSION['refID'])) {
        $patientModel = new PatientModel($conn);
        if ($patientModel->getById($_SESSION['refID'])) {
            return $_SESSION['refID'];
        }
    }

    $linked = getLinkedRefId($conn, 'patient', $username);
    if ($linked) {
        $_SESSION['refID'] = $linked;
        return $linked;
    }

    $linked = ensurePatientRefLink($conn, $username);
    if ($linked) {
        return $linked;
    }

    http_response_code(500);
    die('Unable to link your login to a patient profile. Please log out and register again, or contact hospital staff.');
}

function ensureDoctorRefLink(mysqli $conn, string $username): ?string
{
    $linked = getLinkedRefId($conn, 'doctor', $username);
    if ($linked) {
        $_SESSION['refID'] = $linked;
        return $linked;
    }

    $stmt = $conn->prepare('SELECT fullname, email FROM user WHERE username = ? AND role = ?');
    $role = 'doctor';
    $stmt->bind_param('ss', $username, $role);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        return null;
    }

    $doctorModel = new DoctorModel($conn);
    $result = $doctorModel->createFromRegistration(
        trim($user['fullname'] ?? $username),
        trim($user['email'] ?? '')
    );
    if (($result['status'] ?? '') === 'Success') {
        $doctorID = $result['doctorID'];
        (new UserModel($conn))->updateRefId($username, $doctorID);
        $_SESSION['refID'] = $doctorID;
        return $doctorID;
    }

    return null;
}

function resolveDoctorId(mysqli $conn): string
{
    $username = $_SESSION['username'] ?? '';
    if ($username === '' || ($_SESSION['role'] ?? '') !== 'doctor') {
        http_response_code(403);
        die('Doctor account not identified.');
    }

    if (!empty($_SESSION['refID'])) {
        $doctorModel = new DoctorModel($conn);
        if ($doctorModel->getById($_SESSION['refID'])) {
            return $_SESSION['refID'];
        }
    }

    $linked = ensureDoctorRefLink($conn, $username);
    if ($linked) {
        return $linked;
    }

    http_response_code(500);
    die('Unable to link your login to a doctor profile. Please contact hospital staff.');
}
