<?php
require_once __DIR__ . '/../../../includes/init.php';
requireRole('doctor');

$patientModel = new PatientModel($conn);
$patients = $patientModel->getAll();
$selected = null;

if (!empty($_GET['id'])) {
    $selected = $patientModel->getById($_GET['id']);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Patients - SGHMS</title>
    <link rel="stylesheet" href="../../../presentation/assets/Css/style.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; padding: 20px; }
        .container { max-width: 900px; margin: auto; }
        .panel { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #eee; }
        a.btn { color: #4e73df; }
    </style>
</head>
<body>
<div class="container">
    <h2>Patient Information</h2>
    <div class="panel">
        <table>
            <tr><th>ID</th><th>Name</th><th>Age</th><th>Gender</th><th>Contact</th><th>Action</th></tr>
            <?php foreach ($patients as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['patientID']) ?></td>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><?= htmlspecialchars($p['age']) ?></td>
                <td><?= htmlspecialchars($p['gender']) ?></td>
                <td><?= htmlspecialchars($p['contactNumber']) ?></td>
                <td><a class="btn" href="?id=<?= urlencode($p['patientID']) ?>">View</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <?php if ($selected): ?>
    <div class="panel">
        <h3>Medical History — <?= htmlspecialchars($selected['name']) ?></h3>
        <p><strong>Patient ID:</strong> <?= htmlspecialchars($selected['patientID']) ?></p>
        <p><strong>Medical History:</strong> <?= nl2br(htmlspecialchars($selected['medicalHistory'])) ?></p>
        <p><a href="prescribe.php?search=<?= urlencode($selected['name']) ?>">Prescribe Medication</a></p>
    </div>
    <?php endif; ?>

    <a href="../../../presentation/modules/doctor/dashboard.php">← Back to Dashboard</a>
</div>
</body>
</html>
