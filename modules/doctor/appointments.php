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
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['appointmentID'], $_POST['status'])) {
    $appointmentID = $_POST['appointmentID'];
    $status = $_POST['status'];

    $stmt = $conn->prepare(
        "UPDATE appointment SET status=? WHERE appointmentID=? AND doctorID=?"
    );
    $stmt->bind_param("sss", $status, $appointmentID, $doctorID);

    if ($stmt->execute()) {
        $message = "Status updated successfully.";
    }

    $stmt->close();
}

/* =========================
   FETCH APPOINTMENTS
========================= */
$stmt = $conn->prepare(
    "SELECT appointmentID, appointmentDate, appointmentTime, reason, status
     FROM appointment
     WHERE doctorID = ?
     ORDER BY appointmentDate DESC"
);
$stmt->bind_param("s", $doctorID);
$stmt->execute();
$appointments = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Appointments</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th, td {
            padding: 8px;
            text-align: center;
        }

        /* STATUS BADGES */
        .status {
            padding: 5px 10px;
            border-radius: 15px;
            color: #fff;
            font-size: 13px;
            display: inline-block;
        }
        .pending { background-color: #f0ad4e; }
        .confirmed { background-color: #0275d8; }
        .completed { background-color: #5cb85c; }
        .cancelled { background-color: #d9534f; }

        .msg {
            color: green;
        }
        button, select {
            padding: 4px 8px;
        }
    </style>
</head>

<body>

<div class="container">
    <h2>Manage Appointments</h2>

    <?php if ($message): ?>
        <p class="msg"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <table>
        <tr>
            <th>Date</th>
            <th>Time</th>
            <th>Reason</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>

        <?php if ($appointments->num_rows > 0): ?>
            <?php while ($row = $appointments->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['appointmentDate']) ?></td>
                    <td><?= htmlspecialchars($row['appointmentTime']) ?></td>
                    <td><?= htmlspecialchars($row['reason']) ?></td>

                    <!-- STATUS DISPLAY (4 STATES) -->
                    <td>
                        <?php
                        $status = $row['status'];
                        if ($status === 'Pending') {
                            echo '<span class="status pending">Pending</span>';
                        } elseif ($status === 'Approved' || $status === 'Confirmed') {
                            echo '<span class="status confirmed">Confirmed</span>';
                        } elseif ($status === 'Completed') {
                            echo '<span class="status completed">Completed</span>';
                        } elseif ($status === 'Cancelled') {
                            echo '<span class="status cancelled">Cancelled</span>';
                        }
                        ?>
                    </td>

                    <!-- ACTIONS (UNCHANGED LOGIC) -->
                    <td>
                        <?php if ($row['status'] === 'Pending'): ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="appointmentID" value="<?= $row['appointmentID'] ?>">
                                <input type="hidden" name="status" value="Approved">
                                <button type="submit">Approve</button>
                            </form>

                            <form method="post" style="display:inline;">
                                <input type="hidden" name="appointmentID" value="<?= $row['appointmentID'] ?>">
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