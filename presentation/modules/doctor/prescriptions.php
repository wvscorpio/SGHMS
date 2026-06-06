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

function doctorDisplayName(?array $doctor, string $fallback = 'Doctor'): string
{
    return formatDoctorDisplayName(trim($doctor['name'] ?? $fallback));
}



function prescriptionDisplayStatus(string $status): string
{
    return prescriptionStatusLabel($status, 'doctor');
}

function buildPrescriptionDetailPayload(array $rx, string $doctorName): array

{

    $instructions = $rx['instructions'] ?? '';

    $rawStatus = normalizePrescriptionStatus($rx['status'] ?? RX_STATUS_PENDING);
    $displayStatus = prescriptionStatusLabel($rawStatus, 'doctor');



    return [

        'prescriptionID' => $rx['prescriptionID'],

        'patientName' => $rx['patientName'] ?? '—',

        'doctor' => $doctorName,

        'medicine' => $rx['medicineName'],

        'dosage' => $rx['dosage'],

        'frequency' => parsePrescriptionFrequency($instructions),

        'duration' => $rx['duration'],

        'instructions' => parsePrescriptionInstructions($instructions),

        'diagnosis' => parsePrescriptionDiagnosis($instructions),

        'status' => $displayStatus,

        'statusClass' => match ($rawStatus) {
            RX_STATUS_READY => 'ready',
            RX_STATUS_COLLECTED => 'issued',
            default => 'issued',
        },

    ];

}



$doctorID = getLinkedRefId($conn, 'doctor', $_SESSION['username']) ?? 'DOC001';
$appointmentModel = new AppointmentModel($conn);
$prescriptionController = new PrescriptionController($conn);
$prescriptionModel = new PrescriptionModel($conn);
$doctorModel = new DoctorModel($conn);
$doctor = $doctorModel->getById($doctorID);
$doctorName = doctorDisplayName($doctor, $_SESSION['fullname'] ?? 'Doctor');
$nextPrescriptionID = $prescriptionModel->generatePrescriptionID();
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
            header('Location: prescriptions.php?rx=success');
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

