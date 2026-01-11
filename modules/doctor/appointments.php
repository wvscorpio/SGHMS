<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../../auth/login.php");
    exit;
}

$doctorName = $_SESSION['fullname'] ?? 'Doctor';
?>

<?php
require_once "../../db/dbcon.php";

/* TEMP – unchanged */
$doctorID = "DOC001";
$message = "";

/* ================= HANDLE STATUS UPDATE ================= */
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

/* ================= FETCH APPOINTMENTS ================= */
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Appointments</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-100 min-h-screen">

<div class="flex min-h-screen">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-slate-900 text-white flex flex-col">
        <div class="px-6 py-5 text-xl font-bold border-b border-slate-700">
            SGHMS
        </div>

        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="dashboard.php" class="block px-4 py-2 rounded hover:bg-slate-800">Dashboard</a>
            <a href="appointments.php" class="block px-4 py-2 rounded bg-slate-800 font-semibold">Appointments</a>
            <a href="schedule.php" class="block px-4 py-2 rounded hover:bg-slate-800">Schedule</a>
        </nav>

        <div class="px-4 py-4 border-t border-slate-700">
            <a href="../../auth/logout.php"
               class="block text-center px-4 py-2 bg-red-600 rounded hover:bg-red-700">
                Logout
            </a>
        </div>
    </aside>

    <!-- MAIN -->
    <div class="flex-1 flex flex-col">

        <!-- HEADER -->
        <header class="bg-white shadow px-8 py-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-lg">
                <?= strtoupper(substr($doctorName, 0, 1)) ?>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800">Dr. <?= htmlspecialchars($doctorName) ?></h1>
                <p class="text-sm text-slate-500">Appointments Management</p>
            </div>
        </header>

        <!-- CONTENT -->
        <main class="flex-1 p-8">

            <?php if ($message): ?>
                <div class="mb-4 p-3 rounded bg-green-100 text-green-700 font-medium">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow overflow-hidden">

                <table class="min-w-full">
                    <thead class="bg-slate-800 text-white">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Time</th>
                            <th class="px-4 py-3">Reason</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                    <?php while ($row = $appointments->fetch_assoc()): ?>

                        <?php
                            $statusStyle = match ($row['status']) {
                                'Pending'   => 'bg-amber-100 text-amber-700',
                                'Confirmed' => 'bg-blue-100 text-blue-700',
                                'Completed' => 'bg-green-100 text-green-700',
                                'Cancelled' => 'bg-red-100 text-red-700',
                                default     => 'bg-slate-100 text-slate-600'
                            };
                        ?>

                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-center"><?= $row['appointmentDate'] ?></td>
                            <td class="px-4 py-3 text-center"><?= $row['appointmentTime'] ?></td>
                            <td class="px-4 py-3 text-center"><?= htmlspecialchars($row['reason']) ?></td>

                            <!-- STATUS BADGE -->
                            <td class="px-4 py-3 text-center">
                                <span class="px-4 py-1 rounded-full font-semibold text-sm <?= $statusStyle ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>

                            <!-- BEAUTIFUL ACTION BUTTON -->
                            <td class="px-4 py-3 text-center">
                                <form method="post">
                                    <input type="hidden" name="appointmentID" value="<?= $row['appointmentID'] ?>">

                                    <select name="status"
                                            onchange="this.form.submit()"
                                            class="appearance-none cursor-pointer
                                                   px-4 py-2 rounded-full font-semibold text-sm
                                                   border shadow-sm
                                                   bg-white hover:shadow-md transition
                                                   <?= $statusStyle ?>">
                                        <option disabled selected>Change status ▾</option>
                                        <option value="Pending">🟡 Pending</option>
                                        <option value="Confirmed">🔵 Confirmed</option>
                                        <option value="Completed">🟢 Completed</option>
                                        <option value="Cancelled">🔴 Cancelled</option>
                                    </select>
                                </form>
                            </td>
                        </tr>

                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- BACK BUTTON -->
            <div class="mt-6">
                <form action="dashboard.php">
                    <button type="submit"
                            class="px-6 py-2 rounded-full bg-indigo-600 text-white font-semibold hover:bg-indigo-700 shadow">
                        ← Back to Dashboard
                    </button>
                </form>
            </div>

        </main>
    </div>
</div>

</body>
</html>
