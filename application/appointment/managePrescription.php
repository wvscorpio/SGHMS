<?php
require_once __DIR__ . '/../../includes/init.php';
requireRole('staff');

$prescriptionController = new PrescriptionController($conn);

if (isset($_POST['prescriptionID'], $_POST['mark_ready'])) {
    $result = $prescriptionController->markReadyForCollection($_POST['prescriptionID']);
    $_SESSION['popup'] = $result['message'];
    header('Location: managePrescription.php');
    exit;
}

$pendingPrescriptions = $prescriptionController->getPendingForStaff();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Manage Prescriptions | SGHMS</title>
    <?php include __DIR__ . '/partials/staffHead.php'; ?>
</head>
<body class="staff-dashboard">

<?php if (isset($_SESSION['popup'])): ?>
    <div class="toast"><?= htmlspecialchars($_SESSION['popup']); ?></div>
    <?php unset($_SESSION['popup']); ?>
<?php endif; ?>

<?php $activeTab = 'prescriptions'; include __DIR__ . '/partials/staffNav.php'; ?>

<div class="staff-content">
    <section class="cardheader">
        <div class="card-header">
            <div class="card-header-text">
                <h3>Manage Prescriptions</h3>
                <p>Prepare medicines prescribed by doctors, then release them to patients for collection</p>
            </div>
            <div class="card-header-tools">
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Search prescription or patient">
                </div>
            </div>
        </div>

        <div class="table-wrap">
            <table class="appointment-table" id="prescriptionTable">
                <thead>
                    <tr>
                        <th>Prescription ID</th>
                        <th>Appointment ID</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Medicine</th>
                        <th>Dosage</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pendingPrescriptions) > 0): ?>
                        <?php foreach ($pendingPrescriptions as $rx): ?>
                            <tr class="searchable-row">
                                <td><?= htmlspecialchars($rx['prescriptionID']) ?></td>
                                <td><?= htmlspecialchars($rx['appointmentID'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($rx['patientName'] ?? '—') ?></td>
                                <td><?= htmlspecialchars(formatDoctorDisplayName($rx['doctorName'] ?? 'Doctor')) ?></td>
                                <td><?= htmlspecialchars($rx['medicineName']) ?></td>
                                <td><?= htmlspecialchars($rx['dosage']) ?></td>
                                <td>
                                    <span class="badge pending">
                                        <?= htmlspecialchars(prescriptionStatusLabel($rx['status'] ?? RX_STATUS_PENDING, 'staff')) ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <form method="post" style="margin:0;display:inline;">
                                        <input type="hidden" name="prescriptionID" value="<?= htmlspecialchars($rx['prescriptionID']) ?>">
                                        <button type="submit" name="mark_ready" class="btn-mark-ready"
                                                onclick="return confirm('Mark this prescription ready for patient collection?')">
                                            <i class="fas fa-check"></i> Mark Ready
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="empty-cell">No prescriptions waiting for preparation.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function () {
    const keyword = this.value.toLowerCase();
    document.querySelectorAll('.searchable-row').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(keyword) ? '' : 'none';
    });
});
</script>
</body>
</html>
