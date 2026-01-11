<?php
// ---- SESSION & ACCESS CONTROL (NO LOGIC CHANGE) ----
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
// FILE: modules/doctor/schedule.php
// Use Case: UC-3 Manage Doctor Information (Update Availability)

require_once "../../db/dbcon.php";

/*
 TEMP SETUP (for testing only)
 Later this will come from login
*/
$doctorID = "DOC001";

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Schedule</title>
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
            <a href="appointments.php" class="block px-4 py-2 rounded hover:bg-slate-800">Appointments</a>
            <a href="schedule.php" class="block px-4 py-2 rounded bg-slate-800 font-semibold">Schedule</a>
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
        <header class="bg-white shadow px-8 py-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">
                    Doctor Schedule
                </h1>
                <p class="text-sm text-slate-500">
                    Welcome, <?= htmlspecialchars($doctorName) ?>
                </p>
            </div>
        </header>

        <!-- CONTENT -->
        <main class="flex-1 p-8 space-y-8">

            <!-- MESSAGE -->
            <?php if ($message): ?>
                <div class="p-4 rounded-lg 
                    <?= str_contains($message, 'successfully') ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- UPDATE AVAILABILITY CARD -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-xl font-bold text-slate-800 mb-6">
                    Update Availability
                </h2>

                <form method="post" class="grid grid-cols-1 md:grid-cols-3 gap-6">

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Day</label>
                        <select name="day" required
                                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-indigo-500">
                            <option value="">-- Select --</option>
                            <option>Monday</option>
                            <option>Tuesday</option>
                            <option>Wednesday</option>
                            <option>Thursday</option>
                            <option>Friday</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Start Time</label>
                        <input type="time" name="startTime" required
                               class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">End Time</label>
                        <input type="time" name="endTime" required
                               class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="md:col-span-3">
                        <button type="submit"
                                class="px-6 py-3 bg-indigo-600 text-white rounded-lg font-semibold
                                       hover:bg-indigo-700 transition shadow">
                            Save Availability
                        </button>
                    </div>

                </form>
            </div>

            <!-- CURRENT SCHEDULE -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h3 class="text-xl font-bold text-slate-800 mb-6">
                    My Current Schedule
                </h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead class="bg-slate-800 text-white">
                            <tr>
                                <th class="px-4 py-3 text-center">Day</th>
                                <th class="px-4 py-3 text-center">Start Time</th>
                                <th class="px-4 py-3 text-center">End Time</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y">
                        <?php if ($scheduleList->num_rows > 0): ?>
                            <?php while ($row = $scheduleList->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 text-center"><?= htmlspecialchars($row['dayOfWeek']) ?></td>
                                    <td class="px-4 py-3 text-center"><?= htmlspecialchars($row['startTime']) ?></td>
                                    <td class="px-4 py-3 text-center"><?= htmlspecialchars($row['endTime']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-slate-500">
                                    No schedule added yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- BACK -->
            <div>
                <a href="dashboard.php"
                   class="inline-block px-6 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-900">
                    ← Back to Dashboard
                </a>
            </div>

        </main>
    </div>
</div>

</body>
</html>