$appointments = array_filter(
    $appointmentModel->getByDoctor($doctorID),
    fn($a) => !in_array($a['status'], ['Cancelled', 'Completed'], true)
);
$prescriptions = $prescriptionController->getByDoctor($doctorID);
$activeTab = 'prescriptions';

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

    <title>Doctor Dashboard - Manage Prescriptions | SGHMS</title>

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

                <h2>Manage Prescriptions</h2>

                <p class="subtitle">Review patient records and prescribe medication</p>

            </div>

            <div class="toolbar-controls">

                <div class="search-box">

                    <i class="fas fa-search"></i>

                    <input type="text" id="searchInput" placeholder="Search patient or prescription">

                </div>

                <?php if (count($prescriptions) > 0): ?>

                    <?php $firstRx = buildPrescriptionDetailPayload($prescriptions[0], $doctorName); ?>

                    <button type="button" class="btn-prescribe btn-view-rx"

                            onclick='viewPrescriptionDetails(<?= json_encode($firstRx, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>

                        View Prescription

                    </button>

                <?php else: ?>

                    <button type="button" class="btn-prescribe btn-view-rx" disabled>View Prescription</button>

                <?php endif; ?>

            </div>

        </div>



        <div class="doc-table-wrap">

            <table class="doc-table" id="appointmentsTable">

                <thead>

                    <tr>

                        <th>Appointment ID</th>

                        <th>Patient ID</th>

                        <th>Patient</th>

                        <th>Date</th>

                        <th>Time</th>

                        <th>Reason</th>

                        <th>Status</th>

                        <th>Action</th>

                    </tr>

                </thead>

                <tbody>

                    <?php if (count($appointments) > 0): ?>

                        <?php foreach ($appointments as $row): ?>

                            <?php
                            $prescribePayload = [
                                'prescriptionID' => $nextPrescriptionID,
                                'appointmentID' => $row['appointmentID'],
                                'patientID' => $row['patientID'],
                                'patientName' => $row['patientName'] ?? '—',
                                'doctorID' => $doctorID,
                                'doctorName' => $doctorName,
                            ];
                            ?>

                            <tr class="searchable-row">

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

                                    <?php if ($row['status'] === 'Confirmed'): ?>
                                        <button type="button" class="btn-prescribe btn-prescribe-sm"
                                                onclick='openPrescriptionModal(<?= json_encode($prescribePayload, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                            Prescribe
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn-prescribe btn-prescribe-sm" disabled
                                                title="Available when appointment is confirmed">
                                            Prescribe
                                        </button>
                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr class="empty-row">

                            <td colspan="8">No appointments available for prescribing.</td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>



    <section class="main-card" id="recent-prescriptions">

        <h2 class="section-heading">Recent Prescriptions</h2>

        <div class="doc-table-wrap">

            <table class="doc-table" id="prescriptionsTable">

                <thead>

                    <tr>

                        <th>Prescription ID</th>

                        <th>Patient</th>

                        <th>Medicine</th>

                        <th>Dosage</th>

                        <th>Frequency</th>

                        <th>Duration</th>

                        <th>Status</th>

                        <th>Action</th>

                    </tr>

                </thead>

                <tbody>

                    <?php if (count($prescriptions) > 0): ?>

                        <?php foreach ($prescriptions as $rx): ?>

                            <?php $rxDetail = buildPrescriptionDetailPayload($rx, $doctorName); ?>

                            <tr class="searchable-row">

                                <td><?= htmlspecialchars($rx['prescriptionID']) ?></td>

                                <td><?= htmlspecialchars($rx['patientName'] ?? '—') ?></td>

                                <td><?= htmlspecialchars($rx['medicineName']) ?></td>

                                <td><?= htmlspecialchars($rx['dosage']) ?></td>

                                <td><?= htmlspecialchars($rxDetail['frequency']) ?></td>

                                <td><?= htmlspecialchars($rx['duration']) ?></td>

                                <td>

                                    <span class="status-badge status-issued">

                                        <?= htmlspecialchars(prescriptionDisplayStatus($rx['status'])) ?>

                                    </span>

                                </td>

                                <td>

                                    <button type="button" class="btn-view-patient btn-view-rx-sm"

                                            onclick='viewPrescriptionDetails(<?= json_encode($rxDetail, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>

                                        View Details

                                    </button>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr class="empty-row">

                            <td colspan="8">No prescription records yet.</td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>



</div>



<!-- Create Prescription Modal -->
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



<!-- Prescription Details Modal (SWDD Figure 6.18) -->

<div id="rxDetailsModal" class="modal-overlay">

    <div class="modal-content rx-details-modal-content">

        <span class="close-btn" onclick="closeRxDetailsModal()" role="button" aria-label="Close">&times;</span>

        <h2>Prescription Details</h2>

        <div class="detail-list">

            <div class="detail-item">

                <span class="detail-label">Prescription ID</span>

                <span class="detail-value" id="rx_detail_id">—</span>

            </div>

            <div class="detail-item">

                <span class="detail-label">Patient Name</span>

                <span class="detail-value" id="rx_detail_patient">—</span>

            </div>

            <div class="detail-item">

                <span class="detail-label">Doctor</span>

                <span class="detail-value" id="rx_detail_doctor">—</span>

            </div>

            <div class="detail-item">

                <span class="detail-label">Medicine</span>

                <span class="detail-value" id="rx_detail_medicine">—</span>

            </div>

            <div class="detail-item">

                <span class="detail-label">Dosage</span>

                <span class="detail-value" id="rx_detail_dosage">—</span>

            </div>

            <div class="detail-item">

                <span class="detail-label">Frequency</span>

                <span class="detail-value" id="rx_detail_frequency">—</span>

            </div>

            <div class="detail-item">

                <span class="detail-label">Duration</span>

                <span class="detail-value" id="rx_detail_duration">—</span>

            </div>

            <div class="detail-item">

                <span class="detail-label">Instructions</span>

                <span class="detail-value" id="rx_detail_instructions">—</span>

            </div>

            <div class="detail-item">

                <span class="detail-label">Diagnosis</span>

                <span class="detail-value" id="rx_detail_diagnosis">—</span>

            </div>

            <div class="detail-item">

                <span class="detail-label">Status</span>

                <span class="detail-value" id="rx_detail_status_wrap"></span>

            </div>

        </div>

        <div class="rx-info-box" id="rx_detail_info_box" style="display:none;">
            <i class="fas fa-info-circle"></i>
            <span>Prescription has been sent to pharmacy staff for preparation.</span>
        </div>

        <div class="modal-actions">

            <button type="button" class="btn-modal-close" onclick="closeRxDetailsModal()">Close</button>

            <button type="button" class="btn-prescribe btn-download-rx" onclick="downloadPrescriptionDetails()">

                <i class="fas fa-download"></i> Download Prescription

            </button>

        </div>

    </div>

