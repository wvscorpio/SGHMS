<?php

class AuthController
{
    private UserModel $userModel;

    public function __construct(mysqli $conn)
    {
        $this->userModel = new UserModel($conn);
    }

    public function validateLogin(string $username, string $password, string $selectedRole): array
    {
        $username = strtolower(trim($username));
        $selectedRole = strtolower(trim($selectedRole));

        $userRecord = $this->userModel->verifyCredential($username, $password);

        if ($userRecord['status'] !== 'Valid') {
            return ['status' => 'Fail', 'message' => 'Invalid Username or Password. Please try again.'];
        }

        if ($selectedRole !== $userRecord['role']) {
            return [
                'status' => 'Fail',
                'message' => 'Role mismatch: This account is registered as a ' . ucfirst($userRecord['role']),
            ];
        }

        $_SESSION['username'] = $userRecord['username'];
        $_SESSION['role'] = $userRecord['role'];
        $_SESSION['fullname'] = $userRecord['fullname'];
        $_SESSION['refID'] = $userRecord['refID'] ?? null;

        return ['status' => 'Success', 'role' => $userRecord['role']];
    }

    public function register(array $input): array
    {
        $username = strtolower(trim($input['username'] ?? ''));
        $password = $input['password'] ?? '';
        $contactNumber = normalizeContactNumber(trim($input['contactNumber'] ?? ''));
        $role = strtolower(trim($input['role'] ?? ''));
        $fullname = trim($input['fullname'] ?? '');

        if ($username === '' || $password === '' || $contactNumber === '' || $role === '' || $fullname === '') {
            return ['status' => 'Error', 'message' => 'All fields are required'];
        }

        if (!isValidContactNumber($contactNumber)) {
            return [
                'status' => 'Error',
                'message' => 'Please enter a valid contact number (10–11 digits, numbers only, e.g. 0123456789).',
            ];
        }

        if ($this->userModel->usernameExists($username)) {
            return ['status' => 'Error', 'message' => 'Username already exists'];
        }

        $details = [
            'username' => $username,
            'password' => $password,
            'email' => $contactNumber,
            'role' => $role,
            'fullname' => $fullname,
        ];
        if (!$this->userModel->register($details)) {
            return ['status' => 'Error', 'message' => 'Registration failed.'];
        }

        return ['status' => 'Success', 'message' => 'Registration successful. Please login.'];
    }

    public static function redirectForRole(string $role): void
    {
        switch ($role) {
            case 'staff':
                header('Location: ../../application/appointment/manageAppointment.php');
                break;
            case 'doctor':
                header('Location: ../modules/doctor/dashboard.php');
                break;
            case 'patient':
                header('Location: ../patient/patientDashboard.php');
                break;
            default:
                header('Location: login.php');
        }
        exit;
    }
}
