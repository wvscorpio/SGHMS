<?php
require_once __DIR__ . '/../../includes/init.php';
requireRole('staff');

$tab = $_GET['tab'] ?? 'doctor';
if (!in_array($tab, ['doctor', 'patient'], true)) {
    $tab = 'doctor';
}

$activeTab = 'users';
$userSubTab = $tab;

if (isset($_POST['save_doctor'])) {
    $id = $_POST['doctorID'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $contact = trim($_POST['contactDetails'] ?? '');

    if ($name === '' || $specialization === '' || $contact === '') {
        $_SESSION['popup'] = 'Please fill in all required fields';
        header('Location: manageUsers.php?tab=doctor');
        exit;
    }

    $doctorModel = new DoctorModel($conn);
    if ($id) {
        $doctorModel->update($id, [
            'name' => $name,
            'specialization' => $specialization,
            'contactDetails' => $contact,
        ]);
        $_SESSION['popup'] = 'Doctor profile updated successfully';
    } else {
        $result = $doctorModel->createFromRegistration($name, $contact);
        if (($result['status'] ?? '') === 'Success') {
            $stmt = $conn->prepare('UPDATE doctor SET specialization = ? WHERE doctorID = ?');
            $stmt->bind_param('ss', $specialization, $result['doctorID']);
            $stmt->execute();
            $stmt->close();
            $_SESSION['popup'] = 'Doctor profile created successfully';
        } else {
            $_SESSION['popup'] = $result['message'] ?? 'Failed to create doctor profile';
        }
    }

    header('Location: manageUsers.php?tab=doctor');
    exit;
}

if (isset($_POST['save_patient'])) {
    $id = $_POST['patientID'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $contact = trim($_POST['contactNumber'] ?? '');
    $history = trim($_POST['medicalHistory'] ?? '');

    if ($name === '' || $age === '' || $gender === '' || $contact === '' || $history === '') {
        $_SESSION['popup'] = 'Please fill in all required fields';
        header('Location: manageUsers.php?tab=patient');
        exit;
    }

    $patientModel = new PatientModel($conn);
    if ($id) {
        $patientModel->update($id, [
            'name' => $name,
            'age' => $age,
            'gender' => $gender,
            'contactNumber' => $contact,
            'medicalHistory' => $history,
        ]);
        $_SESSION['popup'] = 'Patient profile updated successfully';
    } else {
        $result = $patientModel->validateAndCreate([
            'name' => $name,
            'age' => $age,
            'gender' => $gender,
            'contactNumber' => $contact,
            'medicalHistory' => $history,
        ]);
        $_SESSION['popup'] = ($result['status'] ?? '') === 'Success'
            ? 'Patient profile created successfully'
            : ($result['message'] ?? 'Failed to create patient profile');
    }

    header('Location: manageUsers.php?tab=patient');
    exit;
}

if (isset($_GET['delete_doctor'])) {
    $stmt = $conn->prepare('DELETE FROM doctor WHERE doctorID = ?');
    $stmt->bind_param('s', $_GET['delete_doctor']);
    $stmt->execute();
    $stmt->close();
    $_SESSION['popup'] = 'Doctor profile deleted successfully';
    header('Location: manageUsers.php?tab=doctor');
    exit;
}

if (isset($_GET['delete_patient'])) {
    $stmt = $conn->prepare('DELETE FROM patient WHERE patientID = ?');
    $stmt->bind_param('s', $_GET['delete_patient']);
    $stmt->execute();
    $stmt->close();
    $_SESSION['popup'] = 'Patient profile deleted successfully';
    header('Location: manageUsers.php?tab=patient');
    exit;
}

$doctors = (new DoctorModel($conn))->getAll();
$patients = (new PatientModel($conn))->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - User Management | SGHMS</title>
    <?php include __DIR__ . '/partials/staffHead.php'; ?>
</head>
<body class="staff-dashboard">

<?php if (isset($_SESSION['popup'])): ?>
    <div class="toast"><?= htmlspecialchars($_SESSION['popup']); ?></div>
    <?php unset($_SESSION['popup']); ?>
<?php endif; ?>

<?php include __DIR__ . '/partials/staffNav.php'; ?>

<div class="staff-content">
    <section class="cardheader">
        <div class="card-header">
            <div class="card-header-text">
                <?php if ($tab === 'doctor'): ?>
                    <h3>Manage Doctors</h3>
                    <p>Add, edit, or remove doctor information</p>
                <?php else: ?>
                    <h3>Manage Patients</h3>
                    <p>Add, edit, or remove patient information</p>
                <?php endif; ?>
            </div>
            <div class="card-header-tools">
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Search <?= $tab === 'doctor' ? 'doctor' : 'patient' ?>">
                </div>
                <?php if ($tab === 'doctor'): ?>
                    <button type="button" class="btn-new-apt" onclick="openDoctorForm()">Add Doctor</button>
                <?php else: ?>
                    <button type="button" class="btn-new-apt" onclick="openPatientForm()">Add Patient</button>
                <?php endif; ?>
            </div>
        </div>

        <div class="table-wrap">
            <?php if ($tab === 'doctor'): ?>
                <table class="appointment-table user-table">
                    <thead>
                        <tr>
                            <th>Doctor ID</th>
                            <th>Name</th>
                            <th>Specialization</th>
                            <th>Contact Details</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($doctors) > 0): ?>
                            <?php foreach ($doctors as $d): ?>
                                <tr class="searchable-row">
                                    <td><?= htmlspecialchars($d['doctorID']) ?></td>
                                    <td><?= htmlspecialchars($d['name']) ?></td>
                                    <td><?= htmlspecialchars($d['specialization']) ?></td>
                                    <td><?= htmlspecialchars($d['contactDetails']) ?></td>
                                    <td class="actions">
                                        <button type="button" class="btn-edit" title="Edit doctor"
                                                onclick='openEditDoctor(<?= json_encode($d, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                            ✏️
                                        </button>
                                        <a href="manageUsers.php?tab=doctor&delete_doctor=<?= urlencode($d['doctorID']) ?>"
                                           class="btn-delete" title="Delete doctor"
                                           onclick="return confirm('Delete this doctor?')">
                                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M3 6h18" stroke="white" stroke-width="2"/>
                                                <path d="M8 6v-2h8v2" stroke="white" stroke-width="2"/>
                                                <rect x="6" y="6" width="12" height="14" rx="2" stroke="white" stroke-width="2"/>
                                                <path d="M10 11v6M14 11v6" stroke="white" stroke-width="2"/>
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="empty-cell">No doctors found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <table class="appointment-table user-table">
                    <thead>
                        <tr>
                            <th>Patient ID</th>
                            <th>Name</th>
                            <th>Age</th>
                            <th>Gender</th>
                            <th>Contact</th>
                            <th>Medical History</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($patients) > 0): ?>
                            <?php foreach ($patients as $p): ?>
                                <tr class="searchable-row">
                                    <td><?= htmlspecialchars($p['patientID']) ?></td>
                                    <td><?= htmlspecialchars($p['name']) ?></td>
                                    <td><?= htmlspecialchars($p['age']) ?></td>
                                    <td><?= htmlspecialchars($p['gender']) ?></td>
                                    <td><?= htmlspecialchars($p['contactNumber']) ?></td>
                                    <td><?= htmlspecialchars($p['medicalHistory']) ?></td>
                                    <td class="actions">
                                        <button type="button" class="btn-edit" title="Edit patient"
                                                onclick='openEditPatient(<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                            ✏️
                                        </button>
                                        <a href="manageUsers.php?tab=patient&delete_patient=<?= urlencode($p['patientID']) ?>"
                                           class="btn-delete" title="Delete patient"
                                           onclick="return confirm('Delete this patient?')">
                                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M3 6h18" stroke="white" stroke-width="2"/>
                                                <path d="M8 6v-2h8v2" stroke="white" stroke-width="2"/>
                                                <rect x="6" y="6" width="12" height="14" rx="2" stroke="white" stroke-width="2"/>
                                                <path d="M10 11v6M14 11v6" stroke="white" stroke-width="2"/>
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="empty-cell">No patients found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </section>
</div>

