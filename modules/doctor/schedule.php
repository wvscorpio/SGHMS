<?php
// FILE: modules/doctor/schedule.php
// Use Case: UC-3 Manage Doctor Information (Update Availability)

session_start();

/* ✅ ADDED: login + role protection ONLY */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../../db/dbcon.php";

/*
 TEMP SETUP (for testing only)
 Later this will come from login
*/

/* ✅ ADDED: use session doctorID if exists, else TEMP */
$doctorID = $_SESSION['doctorID'] ?? "DOC001";

$message = "";

/* =========================
   HANDLE FORM SUBMISSION
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $day       = $_POST['day'];
    $startTime = $_POST['startTime'];
    $endTime   = $_POST['endTime'];

    // 1. Basic validation
    if ($startTime >= $endTime) {
        $message = "End time must be later than start time.";
    } else {

        // 2. Check schedule conflict
        $checkSql = "
            SELECT * FROM doctor_schedule
            WHERE doctorID = ?
              AND dayOfWeek = ?
              AND NOT (endTime <= ? OR startTime >= ?)
        ";
        $stmt = $conn->prepare($checkSql);
        $stmt->bind_param("ssss", $doctorID, $day, $startTime, $endTime);
        $stmt->execute();
        $conflict = $stmt->get_result()->num_rows;

        if ($conflict > 0) {
            $message = "Schedule conflict detected. Please choose another time.";
        } else {

            // 3. Save availability
            $insertSql = "
                INSERT INTO doctor_schedule (doctorID, dayOfWeek, startTime, endTime)
                VALUES (?, ?, ?, ?)
            ";
            $stmt = $conn->prepare($insertSql);
            $stmt->bind_param("ssss", $doctorID, $day, $startTime, $endTime);
            $stmt->execute();

            $message = "Availability updated successfully.";
        }
    }
}

/* =========================
   GET CURRENT SCHEDULE
   ========================= */
$listSql = "
    SELECT dayOfWeek, startTime, endTime
    FROM doctor_schedule
    WHERE doctorID = ?
    ORDER BY dayOfWeek, startTime
";
$stmt = $conn->prepare($listSql);
$stmt->bind_param("s", $doctorID);
$stmt->execute();
$scheduleList = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <title>Doctor Schedule</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            padding: 20px;
        }
        .container {
            background: #ffffff;
            padding: 20px;
            width: 600px;
            border-radius: 8px;
        }
        h2 {
            margin-top: 0;
        }
        label {
            display: inline-block;
            width: 100px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th, td {
            padding: 8px;
            text-align: center;
        }
        .msg {
            color: red;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="container">

    <h2>Update Availability</h2>

    <!-- Message -->
    <?php if ($message != ""): ?>
        <p class="msg"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <!-- Update Availability Form -->
    <form method="post">
        <p>
            <label>Day:</label>
            <select name="day" required>
                <option value="">-- Select --</option>
                <option>Monday</option>
                <option>Tuesday</option>
                <option>Wednesday</option>
                <option>Thursday</option>
                <option>Friday</option>
            </select>
        </p>

        <p>
            <label>Start Time:</label>
            <input type="time" name="startTime" required>
        </p>

        <p>
            <label>End Time:</label>
            <input type="time" name="endTime" required>
        </p>

        <p>
            <button type="submit">Save Availability</button>
        </p>
    </form>

    <hr>

    <!-- Current Schedule -->
    <h3>My Current Schedule</h3>

    <table>
        <tr>
            <th>Day</th>
            <th>Start Time</th>
            <th>End Time</th>
        </tr>

        <?php if ($scheduleList->num_rows > 0): ?>
            <?php while ($row = $scheduleList->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['dayOfWeek']) ?></td>
                    <td><?= htmlspecialchars($row['startTime']) ?></td>
                    <td><?= htmlspecialchars($row['endTime']) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="3">No schedule added yet.</td>
            </tr>
        <?php endif; ?>
    </table>

    <br>
    <a href="dashboard.php">← Back to Dashboard</a>

</div>

</body>
</html>