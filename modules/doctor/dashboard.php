<?php
// FILE: modules/doctor/dashboard.php

session_start();

/* ✅ ADDED: login + role protection ONLY */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../../db/dbcon.php";

/* ✅ ADDED: use session doctor if exists, otherwise keep TEMP */
$doctorID = $_SESSION['doctorID'] ?? "DOC001";

/* ❌ EXISTING LOGIC — NOT CHANGED */
$sql = "SELECT * FROM doctor WHERE doctorID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $doctorID);
$stmt->execute();
$result = $stmt->get_result();

$doctor = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="../../assets/css/style.css">

    <meta charset="UTF-8">
    <title>Doctor Dashboard</title>
    <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>

<h1>Doctor Dashboard</h1>

<?php if ($doctor): ?>
    <div>
        <p><strong>ID:</strong> <?= htmlspecialchars($doctor['doctorID']) ?></p>
        <p><strong>Name:</strong> <?= htmlspecialchars($doctor['name']) ?></p>
        <p><strong>Specialization:</strong> <?= htmlspecialchars($doctor['specialization']) ?></p>
        <p><strong>Contact:</strong> <?= htmlspecialchars($doctor['contactDetails']) ?></p>
    </div>
<?php else: ?>
    <p>Doctor record not found.</p>
<?php endif; ?>

<hr>

<h3>Quick Actions</h3>
<ul>
    <li><a href="schedule.php">Update Availability</a></li>
    <li><a href="appointments.php">View Appointments</a></li>
</ul>

</body>
</html>
