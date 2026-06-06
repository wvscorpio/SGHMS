<?php
require_once __DIR__ . '/../../includes/init.php';
requireRole('patient');

$patientID = resolvePatientId($conn);
$patientModel = new PatientModel($conn);
$appointmentModel = new AppointmentModel($conn);
$prescriptionController = new PrescriptionController($conn);

$patient = $patientModel->getById($patientID);
$appointments = $appointmentModel->getByPatient($patientID);
$prescriptions = $prescriptionController->getByPatient($patientID);
$prescriptionsByAppointment = [];
foreach ($prescriptions as $rx) {
    if (!empty($rx['appointmentID'])) {
        $prescriptionsByAppointment[$rx['appointmentID']] = $rx;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Medical History - SGHMS</title>
    <link rel="stylesheet" href="../css/patient.css">
    <style>
        .container { padding: 30px 40px; }
        .panel { background: #fff; padding: 24px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #eee; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #eee; text-align: left; }
    </style>
</head>
<body>
    <div class="header" style="padding:20px;background:#fff;border-bottom:1px solid #ddd;">
        <h1 style="margin:0;">Medical History</h1>
        <p><a href="patientDashboard.php">← Back to Dashboard</a></p>
    </div>
    <div class="container">
        <?php if ($patient): ?>
        <div class="panel">
            <h3>Patient Profile</h3>
            <p><strong>Name:</strong> <?= htmlspecialchars($patient['name']) ?></p>
            <p><strong>Patient ID:</strong> <?= htmlspecialchars($patient['patientID']) ?></p>
            <p><strong>Age:</strong> <?= htmlspecialchars($patient['age']) ?></p>
            <p><strong>Gender:</strong> <?= htmlspecialchars($patient['gender']) ?></p>
            <p><strong>Contact:</strong> <?= htmlspecialchars($patient['contactNumber']) ?></p>
            <p><strong>Medical History:</strong> <?= nl2br(htmlspecialchars($patient['medicalHistory'])) ?></p>
        </div>
        <?php endif; ?>

        <div class="panel">
            <h3>Prescriptions by Appointment</h3>
            <table>
                <tr>
                    <th>Appointment</th>
                    <th>Diagnosed Date</th>
                    <th>Prescription ID</th>
                    <th>Doctor</th>
                    <th>Medicine</th>
                    <th>Status</th>
                </tr>
                <?php foreach ($appointments as $appt): ?>
                    <?php $rx = $prescriptionsByAppointment[$appt['appointmentID']] ?? null; ?>
                    <?php if (!$rx) continue; ?>
                    <tr>
                        <td><?= htmlspecialchars($appt['appointmentID']) ?></td>
                        <td><?= !empty($appt['diagnosedDate']) ? htmlspecialchars(date('d M Y', strtotime($appt['diagnosedDate']))) : '—' ?></td>
                        <td><?= htmlspecialchars($rx['prescriptionID']) ?></td>
                        <td><?= htmlspecialchars(formatDoctorDisplayName($rx['doctorName'] ?? 'Doctor')) ?></td>
                        <td><?= htmlspecialchars($rx['medicineName']) ?></td>
                        <td><?= htmlspecialchars(prescriptionStatusLabel($rx['status'] ?? RX_STATUS_PENDING, 'patient')) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($prescriptionsByAppointment)): ?>
                <tr><td colspan="6">No prescription records linked to appointments yet.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
</body>
</html>