<div class="staffcard" id="doctorModal">
    <form method="post" class="form-container">
        <button type="button" class="close-btn" onclick="closeDoctorForm()" aria-label="Close">&times;</button>
        <h2 id="doctorFormTitle">Add New Doctor</h2>
        <p class="form-subtitle" id="doctorFormSubtitle">Enter doctor information</p>
        <input type="hidden" name="doctorID" id="doctorID">

        <label for="doctorName">Full Name</label>
        <input type="text" name="name" id="doctorName" required>

        <label for="doctorSpecialization">Specialization</label>
        <select name="specialization" id="doctorSpecialization" required>
            <option value="">Select specialization</option>
            <?php foreach (['General Practitioner (GP)', 'Pediatrics', 'Cardiology', 'Respiratory / Pulmonology', 'Orthopedics', 'Neurology', 'Dermatology', 'ENT (Ear, Nose, Throat)', 'Endocrinology', 'Gastroenterology', 'Emergency Medicine'] as $spec): ?>
                <option><?= htmlspecialchars($spec) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="doctorContact">Contact Details</label>
        <textarea name="contactDetails" id="doctorContact" required></textarea>

        <div class="form-actions">
            <button type="submit" name="save_doctor">Save</button>
            <button type="button" onclick="closeDoctorForm()">Cancel</button>
        </div>
    </form>
