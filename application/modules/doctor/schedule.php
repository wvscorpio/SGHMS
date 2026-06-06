<?php
require_once __DIR__ . '/../../../includes/init.php';
requireRole('doctor');

$doctorID = resolveDoctorId($conn);
$doctorModel = new DoctorModel($conn);
$doctor = $doctorModel->getById($doctorID);
$doctorName = htmlspecialchars(formatDoctorDisplayName($doctor['name'] ?? ($_SESSION['fullname'] ?? 'Doctor')));
$message = '';
$isError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $doctorModel->setSchedule(
        $doctorID,
        $_POST['day'],
        $_POST['startTime'],
        $_POST['endTime']
    );
    $message = $result['message'];
    $isError = ($result['status'] ?? '') !== 'Success';
}

$scheduleList = $doctorModel->retrieveSchedule($doctorID);

function formatScheduleTime(string $time): string
{
    $dt = DateTime::createFromFormat('H:i:s', $time) ?: DateTime::createFromFormat('H:i', $time);
    return $dt ? $dt->format('g:i A') : $time;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Availability | SGHMS</title>
    <link rel="stylesheet" href="../../../presentation/css/doctorDashboard.css">
    <link rel="stylesheet" href="../../../presentation/css/doctorSchedule.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="doctor-dashboard schedule-page">
    <div class="doc-header">
        <div>
            <h1>Sarawak General Hospital</h1>
            <p>Welcome, <?= $doctorName ?></p>
        </div>
        <div class="doc-header-actions">
            <a href="schedule.php" class="btn-availability is-current">
                <i class="fas fa-calendar-check"></i> Update Availability
            </a>
            <form method="post" action="../../../presentation/auth/logout.php">
                <button type="submit" class="btn-logout">Logout</button>
            </form>
        </div>
    </div>

    <div class="doc-content">
        <div class="page-intro">
            <h2>Update Availability</h2>
            <p>Set your weekly working hours so patients can book appointments during your available times.</p>
        </div>

        <div class="schedule-grid">
            <section class="main-card schedule-form-card">
                <div class="card-heading">
                    <i class="fas fa-clock"></i>
                    <div>
                        <h3>Add or Update Hours</h3>
                        <span>Choose a day and time range</span>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="schedule-alert <?= $isError ? 'error' : 'success' ?>">
                        <i class="fas fa-<?= $isError ? 'circle-exclamation' : 'circle-check' ?>"></i>
                        <span><?= htmlspecialchars($message) ?></span>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="schedule-field">
                        <label for="day">Day of Week</label>
                        <select name="day" id="day" required>
                            <option value="">Select a day</option>
                            <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day): ?>
                                <option value="<?= $day ?>"><?= $day ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="schedule-time-row">
                        <div class="schedule-field">
                            <label for="startTime">Start Time</label>
                            <input type="time" name="startTime" id="startTime" required>
                        </div>
                        <div class="schedule-field">
                            <label for="endTime">End Time</label>
                            <input type="time" name="endTime" id="endTime" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-save-schedule">
                        <i class="fas fa-save"></i> Save Availability
                    </button>
                </form>
            </section>

            <section class="main-card schedule-list-card">
                <div class="card-heading">
                    <h3>My Current Schedule</h3>
                    <span class="schedule-count">
                        <i class="fas fa-list"></i>
                        <?= count($scheduleList) ?> day<?= count($scheduleList) === 1 ? '' : 's' ?>
                    </span>
                </div>

                <?php if (count($scheduleList) > 0): ?>
                    <div class="schedule-table-wrap">
                        <table class="schedule-table">
                            <thead>
                                <tr>
                                    <th>Day</th>
                                    <th>Start</th>
                                    <th>End</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($scheduleList as $row): ?>
                                    <tr>
                                        <td>
                                            <span class="day-badge">
                                                <i class="fas fa-calendar-day"></i>
                                                <?= htmlspecialchars($row['dayOfWeek']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="time-cell">
                                                <i class="fas fa-play"></i>
                                                <?= htmlspecialchars(formatScheduleTime($row['startTime'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="time-cell">
                                                <i class="fas fa-stop"></i>
                                                <?= htmlspecialchars(formatScheduleTime($row['endTime'])) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="schedule-empty">
                        <i class="fas fa-calendar-xmark"></i>
                        <p>No schedule added yet</p>
                        <small>Use the form to add your first available day.</small>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <a href="../../../presentation/modules/doctor/dashboard.php" class="schedule-back">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</body>
</html>
