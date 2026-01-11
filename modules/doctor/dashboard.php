<?php
// ---- SESSION & ACCESS CONTROL (fixed, no duplicate session_start) ----
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
// ---- DATABASE CONNECTION ----
require_once "../../db/dbcon.php";

// ---- FETCH DOCTOR INFO (DoctorID REMOVED COMPLETELY) ----
// NOTE: LOGIC UNCHANGED
$sql = "SELECT * FROM doctor LIMIT 1";
$result = $conn->query($sql);
$doctor = $result ? $result->fetch_assoc() : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Dashboard</title>
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
            <a href="dashboard.php"
               class="block px-4 py-2 rounded bg-slate-800 font-semibold">
                Dashboard
            </a>
            <a href="appointments.php"
               class="block px-4 py-2 rounded hover:bg-slate-800 transition">
                Appointments
            </a>
            <a href="schedule.php"
               class="block px-4 py-2 rounded hover:bg-slate-800 transition">
                Schedule
            </a>
        </nav>

        <div class="px-4 py-4 border-t border-slate-700">
            <a href="../../auth/logout.php"
               class="block text-center px-4 py-2 bg-red-600 rounded hover:bg-red-700 transition">
                Logout
            </a>
        </div>
    </aside>

    <!-- MAIN AREA -->
    <div class="flex-1 flex flex-col">

        <!-- HEADER -->
        <header class="bg-white shadow px-8 py-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">
                    Doctor Dashboard
                </h1>
                <p class="text-sm text-slate-500">
                    Welcome back, <?= htmlspecialchars($doctorName) ?>
                </p>
            </div>
        </header>

        <!-- CONTENT -->
        <main class="flex-1 p-8 space-y-8">

            <?php if ($doctor): ?>
            <!-- ⭐ ADVANCED DOCTOR PROFILE CARD -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <div class="flex flex-col md:flex-row gap-8 items-center md:items-start">

                    <!-- RANDOM DOCTOR IMAGE -->
                    <div class="relative">
                        <img
                            src="https://static.vecteezy.com/system/resources/previews/015/412/022/non_2x/doctor-round-avatar-medicine-flat-avatar-with-male-doctor-medical-clinic-team-round-icon-medical-collection-illustration-vector.jpg"
                            alt="Doctor Profile"
                            class="w-32 h-32 rounded-full object-cover shadow-lg border-4 border-white"
                        />
                        <!-- Online indicator -->
                        <span class="absolute bottom-2 right-2 w-4 h-4 bg-green-500
                                     rounded-full border-2 border-white"></span>
                    </div>

                    <!-- DETAILS -->
                    <div class="flex-1">
                        <h2 class="text-2xl font-bold text-slate-800 mb-1">
                            <?= htmlspecialchars($doctor['name']) ?>
                        </h2>
                        <p class="text-indigo-600 font-semibold mb-4">
                            <?= htmlspecialchars($doctor['specialization']) ?>
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-slate-700">

                            <!-- Contact -->
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-lg bg-indigo-100
                                            text-indigo-600 flex items-center justify-center text-xl">
                                    📞
                                </div>
                                <div>
                                    <p class="text-sm font-semibold">Contact</p>
                                    <p><?= htmlspecialchars($doctor['contactDetails']) ?></p>
                                </div>
                            </div>

                            <!-- Specialization -->
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-lg bg-green-100
                                            text-green-600 flex items-center justify-center text-xl">
                                    🏥
                                </div>
                                <div>
                                    <p class="text-sm font-semibold">Specialization</p>
                                    <p><?= htmlspecialchars($doctor['specialization']) ?></p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
                <div class="bg-red-100 text-red-700 p-4 rounded">
                    Doctor record not found.
                </div>
            <?php endif; ?>

            <!-- QUICK ACTIONS -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h3 class="text-xl font-bold text-slate-800 mb-6">
                    Quick Actions
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <a href="schedule.php"
                       class="flex items-center justify-between p-6 rounded-xl
                              bg-indigo-600 text-white hover:bg-indigo-700
                              transition shadow-lg">
                        <div>
                            <h4 class="text-lg font-semibold">Update Availability</h4>
                            <p class="text-sm opacity-90">Manage your working schedule</p>
                        </div>
                        <span class="text-3xl">📅</span>
                    </a>

                    <a href="appointments.php"
                       class="flex items-center justify-between p-6 rounded-xl
                              bg-slate-800 text-white hover:bg-slate-900
                              transition shadow-lg">
                        <div>
                            <h4 class="text-lg font-semibold">View Appointments</h4>
                            <p class="text-sm opacity-90">Manage patient bookings</p>
                        </div>
                        <span class="text-3xl">🗂️</span>
                    </a>
                </div>
            </div>

        </main>
    </div>
</div>

</body>
</html>