</div>



<script>

let currentRxDetails = null;



document.getElementById('searchInput').addEventListener('keyup', function () {

    const keyword = this.value.toLowerCase();

    document.querySelectorAll('.searchable-row').forEach(row => {

        row.style.display = row.innerText.toLowerCase().includes(keyword) ? '' : 'none';

    });

});



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



function viewPrescriptionDetails(data) {

    currentRxDetails = data;

    document.getElementById('rx_detail_id').textContent = data.prescriptionID || '—';

    document.getElementById('rx_detail_patient').textContent = data.patientName || '—';

    document.getElementById('rx_detail_doctor').textContent = data.doctor || '—';

    document.getElementById('rx_detail_medicine').textContent = data.medicine || '—';

    document.getElementById('rx_detail_dosage').textContent = data.dosage || '—';

    document.getElementById('rx_detail_frequency').textContent = data.frequency || '—';

    document.getElementById('rx_detail_duration').textContent = data.duration || '—';

    document.getElementById('rx_detail_instructions').textContent = data.instructions || '—';

    document.getElementById('rx_detail_diagnosis').textContent = data.diagnosis || '—';



    const statusWrap = document.getElementById('rx_detail_status_wrap');

    const statusClass = data.statusClass === 'ready' ? 'rx-status-ready' : 'rx-status-issued';

    statusWrap.innerHTML = `<span class="${statusClass}">${data.status || '—'}</span>`;



    const infoBox = document.getElementById('rx_detail_info_box');
    infoBox.style.display = (data.status === 'Sent to Pharmacy') ? 'flex' : 'none';



    document.getElementById('rxDetailsModal').classList.add('open');

}



function closeRxDetailsModal() {

    document.getElementById('rxDetailsModal').classList.remove('open');

}



document.getElementById('rxDetailsModal').addEventListener('click', function (e) {

    if (e.target === this) closeRxDetailsModal();

});



function downloadPrescriptionDetails() {

    if (!currentRxDetails) return;

    const d = currentRxDetails;

    let text = 'Sarawak General Hospital — Prescription\n';

    text += '=====================================\n\n';

    text += `Prescription ID: ${d.prescriptionID}\n`;

    text += `Patient Name: ${d.patientName}\n`;

    text += `Doctor: ${d.doctor}\n`;

    text += `Medicine: ${d.medicine}\n`;

    text += `Dosage: ${d.dosage}\n`;

    text += `Frequency: ${d.frequency}\n`;

    text += `Duration: ${d.duration}\n`;

    text += `Instructions: ${d.instructions}\n`;

    text += `Diagnosis: ${d.diagnosis}\n`;

    text += `Status: ${d.status}\n\n`;

    text += 'Please collect your medicine from the hospital pharmacy.\n';



    const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });

    const link = document.createElement('a');

    link.href = URL.createObjectURL(blob);

    link.download = `prescription-${d.prescriptionID}.txt`;

    document.body.appendChild(link);

    link.click();

    document.body.removeChild(link);

    URL.revokeObjectURL(link.href);

}

</script>

<?php if ($status_message): ?>
<script>
    Swal.fire({
        title: '<?= $status_message === 'success' ? 'Success!' : 'Error!' ?>',
        text: '<?= $status_message === 'success' ? addslashes($success_text) : addslashes($error_text) ?>',
        icon: '<?= $status_message ?>'
    }).then(() => {
        if (window.location.search.includes('rx=success')) {
            window.history.replaceState({}, document.title, 'prescriptions.php');
        }
    });
</script>
<?php endif; ?>
</body>

</html>

