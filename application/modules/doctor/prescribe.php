<?php
require_once __DIR__ . '/../../../includes/init.php';
requireRole('doctor');

$doctorID = getLinkedRefId($conn, 'doctor', $_SESSION['username']) ?? 'DOC001';
$prescriptionController = new PrescriptionController($conn);
$message = '';
$msgClass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_prescription'])) {
    $result = $prescriptionController->createPrescription([
        'medicineName' => trim($_POST['medicineName'] ?? ''),
        'dosage' => trim($_POST['dosage'] ?? ''),
        'duration' => trim($_POST['duration'] ?? ''),
        'instructions' => trim($_POST['instructions'] ?? ''),
    ], $_POST['patientID'], $doctorID);

    $message = $result['message'] ?? 'Unknown error';
    $msgClass = ($result['status'] === 'Success') ? 'success' : 'error';
}

$patients = (new PatientModel($conn))->getAll();
$searchResults = [];
if (!empty($_GET['search'])) {
    $searchResults = $prescriptionController->searchPatient($_GET['search']);
}

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
<html>
<head>
    <title>Create Prescription - SGHMS</title>
    <link rel="stylesheet" href="../../../presentation/assets/Css/style.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; padding: 20px; }
        .container { max-width: 800px; margin: auto; background: #fff; padding: 25px; border-radius: 8px; }
        label { display: block; margin-top: 12px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; margin-top: 4px; box-sizing: border-box; }
        .msg.success { color: green; } .msg.error { color: red; }
        button { margin-top: 15px; padding: 10px 20px; background: #4e73df; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
<div class="container">
    <h2>Create Prescription</h2>
    <?php if ($message): ?><p class="msg <?= $msgClass ?>"><?= htmlspecialchars($message) ?></p><?php endif; ?>

    <form method="get" style="margin-bottom:20px;">
        <label>Search Patient</label>
        <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Enter patient name">
        <button type="submit">Search</button>
    </form>

    <form method="post">
        <label>Patient</label>
        <select name="patientID" required>
            <option value="">Select patient</option>
            <?php
            $list = !empty($searchResults) ? $searchResults : $patients;
            $selectedPatient = $_GET['patientID'] ?? '';
            foreach ($list as $p):
            ?>
                <option value="<?= $p['patientID'] ?>" <?= $selectedPatient === $p['patientID'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name'] . ' (' . $p['patientID'] . ')') ?></option>
            <?php endforeach; ?>
        </select>

        <label>Medicine Name</label>
        <input type="text" name="medicineName" list="commonMedicinesList" required
               placeholder="Type medicine name" autocomplete="off">
        <datalist id="commonMedicinesList">
            <?php foreach ($commonMedicines as $medicine): ?>
                <option value="<?= htmlspecialchars($medicine) ?>"></option>
            <?php endforeach; ?>
        </datalist>
        <small style="color:#666;">Type any medicine. Pick from suggestions only if it matches a common name.</small>

        <label>Dosage</label>
        <input type="text" name="dosage" required placeholder="e.g. 2 tablets daily">

        <label>Duration</label>
        <input type="text" name="duration" required placeholder="e.g. 7 days">

        <label>Instructions</label>
        <textarea name="instructions" rows="3" required placeholder="Additional medical instructions"></textarea>

        <button type="submit" name="create_prescription">Submit Prescription</button>
    </form>
    <br>
    <a href="../../../presentation/modules/doctor/dashboard.php">← Back to Dashboard</a>
</div>
</body>
</html>
