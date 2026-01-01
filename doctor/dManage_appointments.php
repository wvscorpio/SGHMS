<?php
session_start();
require 'db/dbcon.php'; 

// Simulate logged-in doctor (replace with actual login session later)
$doctorID = 1; // doctorID from database
$stmt = $pdo->prepare("SELECT * FROM doctor WHERE doctorID = ?");
$stmt->execute([$doctorID]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$doctor) die("Doctor not found");

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateProfile'])) {
    $name = $_POST['name'];
    $specialization = $_POST['specialization'];
    $contactDetails = $_POST['contactDetails'];

    $stmt = $pdo->prepare("UPDATE doctor SET name=?, specialization=?, contactDetails=? WHERE doctorID=?");
    $stmt->execute([$name, $specialization, $contactDetails, $doctorID]);

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle appointment status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateStatus'])) {
    $appointmentID = $_POST['appointmentID'];
    $newStatus = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE appointment SET status=? WHERE appointmentID=? AND doctorID=?");
    $stmt->execute([$newStatus, $appointmentID, $doctorID]);

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch doctor's appointments
$appointments = $pdo->prepare("
    SELECT a.*, p.name AS patientName
    FROM appointment a
    JOIN patient p ON a.patientID = p.patientID
    WHERE a.doctorID = ?
    ORDER BY a.appointmentDate DESC, a.appointmentTime DESC
");
$appointments->execute([$doctorID]);
$appointments = $appointments->fetchAll(PDO::FETCH_ASSOC);

// Helper function for badge color
function getStatusColor($status) {
    switch ($status) {
        case "pending": return "yellow";
        case "confirmed": return "blue";
        case "completed": return "green";
        case "cancelled": return "red";
        default: return "gray";
    }
}
?>

<!DOCTYPE html>
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Doctor Manage Appointments</title>
        <link rel="stylesheet" href="#" />

        <style>
            body { font-family: Arial, sans-serif; background:#f0f0f0; margin:0; padding:0; }
            .header { background:#fff; padding:1rem; display:flex; justify-content:space-between; border-bottom:1px solid #ccc; }
            .card { background:#fff; padding:1rem; margin-bottom:1rem; border-radius:5px; }
            .badge { padding:0.25rem 0.5rem; border-radius:3px; color:#fff; text-transform:capitalize; }
            .badge.yellow { background:#f59e0b; }
            .badge.blue { background:#3b82f6; }
            .badge.green { background:#10b981; }
            .badge.red { background:#ef4444; }
            input, select, textarea { width:100%; padding:0.5rem; margin:0.25rem 0; }
            button { padding:0.5rem 1rem; margin-top:0.5rem; cursor:pointer; }
            .tabs { display:flex; gap:1rem; margin-bottom:1rem; }
            .tab-content { display:none; }
            .tab-content.active { display:block; }
        </style>

        <script>
        function showTab(tabId){
            document.querySelectorAll('.tab-content').forEach(tc=>tc.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
        }
        </script>
    </head>

    <body>
        <div class = "header">
            <div>
                <h1>Sarawak General Hospital</h1>
                <p> Welcome, <?php echo #;?> </p>
            </div>
            <form method = "post" action = "logout.php">
                <button type = "Submit">Logout</button>
            </form>
        </div>

        <!-- Appointment Tab -->
        <div id="appointments" class="tab-content active">
            <h2>My Appointments</h2>
            <?php if(empty($appointments)): ?>
                <div class="card">No appointments yet.</div>
            <?php else: ?>
                <?php foreach ($appointments as $apt): ?>
                    <div class="card">
                        <strong><?= htmlspecialchars($apt['patientName']); ?></strong> (Patient ID: <?= $apt['patientID']; ?>)
                        <span class="badge <?= getStatusColor($apt['status']); ?>"><?= $apt['status']; ?></span>
                        <p>Appointment ID: <?= $apt['appointmentID']; ?></p>
                        <p>Date: <?= date('d-m-Y', strtotime($apt['appointmentDate'])); ?> | Time: <?= $apt['appointmentTime']; ?></p>
                        <p>Reason: <?= htmlspecialchars($apt['reason']); ?></p>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="appointmentID" value="<?= $apt['appointmentID']; ?>">
                            <select name="status">
                                <option value="pending" <?= $apt['status']=='pending'?'selected':''; ?>>Pending</option>
                                <option value="confirmed" <?= $apt['status']=='confirmed'?'selected':''; ?>>Confirmed</option>
                                <option value="completed" <?= $apt['status']=='completed'?'selected':''; ?>>Completed</option>
                                <option value="cancelled" <?= $apt['status']=='cancelled'?'selected':''; ?>>Cancelled</option>
                            </select>
                            <button type="submit" name="updateStatus">Update Status</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </body>
</html>