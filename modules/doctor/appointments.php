<?php
// FILE: modules/doctor/appointments.php
// Purpose: Doctor views and manages appointments

session_start();
require_once "../../db/dbcon.php";

/*
 TEMP (for testing only)
 Later doctorID comes from login
*/
$doctorID = "DOC001";
$message = "";

/* =========================
   HANDLE STATUS UPDATE
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $appointmentID = $_POST['appointmentID'];
    $newStatus     = $_POST['status'];

    $updateSql = "
        UPDATE appointment
        SET status = ?
        WHERE appointmentID = ?
          AND doctorID = ?
    ";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("sss", $newStatus, $appointmentID, $doctorID);
    $stmt->execute();

    $message = "Appointment status updated.";
}

/* =========================
   FETCH APPOINTMENTS
   ========================= */
$sql = "
    SELECT appointmentID, appointmentDate, appointmentTime,
           reason, status
    FROM appointment
    WHERE doctorID = ?
    ORDER BY appointmentDate, appointmentTime
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $doctorID);
$stmt->execute();
$appointments = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Doctor Appointments</title>
   <link rel="stylesheet" href="../../assets/css/style.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            padding: 20px;
        }
        .container {
            background: #fff;
            padding: 20px;
            width: 800px;
            border-radius: 8px;
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
            color: green;
        }
        button {
            padding: 4px 8px;
        }
    </style>
</head>
<body>

<div class="container">

    <h2>My Appointments</h2>

    <?php if ($message != ""): ?>
        <p class="msg"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <table>
        <tr>
            <th>Date</th>
            <th>Time</th>
            <th>Reason</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php if ($appointments->num_rows > 0): ?>
            <?php while ($row = $appointments->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['appointmentDate']) ?></td>
                    <td><?= htmlspecialchars($row['appointmentTime']) ?></td>
                    <td><?= htmlspecialchars($row['reason']) ?></td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                    <td>
                        <?php if ($row['status'] === 'Pending'): ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="appointmentID"
                                       value="<?= $row['appointmentID'] ?>">
                                <input type="hidden" name="status" value="Approved">
                                <button type="submit">Approve</button>
                            </form>

                            <form method="post" style="display:inline;">
                                <input type="hidden" name="appointmentID"
                                       value="<?= $row['appointmentID'] ?>">
                                <input type="hidden" name="status" value="Cancelled">
                                <button type="submit">Cancel</button>
                            </form>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5">No appointments found.</td>
            </tr>
        <?php endif; ?>
    </table>

    <br>
    <a href="dashboard.php">← Back to Dashboard</a>

</div>

</body>
</html>