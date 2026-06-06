<?php
require_once __DIR__ . '/../../../includes/init.php';
requireRole('doctor');

function doctorStatusClass(string $status): string
{
    return match ($status) {
        'Confirmed' => 'status-confirmed',
        'Pending' => 'status-pending',
        'Cancelled' => 'status-cancelled',
        'Completed' => 'status-completed',
        default => 'status-pending',
    };
}

function doctorParseAllergies(?array $patient): array
{
    $allergyMap = [
        'Eczema' => ['Dust', 'Strong soap'],
        'Asthma' => ['Dust'],
    ];
    $allergies = [];
    if (!$patient || empty($patient['medicalHistory']) || $patient['medicalHistory'] === 'None recorded') {
        return $allergies;
    }
    foreach (preg_split('/[,;]+/', $patient['medicalHistory']) as $condition) {
        $condition = trim($condition);
        if ($condition === '' || !isset($allergyMap[$condition])) {
            continue;
        }
        foreach ($allergyMap[$condition] as $allergy) {
            if (!in_array($allergy, $allergies, true)) {
                $allergies[] = $allergy;
            }
        }
    }
    return $allergies;
}

function doctorParseCurrentMedications(array $prescriptions): array
{
    $medications = [];
    foreach ($prescriptions as $rx) {
        $status = normalizePrescriptionStatus($rx['status'] ?? '');
        if (!in_array($status, [RX_STATUS_READY, RX_STATUS_COLLECTED, RX_STATUS_PENDING], true)) {
            continue;
        }
        $medications[] = $rx['medicineName'];
    }
    return $medications;
}

function doctorBuildPatientNotes(array $appointment): string
{
    $reason = strtolower($appointment['reason'] ?? '');
    if (str_contains($reason, 'follow-up')) {
        return 'Follow-up consultation scheduled. Patient should avoid known allergy triggers.';
    }
    if (str_contains($reason, 'check')) {
        return 'General check-up scheduled. Review patient history and vital signs.';
    }
    return 'Review patient history before consultation.';
}

function doctorDisplayName(?array $doctor, string $fallback = 'Doctor'): string
{
    return formatDoctorDisplayName(trim($doctor['name'] ?? $fallback));
}

$doctorID = getLinkedRefId($conn, 'doctor', $_SESSION['username']) ?? 'DOC001';
$appointmentModel = new AppointmentModel($conn);
$patientModel = new PatientModel($conn);
$prescriptionController = new PrescriptionController($conn);
$doctorModel = new DoctorModel($conn);
$doctor = $doctorModel->getById($doctorID);
$doctorName = doctorDisplayName($doctor, $_SESSION['fullname'] ?? 'Doctor');
$prescriptionModel = new PrescriptionModel($conn);
$nextPrescriptionID = $prescriptionModel->generatePrescriptionID();
$appointments = $appointmentModel->getByDoctor($doctorID);