</div>

<div class="staffcard" id="patientModal">
    <form method="post" class="form-container">
        <button type="button" class="close-btn" onclick="closePatientForm()" aria-label="Close">&times;</button>
        <h2 id="patientFormTitle">Add New Patient</h2>
        <p class="form-subtitle" id="patientFormSubtitle">Enter patient information</p>
        <input type="hidden" name="patientID" id="patientID">

        <label for="patientName">Full Name</label>
        <input type="text" name="name" id="patientName" required>

        <div class="row">
            <label>Age
                <input type="number" name="age" id="patientAge" required>
            </label>
            <label>Gender
                <select name="gender" id="patientGender" required>
                    <option value="">Select</option>
                    <option value="Female">Female</option>
                    <option value="Male">Male</option>
                    <option value="Other">Other</option>
                </select>
            </label>
        </div>

        <label for="patientContact">Contact Number</label>
        <input type="text" name="contactNumber" id="patientContact" required>

        <label for="patientHistory">Medical History</label>
        <textarea name="medicalHistory" id="patientHistory" required></textarea>

        <div class="form-actions">
            <button type="submit" name="save_patient">Save</button>
            <button type="button" onclick="closePatientForm()">Cancel</button>
        </div>
    </form>
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function () {
    const keyword = this.value.toLowerCase();
    document.querySelectorAll('.searchable-row').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(keyword) ? '' : 'none';
    });
});

function openDoctorForm() {
    document.getElementById('doctorFormTitle').innerText = 'Add New Doctor';
    document.getElementById('doctorFormSubtitle').innerText = 'Enter doctor information';
    document.getElementById('doctorID').value = '';
    document.getElementById('doctorName').value = '';
    document.getElementById('doctorSpecialization').value = '';
    document.getElementById('doctorContact').value = '';
    document.getElementById('doctorModal').style.display = 'flex';
}

function openEditDoctor(data) {
    document.getElementById('doctorFormTitle').innerText = 'Edit Doctor';
    document.getElementById('doctorFormSubtitle').innerText = 'Update doctor information';
    document.getElementById('doctorID').value = data.doctorID;
    document.getElementById('doctorName').value = data.name;
    document.getElementById('doctorSpecialization').value = data.specialization;
    document.getElementById('doctorContact').value = data.contactDetails;
    document.getElementById('doctorModal').style.display = 'flex';
}

function closeDoctorForm() {
    document.getElementById('doctorModal').style.display = 'none';
}

function openPatientForm() {
    document.getElementById('patientFormTitle').innerText = 'Add New Patient';
    document.getElementById('patientFormSubtitle').innerText = 'Enter patient information';
    document.getElementById('patientID').value = '';
    document.getElementById('patientName').value = '';
    document.getElementById('patientAge').value = '';
    document.getElementById('patientGender').value = '';
    document.getElementById('patientContact').value = '';
    document.getElementById('patientHistory').value = '';
    document.getElementById('patientModal').style.display = 'flex';
}

function openEditPatient(data) {
    document.getElementById('patientFormTitle').innerText = 'Edit Patient';
    document.getElementById('patientFormSubtitle').innerText = 'Update patient information';
    document.getElementById('patientID').value = data.patientID;
    document.getElementById('patientName').value = data.name;
    document.getElementById('patientAge').value = data.age;
    document.getElementById('patientGender').value = data.gender;
    document.getElementById('patientContact').value = data.contactNumber;
    document.getElementById('patientHistory').value = data.medicalHistory;
    document.getElementById('patientModal').style.display = 'flex';
}

function closePatientForm() {
    document.getElementById('patientModal').style.display = 'none';
}
</script>
</body>
</html>