$patientCache = [];
$prescriptionCache = [];
$status_message = null;
$success_text = '';
$error_text = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_prescription'])) {
    $instructions = trim($_POST['instructions'] ?? '');
    $frequency = trim($_POST['frequency'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $parts = [];
    if ($frequency !== '') {
        $parts[] = 'Frequency: ' . $frequency;
    }
    if ($instructions !== '') {
        $parts[] = $instructions;
    }
    if ($diagnosis !== '') {
        $parts[] = 'Diagnosis/Notes: ' . $diagnosis;
    }
    $fullInstructions = implode('. ', $parts) ?: 'As directed.';
    $appointment = $appointmentModel->getById(trim($_POST['appointment_id'] ?? ''));

    if (!doctorCanPrescribeForAppointment($appointment, $doctorID)) {
        $status_message = 'error';
        $error_text = 'Prescriptions can only be created for confirmed appointments.';
    } else {
        $result = $prescriptionController->createPrescription([
            'medicineName' => trim($_POST['medicineName'] ?? ''),
            'dosage' => trim($_POST['dosage'] ?? ''),
            'duration' => trim($_POST['duration'] ?? ''),
            'instructions' => $fullInstructions,
        ], $_POST['patientID'] ?? '', $doctorID, trim($_POST['appointment_id'] ?? '') ?: null);

        if (($result['status'] ?? '') === 'Success') {
            header('Location: dashboard.php?rx=success');
            exit;
        }
        $status_message = 'error';
        $error_text = $result['message'] ?? 'Failed to create prescription.';
    }
}

if (isset($_GET['rx']) && $_GET['rx'] === 'success') {
    $status_message = 'success';
    $success_text = 'Prescription submitted to pharmacy staff.';
}

$today = date('Y-m-d');
$todayAppts = array_filter($appointments, fn($a) => $a['appointmentDate'] === $today);
$totalToday = count($todayAppts);
$confirmedToday = count(array_filter($todayAppts, fn($a) => $a['status'] === 'Confirmed'));
$pendingToday = count(array_filter($todayAppts, fn($a) => $a['status'] === 'Pending'));

if ($totalToday === 0 && count($appointments) > 0) {
    $totalToday = count($appointments);
    $confirmedToday = count(array_filter($appointments, fn($a) => $a['status'] === 'Confirmed'));
    $pendingToday = count(array_filter($appointments, fn($a) => $a['status'] === 'Pending'));
}

$activeTab = 'appointments';

$commonMedicines = [
    'Paracetamol',
    'Ibuprofen',
    'Amoxicillin',
    'Salbutamol Inhaler',
    'Hydrocortisone Cream',
    'Antihistamine',
    'Metformin',
    'Amlodipine',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Appointments | SGHMS</title>
    <link rel="stylesheet" href="../../css/doctorDashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="doctor-dashboard">

<?php include __DIR__ . '/partials/header.php'; ?>

<div class="doc-content">

    <section class="main-card">
        <div class="main-card-top">
            <div>
                <h2>Manage Appointments</h2>
                <p class="subtitle">View upcoming consultations and manage prescriptions</p>
            </div>
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search appointment or patient">
            </div>
        </div>

        <div class="doc-table-wrap">
            <table class="doc-table" id="appointmentsTable">
                <thead>
                    <tr>
                        <th>Appointment ID</th>
                        <th>Patient ID</th>
                        <th>Patient Name</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($appointments) > 0): ?>
                        <?php foreach ($appointments as $row): ?>
                            <?php
                            $pid = $row['patientID'];
                            if (!isset($patientCache[$pid])) {
                                $patientCache[$pid] = $patientModel->getById($pid);
                                $prescriptionCache[$pid] = $prescriptionController->getByPatient($pid);
                            }
                            $patient = $patientCache[$pid];
                            $allergies = doctorParseAllergies($patient);
                            $medications = doctorParseCurrentMedications($prescriptionCache[$pid]);
                            $patientPayload = [
                                'patientID' => $pid,
                                'name' => $patient['name'] ?? ($row['patientName'] ?? '—'),
                                'age' => $patient['age'] ?? '—',
                                'gender' => $patient['gender'] ?? '—',
                                'contact' => $patient['contactNumber'] ?? '—',
                                'doctor' => $doctorName,
                                'appointmentID' => $row['appointmentID'],
                                'medicalHistory' => $patient['medicalHistory'] ?? 'None recorded',
                                'allergies' => count($allergies) ? implode(', ', $allergies) : 'None recorded',
                                'medications' => count($medications) ? implode(', ', $medications) : 'None',
                                'notes' => doctorBuildPatientNotes($row),
                                'appointmentStatus' => $row['status'],
                            ];
                            $prescribePayload = [
                                'prescriptionID' => $nextPrescriptionID,
                                'appointmentID' => $row['appointmentID'],
                                'patientID' => $pid,
                                'patientName' => $patientPayload['name'],
                                'doctorID' => $doctorID,
                                'doctorName' => $doctorName,
                            ];
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($row['appointmentID']) ?></td>
                                <td><?= htmlspecialchars($row['patientID']) ?></td>
                                <td><?= htmlspecialchars($row['patientName'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($row['appointmentDate']) ?></td>
                                <td><?= date('h:i A', strtotime($row['appointmentTime'])) ?></td>
                                <td><?= htmlspecialchars($row['reason']) ?></td>
                                <td>
                                    <span class="status-badge <?= doctorStatusClass($row['status']) ?>">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <button type="button" class="btn-view-patient"
                                                onclick='viewPatientDetails(<?= json_encode($patientPayload, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                            View Patient
                                        </button>
                                        <?php if ($row['status'] === 'Confirmed'): ?>
                                            <button type="button" class="btn-prescribe"
                                                    onclick='openPrescriptionModal(<?= json_encode($prescribePayload, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                                Prescribe
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn-prescribe" disabled
                                                    title="Available when appointment is confirmed">
                                                Prescribe
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="empty-row">
                            <td colspan="8">No appointments found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="summary-card">
        <h3>Today Summary</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-icon blue"><i class="fas fa-calendar-alt"></i></div>
                <div>
                    <div class="label">Total Appointments</div>
                    <div class="value blue"><?= $totalToday ?></div>
                </div>
            </div>
            <div class="summary-item">
                <div class="summary-icon green"><i class="fas fa-check-circle"></i></div>
                <div>
                    <div class="label">Confirmed</div>
                    <div class="value green"><?= $confirmedToday ?></div>
                </div>
            </div>
            <div class="summary-item">
                <div class="summary-icon orange"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="label">Pending</div>
                    <div class="value orange"><?= $pendingToday ?></div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Patient Details Modal (SWDD Figure 6.16) -->
<div id="patientModal" class="modal-overlay">
    <div class="modal-content patient-modal-content">
        <span class="close-btn" onclick="closePatientModal()" role="button" aria-label="Close">&times;</span>
        <h2>Patient Details</h2>
        <div class="detail-list">
            <div class="detail-item">
                <span class="detail-label">Patient ID</span>
                <span class="detail-value" id="patient_detail_id">—</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Name</span>
                <span class="detail-value" id="patient_detail_name">—</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Age</span>
                <span class="detail-value" id="patient_detail_age">—</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Gender</span>
                <span class="detail-value" id="patient_detail_gender">—</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Contact</span>
                <span class="detail-value" id="patient_detail_contact">—</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Doctor</span>
                <span class="detail-value" id="patient_detail_doctor">—</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Appointment ID</span>
                <span class="detail-value" id="patient_detail_appointment">—</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Medical History</span>
                <span class="detail-value" id="patient_detail_history">—</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Allergies</span>
                <span class="detail-value" id="patient_detail_allergies">—</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Current Medication</span>
                <span class="detail-value" id="patient_detail_medications">—</span>
            </div>
            <div class="detail-item detail-item-notes">
                <span class="detail-label">Notes</span>
                <span class="detail-value notes-text" id="patient_detail_notes">—</span>
            </div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-modal-close" onclick="closePatientModal()">Close</button>
            <button type="button" class="btn-prescribe btn-prescribe-modal" id="patient_detail_prescribe">Prescribe</button>
        </div>
    </div>
</div>

<!-- Create Prescription Modal (SWDD Figure 6.17) -->
<div id="prescriptionModal" class="modal-overlay">
    <div class="modal-content prescription-modal-content">
        <span class="close-btn" onclick="closePrescriptionModal()" role="button" aria-label="Close">&times;</span>
        <h2>Create Prescription</h2>
        <form method="post" class="rx-form" id="prescriptionForm">
            <input type="hidden" name="patientID" id="rx_patient_id">
            <input type="hidden" name="appointment_id" id="rx_appointment_id">

            <div class="rx-form-grid rx-readonly-grid">
                <div class="rx-field">
                    <label for="rx_prescription_id">Prescription ID</label>
                    <input type="text" id="rx_prescription_id" readonly>
                </div>
                <div class="rx-field">
                    <label for="rx_appointment_display">Appointment ID</label>
                    <input type="text" id="rx_appointment_display" readonly>
                </div>
                <div class="rx-field">
                    <label for="rx_patient_id_display">Patient ID</label>
                    <input type="text" id="rx_patient_id_display" readonly>
                </div>
                <div class="rx-field">
                    <label for="rx_patient_name">Patient Name</label>
                    <input type="text" id="rx_patient_name" readonly>
                </div>
                <div class="rx-field">
                    <label for="rx_doctor_id">Doctor ID</label>
                    <input type="text" id="rx_doctor_id" readonly>
                </div>
                <div class="rx-field">
                    <label for="rx_doctor_name">Doctor Name</label>
                    <input type="text" id="rx_doctor_name" readonly>
                </div>
            </div>

            <div class="rx-form-grid">
                <div class="rx-field">
                    <label for="medicineName">Medicine Name</label>
                    <input type="text" name="medicineName" id="medicineName" list="commonMedicinesList"
                           required placeholder="Type medicine name" autocomplete="off">
                    <datalist id="commonMedicinesList">
                        <?php foreach ($commonMedicines as $medicine): ?>
                            <option value="<?= htmlspecialchars($medicine) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                    <span class="rx-field-hint">Type any medicine. Suggestions appear only for common names.</span>
                </div>
                <div class="rx-field">
                    <label for="dosage">Dosage</label>
                    <input type="text" name="dosage" id="dosage" required placeholder="e.g. 500mg">
                </div>
                <div class="rx-field">
                    <label for="frequency">Frequency</label>
                    <select name="frequency" id="frequency" required>
                        <option value="Once daily">Once daily</option>
                        <option value="Twice daily">Twice daily</option>
                        <option value="3 times daily" selected>3 times daily</option>
                        <option value="4 times daily">4 times daily</option>
                        <option value="As needed">As needed</option>
                    </select>
                </div>
                <div class="rx-field">
                    <label for="duration">Duration</label>
                    <select name="duration" id="duration" required>
                        <option value="3 days">3 days</option>
                        <option value="5 days" selected>5 days</option>
                        <option value="7 days">7 days</option>
                        <option value="14 days">14 days</option>
                        <option value="30 days">30 days</option>
                    </select>
                </div>
                <div class="rx-field rx-field-full">
                    <label for="instructions">Instructions</label>
                    <input type="text" name="instructions" id="instructions" required placeholder="e.g. Take after meal">
                </div>
                <div class="rx-field rx-field-full">
                    <label for="diagnosis">Diagnosis / Notes</label>
                    <textarea name="diagnosis" id="diagnosis" rows="3" placeholder="Enter diagnosis or clinical notes"></textarea>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-modal-close" onclick="closePrescriptionModal()">Cancel</button>
                <button type="submit" name="create_prescription" class="btn-prescribe btn-prescribe-submit">Submit Prescription</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function () {
    const keyword = this.value.toLowerCase();
    document.querySelectorAll('#appointmentsTable tbody tr').forEach(row => {
        if (row.classList.contains('empty-row')) return;
        row.style.display = row.innerText.toLowerCase().includes(keyword) ? '' : 'none';
    });
});

function viewPatientDetails(data) {
    document.getElementById('patient_detail_id').textContent = data.patientID || '—';
    document.getElementById('patient_detail_name').textContent = data.name || '—';
    document.getElementById('patient_detail_age').textContent = data.age || '—';
    document.getElementById('patient_detail_gender').textContent = data.gender || '—';
    document.getElementById('patient_detail_contact').textContent = data.contact || '—';
    document.getElementById('patient_detail_doctor').textContent = data.doctor || '—';
    document.getElementById('patient_detail_appointment').textContent = data.appointmentID || '—';
    document.getElementById('patient_detail_history').textContent = data.medicalHistory || '—';
    document.getElementById('patient_detail_allergies').textContent = data.allergies || '—';
    document.getElementById('patient_detail_medications').textContent = data.medications || 'None';
    document.getElementById('patient_detail_notes').textContent = data.notes || '—';
    const prescribeBtn = document.getElementById('patient_detail_prescribe');
    if (data.appointmentStatus === 'Confirmed') {
        prescribeBtn.disabled = false;
        prescribeBtn.title = '';
        prescribeBtn.onclick = function () {
            closePatientModal();
            openPrescriptionModal({
                prescriptionID: '<?= htmlspecialchars($nextPrescriptionID, ENT_QUOTES) ?>',
                appointmentID: data.appointmentID,
                patientID: data.patientID,
                patientName: data.name,
                doctorID: '<?= htmlspecialchars($doctorID, ENT_QUOTES) ?>',
                doctorName: <?= json_encode($doctorName) ?>
            });
        };
    } else {
        prescribeBtn.disabled = true;
        prescribeBtn.title = 'Available when appointment is confirmed';
        prescribeBtn.onclick = null;
    }
    document.getElementById('patientModal').classList.add('open');
}

function openPrescriptionModal(ctx) {
    document.getElementById('rx_prescription_id').value = ctx.prescriptionID || '';
    document.getElementById('rx_appointment_display').value = ctx.appointmentID || '';
    document.getElementById('rx_appointment_id').value = ctx.appointmentID || '';
    document.getElementById('rx_patient_id').value = ctx.patientID || '';
    document.getElementById('rx_patient_id_display').value = ctx.patientID || '';
    document.getElementById('rx_patient_name').value = ctx.patientName || '';
    document.getElementById('rx_doctor_id').value = ctx.doctorID || '';
    document.getElementById('rx_doctor_name').value = ctx.doctorName || '';
    document.getElementById('medicineName').value = '';
    document.getElementById('dosage').value = '';
    document.getElementById('frequency').value = '3 times daily';
    document.getElementById('duration').value = '5 days';
    document.getElementById('instructions').value = '';
    document.getElementById('diagnosis').value = '';
    document.getElementById('prescriptionModal').classList.add('open');
}

function closePrescriptionModal() {
    document.getElementById('prescriptionModal').classList.remove('open');
}

document.getElementById('prescriptionModal').addEventListener('click', function (e) {
    if (e.target === this) closePrescriptionModal();
});

function closePatientModal() {
    document.getElementById('patientModal').classList.remove('open');
}

document.getElementById('patientModal').addEventListener('click', function (e) {
    if (e.target === this) closePatientModal();
});
</script>

<?php if ($status_message): ?>
<script>
    Swal.fire({
        title: '<?= $status_message === 'success' ? 'Success!' : 'Error!' ?>',
        text: '<?= $status_message === 'success' ? addslashes($success_text) : addslashes($error_text) ?>',
        icon: '<?= $status_message ?>'
    }).then(() => {
        if (window.location.search.includes('rx=success')) {
            window.history.replaceState({}, document.title, 'dashboard.php');
        }
    });
</script>
<?php endif; ?>
</body>
</html>
