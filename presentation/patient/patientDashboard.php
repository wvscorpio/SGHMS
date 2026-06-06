<?php
require_once __DIR__ . '/../../includes/init.php';
requireRole('patient');

function parseMedicalHistoryRows(?array $patient): array
{
    $rows = [];
    $known = [
        'Eczema' => ['date' => '2023-06-15', 'notes' => 'Skin allergy, avoid strong soap and dust'],
        'Asthma' => ['date' => '2021-03-10', 'notes' => 'Use inhaler when needed. Avoid cold air.'],
    ];

    if ($patient && !empty($patient['medicalHistory']) && $patient['medicalHistory'] !== 'None recorded') {
        foreach (preg_split('/[,;]+/', $patient['medicalHistory']) as $condition) {
            $condition = trim($condition);
            if ($condition === '') {
                continue;
            }
            $rows[] = [
                'condition' => $condition,
                'date' => $known[$condition]['date'] ?? '-',
                'notes' => $known[$condition]['notes'] ?? 'Recorded in patient profile.',
            ];
        }
    }

    return $rows;
}

function parseAllergies(array $medicalRows): array
{
    $allergyMap = [
        'Eczema' => ['Dust', 'Strong soap'],
        'Asthma' => ['Dust'],
    ];
    $allergies = [];
    foreach ($medicalRows as $row) {
        $condition = $row['condition'];
        if (!isset($allergyMap[$condition])) {
            continue;
        }
        foreach ($allergyMap[$condition] as $allergy) {
            if (!in_array($allergy, $allergies, true)) {
                $allergies[] = $allergy;
            }
        }
    }
    return $allergies;
}

function parseCurrentMedications(array $prescriptions): array
{
    $medications = [];
    foreach ($prescriptions as $rx) {
        $status = normalizePrescriptionStatus($rx['status'] ?? '');
        if (!in_array($status, [RX_STATUS_READY, RX_STATUS_COLLECTED, RX_STATUS_PENDING], true)) {
            continue;
        }
        $name = $rx['medicineName'];
        $instructions = strtolower($rx['instructions'] ?? '');
        if (str_contains($instructions, 'when') || str_contains($instructions, 'as needed') || stripos($name, 'inhaler') !== false) {
            $medications[] = $name . ' (as needed)';
        } else {
            $medications[] = $name;
        }
    }
    return $medications;
}

function buildVisitDiagnosisRows(array $appointments, array $prescriptionsByAppointment): array
{
    $rows = [];
    foreach ($appointments as $appt) {
        if (($appt['status'] ?? '') !== 'Completed' || empty($appt['diagnosedDate'])) {
            continue;
        }
        $rx = $prescriptionsByAppointment[$appt['appointmentID']] ?? null;
        $diagnosis = $rx ? parsePrescriptionDiagnosis($rx['instructions'] ?? '') : '—';
        if ($diagnosis === '—') {
            $diagnosis = $appt['reason'] ?? 'Consultation';
        }
        $rows[] = [
            'condition' => $diagnosis,
            'date' => date('d M Y', strtotime($appt['diagnosedDate'])),
            'notes' => 'Appointment ' . ($appt['appointmentID'] ?? ''),
        ];
    }

    usort($rows, fn($a, $b) => strtotime($b['date']) <=> strtotime($a['date']));
    return $rows;
}

function mergeMedicalHistoryRows(array $profileRows, array $visitRows): array
{
    $merged = $visitRows;
    foreach ($profileRows as $row) {
        $exists = false;
        foreach ($visitRows as $visit) {
            if (strcasecmp($visit['condition'], $row['condition']) === 0) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $merged[] = $row;
        }
    }
    return $merged;
}

function statusBadgeColor(string $status): string
{
    return match ($status) {
        'Pending' => '#f6c23e',
        'Confirmed' => '#4e73df',
        'Cancelled' => '#e74a3b',
        'Completed' => '#1cc88a',
        default => '#6b7280',
    };
}

function notificationFaIcon(string $icon): string
{
    return match ($icon) {
        'check-circle' => 'fa-check-circle',
        'calendar' => 'fa-calendar',
        'prescription' => 'fa-file-medical',
        'heart' => 'fa-heart',
        'calendar-alt' => 'fa-calendar-alt',
        'info-circle' => 'fa-info-circle',
        default => 'fa-bell',
    };
}

function renderNotificationListItem(array $item, string $context = 'sidebar'): void
{
    $unread = !empty($item['unread']);
    $iconClass = notificationFaIcon($item['icon'] ?? 'bell');
    $iconColor = htmlspecialchars($item['iconColor'] ?? '#4e73df');
    $title = htmlspecialchars($item['title'] ?? 'Notification');
    $time = htmlspecialchars($item['time'] ?? '');
    $bodyContent = $item['bodyHtml'] ?? htmlspecialchars($item['body'] ?? '');

    if ($context === 'modal') {
        ?>
        <div class="notification-modal-item <?= $unread ? 'unread' : '' ?>">
            <div class="notification-item-inner">
                <div class="notification-icon" style="background:<?= $iconColor ?>20;color:<?= $iconColor ?>;">
                    <i class="fas <?= $iconClass ?>"></i>
                </div>
                <div class="notification-text">
                    <div class="notification-modal-item-top">
                        <h4><?= $title ?></h4>
                        <span class="time-badge"><?= $time ?></span>
                    </div>
                    <p><?= $bodyContent ?></p>
                </div>
            </div>
        </div>
        <?php
        return;
    }

    ?>
    <div class="notification-item <?= $unread ? 'unread' : '' ?>">
        <div class="notification-item-inner">
            <div class="notification-icon" style="background:<?= $iconColor ?>20;color:<?= $iconColor ?>;">
                <i class="fas <?= $iconClass ?>"></i>
            </div>
            <div class="notification-text">
                <h4><?= $title ?></h4>
                <p><?= $bodyContent ?></p>
                <div class="time"><?= $time ?></div>
            </div>
        </div>
    </div>
    <?php
}

function buildSidebarNotifications(array $myAppointments, array $prescriptions, array $dbNotifications, ?array $upcoming): array
{
    $items = [];

    if ($upcoming) {
        $doc = $upcoming['doc_name'] ?? 'your doctor';
        $dateStr = date('d M Y', strtotime($upcoming['appointmentDate']));
        $timeStr = date('h:i A', strtotime($upcoming['appointmentTime']));
        $items[] = [
            'title' => 'Appointment Reminder',
            'body' => "You have an appointment with {$doc} on {$dateStr} at {$timeStr}.",
            'bodyHtml' => 'You have an appointment with <span class="notif-highlight">'
                . htmlspecialchars($doc) . '</span> on <span class="notif-highlight">'
                . htmlspecialchars("{$dateStr} at {$timeStr}") . '</span>.',
            'time' => '2 hours ago',
            'icon' => 'bell',
            'iconColor' => '#4e73df',
            'unread' => true,
        ];
    }

    foreach ($myAppointments as $appt) {
        if ($appt['status'] === 'Confirmed') {
            $items[] = [
                'title' => 'Appointment Confirmed',
                'body' => 'Your appointment (' . $appt['appointmentID'] . ') on '
                    . date('d M Y', strtotime($appt['appointmentDate'])) . ' at '
                    . date('h:i A', strtotime($appt['appointmentTime'])) . ' has been confirmed.',
                'time' => '1 day ago',
                'icon' => 'calendar',
                'iconColor' => '#1cc88a',
                'unread' => true,
            ];
            break;
        }
    }

    $futureAppts = array_values(array_filter(
        $myAppointments,
        fn($a) => $a['appointmentDate'] >= date('Y-m-d') && $a['status'] !== 'Cancelled'
    ));
    usort($futureAppts, function ($a, $b) {
        $cmp = strcmp($a['appointmentDate'], $b['appointmentDate']);
        return $cmp !== 0 ? $cmp : strcmp($a['appointmentTime'], $b['appointmentTime']);
    });
    if (count($futureAppts) > 1) {
        $later = $futureAppts[1];
        $laterDoc = $later['doc_name'] ?? 'your doctor';
        $laterDate = date('d M Y', strtotime($later['appointmentDate']));
        $laterTime = date('h:i A', strtotime($later['appointmentTime']));
        $items[] = [
            'title' => 'Upcoming Appointment',
            'body' => "You have an appointment on {$laterDate} at {$laterTime} with {$laterDoc}.",
            'time' => '2 days ago',
            'icon' => 'bell',
            'iconColor' => '#f6c23e',
            'unread' => true,
        ];
    }

    foreach ($prescriptions as $rx) {
        if (normalizePrescriptionStatus($rx['status'] ?? '') !== RX_STATUS_READY) {
            continue;
        }
        $items[] = [
            'title' => 'Prescription Ready for Collection',
            'body' => 'Your prescription ' . ($rx['prescriptionID'] ?? '') . ' is ready at the hospital pharmacy.',
            'time' => timeAgo($rx['createdAt'] ?? null),
            'icon' => 'prescription',
            'iconColor' => '#8b5cf6',
            'unread' => true,
        ];
        break;
    }

    foreach ($dbNotifications as $note) {
        $items[] = [
            'title' => 'Notification',
            'body' => $note['messageBody'],
            'time' => timeAgo($note['createdAt'] ?? null),
            'icon' => 'info-circle',
            'iconColor' => '#4e73df',
            'unread' => !(bool) $note['isRead'],
        ];
    }

    if (empty($myAppointments)) {
        $items[] = [
            'title' => 'No Upcoming Appointment',
            'body' => 'You have no upcoming appointment at the moment. Book one to stay on top of your health.',
            'time' => 'Just now',
            'icon' => 'calendar-alt',
            'iconColor' => '#9ca3af',
            'unread' => true,
        ];
    }

    $items[] = [
        'title' => 'Health Tip',
        'body' => 'Drink plenty of water and get enough rest to stay healthy.',
        'time' => '1 week ago',
        'icon' => 'info-circle',
        'iconColor' => '#9ca3af',
        'unread' => false,
    ];

    return $items;
}

function findUpcomingAppointment(array $appointments): ?array
{
    $today = date('Y-m-d');
    $future = array_filter(
        $appointments,
        fn($a) => $a['appointmentDate'] >= $today && !in_array($a['status'], ['Cancelled', 'Completed'], true)
    );
    usort($future, function ($a, $b) {
        $cmp = strcmp($a['appointmentDate'], $b['appointmentDate']);
        return $cmp !== 0 ? $cmp : strcmp($a['appointmentTime'], $b['appointmentTime']);
    });
    return $future[0] ?? null;
}

function timeAgo(?string $datetime): string
{
    if (!$datetime) {
        return 'Just now';
    }
    $diff = time() - strtotime($datetime);
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    }
    if ($diff < 604800) {
        return floor($diff / 86400) . ' days ago';
    }
    return date('d M Y', strtotime($datetime));
}

$patientID = resolvePatientId($conn);
$appointmentController = new AppointmentController($conn);
$appointmentModel = new AppointmentModel($conn);
$doctorModel = new DoctorModel($conn);
$patientModel = new PatientModel($conn);
$notificationController = new NotificationController($conn);
$prescriptionController = new PrescriptionController($conn);

$patient = $patientModel->getById($patientID);
$doctors = $doctorModel->getAll();
$myAppointments = $appointmentModel->getByPatient($patientID);
$prescriptions = $prescriptionController->getByPatient($patientID);
$prescriptionsByAppointment = [];
foreach ($prescriptions as $rx) {
    if (!empty($rx['appointmentID'])) {
        $prescriptionsByAppointment[$rx['appointmentID']] = $rx;
    }
}
$notifications = $notificationController->getByPatient($patientID);
$profileMedicalRows = parseMedicalHistoryRows($patient);
$visitMedicalRows = buildVisitDiagnosisRows($myAppointments, $prescriptionsByAppointment);
$medicalRows = mergeMedicalHistoryRows($profileMedicalRows, $visitMedicalRows);
$allergyRows = parseAllergies($medicalRows);
$medicationRows = parseCurrentMedications($prescriptions);
$status_message = null;
$success_text = '';
$error_text = '';

if (isset($_POST['mark_all_read'])) {
    $notificationController->markAllAsRead($patientID);
    header('Location: patientDashboard.php');
    exit;
}

if (isset($_POST['save_appointment'])) {
    $details = [
        'doctorID' => $_POST['doctor'],
        'date' => $_POST['date'],
        'time' => $_POST['time'],
        'reason' => $_POST['reason'],
        'patientID' => $patientID,
    ];

    if (empty($_POST['appointment_id'])) {
        $result = $appointmentController->submitBooking($details);
        $status_message = ($result['status'] === 'Success') ? 'success' : 'error';
        $success_text = 'Appointment booked successfully.';
        $error_text = $result['message'] ?? 'Failed to book appointment.';
    } else {
        $time = date('H:i:s', strtotime($_POST['time']));
        $ok = $appointmentModel->update($_POST['appointment_id'], [
            'doctorID' => $_POST['doctor'],
            'date' => $_POST['date'],
            'time' => $time,
            'reason' => $_POST['reason'],
            'status' => 'Pending',
            'patientID' => $patientID,
        ]);
        $status_message = $ok ? 'success' : 'error';
        $success_text = 'Appointment updated successfully.';
        $error_text = 'Failed to update appointment.';
    }
}

if (isset($_POST['delete_id'])) {
    $appointmentModel->delete($_POST['delete_id']);
    header('Location: patientDashboard.php');
    exit;
}

if (isset($_POST['confirm_appointment'])) {
    $notificationController->processResponse('Confirm', $_POST['appointment_id']);
    header('Location: patientDashboard.php');
    exit;
}

if (isset($_POST['cancel_appointment'])) {
    $notificationController->processResponse('Cancel', $_POST['appointment_id']);
    header('Location: patientDashboard.php');
    exit;
}

$upcoming = findUpcomingAppointment($myAppointments);
$allNotificationItems = buildSidebarNotifications($myAppointments, $prescriptions, $notifications, $upcoming);
$sidebarItems = array_slice($allNotificationItems, 0, 5);

usort($myAppointments, function ($a, $b) {
    $cmp = strcmp($b['appointmentDate'], $a['appointmentDate']);
    return $cmp !== 0 ? $cmp : strcmp($b['appointmentTime'], $a['appointmentTime']);
});

$displayName = htmlspecialchars($_SESSION['fullname'] ?? 'Patient');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Patient Dashboard - SGHMS</title>
    <?php $patientCssVer = pres_asset_version('css/patient.css'); ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(pres_asset('css/patient.css')); ?>?v=<?php echo $patientCssVer; ?>">
    <link rel="stylesheet" href="../css/patient.css?v=<?php echo $patientCssVer; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="dashboard-page">

    <div class="header">
        <div>
            <h1>Sarawak General Hospital</h1>
            <p>Welcome, <?= $displayName ?></p>
        </div>
        <form method="post" action="../auth/logout.php">
            <button type="submit" class="btn-cancel">Logout</button>
        </form>
    </div>

    <div class="dashboard-toolbar">
        <div>
            <h2>Patient Dashboard</h2>
            <p class="subtitle">View your appointments, medical history and notifications</p>
        </div>
        <button type="button" class="btn-book" onclick="openModal()">
            <i class="fas fa-plus"></i> Book Appointment
        </button>
    </div>

    <div class="dashboard-grid">
        <div class="dashboard-main">

            <!-- Upcoming Appointment -->
            <section class="section-card">
                <div class="section-card-header">
                    <i class="fas fa-calendar-alt"></i>
                    <h3>Upcoming Appointment</h3>
                </div>
                <?php if ($upcoming): ?>
                    <div class="upcoming-feature">
                        <div class="date-box">
                            <div class="date-day"><?= date('d M Y', strtotime($upcoming['appointmentDate'])) ?></div>
                            <div class="date-weekday"><?= date('l', strtotime($upcoming['appointmentDate'])) ?></div>
                        </div>
                        <div class="upcoming-details">
                            <div class="detail-row">
                                <i class="fas fa-user-md"></i>
                                <div>
                                    <strong><?= htmlspecialchars($upcoming['doc_name'] ?? 'Doctor') ?></strong>
                                    <span><?= htmlspecialchars($upcoming['specialization'] ?? '') ?></span>
                                </div>
                            </div>
                            <div class="detail-row">
                                <i class="fas fa-clock"></i>
                                <div>
                                    <strong><?= date('h:i A', strtotime($upcoming['appointmentTime'])) ?></strong>
                                    <span>Duration: 30 min</span>
                                </div>
                            </div>
                            <div class="detail-row">
                                <i class="fas fa-file-medical"></i>
                                <div>
                                    <strong><?= htmlspecialchars($upcoming['reason']) ?></strong>
                                </div>
                            </div>
                            <span class="status-pill" style="background:<?= statusBadgeColor($upcoming['status']) ?>;">
                                <?= htmlspecialchars($upcoming['status']) ?>
                            </span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-calendar-alt"></i></div>
                        <h4>No upcoming appointment</h4>
                        <p>You have no upcoming appointment at the moment.</p>
                        <button type="button" class="btn-outline" onclick="openModal()">Book your first appointment</button>
                    </div>
                <?php endif; ?>
            </section>

            <!-- My Appointments -->
            <section class="section-card">
                <div class="section-card-header">
                    <i class="fas fa-calendar"></i>
                    <h3>My Appointments</h3>
                </div>
                <?php if (count($myAppointments) > 0): ?>
                    <div class="table-wrap">
                        <table class="appointments-table">
                            <thead>
                                <tr>
                                    <th>Appointment ID</th>
                                    <th>Doctor</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($myAppointments as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['appointmentID']) ?></td>
                                        <td><?= htmlspecialchars($row['doc_name'] ?? 'Doctor') ?></td>
                                        <td><?= htmlspecialchars($row['appointmentDate']) ?></td>
                                        <td><?= date('h:i A', strtotime($row['appointmentTime'])) ?></td>
                                        <td><?= htmlspecialchars($row['reason']) ?></td>
                                        <td>
                                            <span class="table-badge" style="background:<?= statusBadgeColor($row['status']) ?>;">
                                                <?= htmlspecialchars($row['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn-view-details" onclick='viewDetails(<?= json_encode([
                                                'appointmentID' => $row['appointmentID'],
                                                'doc_name' => $row['doc_name'] ?? 'Doctor',
                                                'specialization' => $row['specialization'] ?? '',
                                                'appointmentDate' => $row['appointmentDate'],
                                                'appointmentTime' => $row['appointmentTime'],
                                                'reason' => $row['reason'],
                                                'status' => $row['status'],
                                                'diagnosedDate' => $row['diagnosedDate'] ?? null,
                                                'prescription' => isset($prescriptionsByAppointment[$row['appointmentID']])
                                                    ? buildPatientPrescriptionPayload($prescriptionsByAppointment[$row['appointmentID']], $row)
                                                    : null,
                                            ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                                View Details
                                            </button>
                                            <?php if (!empty($prescriptionsByAppointment[$row['appointmentID']])): ?>
                                                <button type="button" class="btn-view-details btn-view-rx"
                                                        onclick='viewPatientPrescription(<?= json_encode(
                                                            buildPatientPrescriptionPayload($prescriptionsByAppointment[$row['appointmentID']], $row),
                                                            JSON_HEX_APOS | JSON_HEX_QUOT
                                                        ) ?>)'>
                                                    Prescription
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-calendar"></i></div>
                        <h4>No appointments found</h4>
                        <p>Your booked appointments will appear here.</p>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Medical History -->
            <section class="section-card">
                <div class="section-card-header">
                    <i class="fas fa-stethoscope"></i>
                    <h3>Medical History</h3>
                </div>
                <?php if (count($medicalRows) > 0): ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Condition</th>
                                <th>Diagnosed Date</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($medicalRows as $history): ?>
                                <tr>
                                    <td><?= htmlspecialchars($history['condition']) ?></td>
                                    <td><?= htmlspecialchars($history['date']) ?></td>
                                    <td><?= htmlspecialchars($history['notes']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No medical history recorded yet.</p>
                    </div>
                <?php endif; ?>
                <div style="text-align:center;">
                    <button type="button" class="btn-link" onclick="openMedicalHistoryModal()">View Full Medical History</button>
                </div>
            </section>
        </div>

        <!-- Notifications Sidebar -->
        <aside class="notifications-sidebar">
            <div class="notifications-sidebar-header">
                <div class="title-wrap">
                    <i class="fas fa-bell" style="color:#4e73df;"></i>
                    <h3>Notifications</h3>
                </div>
                <form method="post" style="margin:0;">
                    <button type="submit" name="mark_all_read" class="mark-read">Mark all as read</button>
                </form>
            </div>

            <?php foreach ($sidebarItems as $item): ?>
                <?php renderNotificationListItem($item, 'sidebar'); ?>
            <?php endforeach; ?>

            <button type="button" class="btn-view-all" onclick="openNotificationsModal()">View All Notifications</button>
        </aside>
    </div>

    <!-- Appointment Details Modal (Figure 6.13) -->
    <div id="detailsModal" class="modal-overlay">
        <div class="modal-content details-modal-content">
            <span class="close-btn" onclick="closeDetailsModal()" role="button" aria-label="Close">&times;</span>
            <h2>Appointment Details</h2>
            <div class="detail-list">
                <div class="detail-item">
                    <span class="detail-label">Appointment ID</span>
                    <span class="detail-value" id="detail_appointmentID">—</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Doctor</span>
                    <span class="detail-value" id="detail_doctor">—</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Specialization</span>
                    <span class="detail-value" id="detail_specialization">—</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Date</span>
                    <span class="detail-value" id="detail_date">—</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Time</span>
                    <span class="detail-value" id="detail_time">—</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Reason</span>
                    <span class="detail-value" id="detail_reason">—</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Location</span>
                    <span class="detail-value" id="detail_location">Outpatient Clinic Room 3</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Status</span>
                    <span class="detail-value" id="detail_status_wrap"></span>
                </div>
                <div class="detail-item" id="detail_diagnosed_row" style="display:none;">
                    <span class="detail-label">Diagnosed Date</span>
                    <span class="detail-value" id="detail_diagnosed_date">—</span>
                </div>
                <div class="detail-item" id="detail_prescription_row" style="display:none;">
                    <span class="detail-label">Prescription</span>
                    <span class="detail-value" id="detail_prescription_status">—</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Notes</span>
                    <span class="detail-value notes-text" id="detail_notes">Please bring any recent test results or reports for review.</span>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-detail-close" onclick="closeDetailsModal()">Close</button>
                <button type="button" class="btn-view-details btn-view-rx" id="detail_view_prescription_btn" style="display:none;" onclick="openPrescriptionFromDetails()">
                    View Prescription
                </button>
                <form method="post" id="detailCancelForm" style="margin:0;display:none;">
                    <input type="hidden" name="appointment_id" id="detail_cancel_id">
                    <button type="submit" name="cancel_appointment" class="btn-detail-cancel">Cancel Appointment</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Medical History Modal (SWDD Figure 6.14) -->
    <div id="historyModal" class="modal-overlay">
        <div class="modal-content history-modal-content">
            <span class="close-btn" onclick="closeMedicalHistoryModal()" role="button" aria-label="Close">&times;</span>
            <div class="history-modal-title">
                <i class="fas fa-stethoscope"></i>
                <h2>Medical History</h2>
            </div>

            <div class="history-modal-section">
                <div class="history-section-header">
                    <i class="fas fa-file-medical"></i>
                    <span>Conditions</span>
                </div>
                <table class="history-modal-table">
                    <thead>
                        <tr>
                            <th>Condition</th>
                            <th>Diagnosed Date</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($medicalRows) > 0): ?>
                            <?php foreach ($medicalRows as $history): ?>
                                <tr>
                                    <td><?= htmlspecialchars($history['condition']) ?></td>
                                    <td><?= htmlspecialchars($history['date']) ?></td>
                                    <td><?= htmlspecialchars($history['notes']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3">No conditions recorded.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="history-modal-section">
                <div class="history-section-header">
                    <i class="fas fa-hand-dots"></i>
                    <span>Allergies</span>
                </div>
                <table class="history-modal-table">
                    <thead>
                        <tr>
                            <th>Allergy</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($allergyRows) > 0): ?>
                            <?php foreach ($allergyRows as $allergy): ?>
                                <tr><td><?= htmlspecialchars($allergy) ?></td></tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td>No known allergies recorded.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="history-modal-section">
                <div class="history-section-header">
                    <i class="fas fa-pills"></i>
                    <span>Current Medication</span>
                </div>
                <table class="history-modal-table">
                    <thead>
                        <tr>
                            <th>Medication</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($medicationRows) > 0): ?>
                            <?php foreach ($medicationRows as $medication): ?>
                                <tr><td><?= htmlspecialchars($medication) ?></td></tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td>No active medications recorded.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-detail-close" onclick="closeMedicalHistoryModal()">Close</button>
                <button type="button" class="btn-download-record" onclick="downloadMedicalRecord()">
                    <i class="fas fa-download"></i> Download Record
                </button>
            </div>
        </div>
    </div>

    <!-- Notifications Modal (SWDD Figure 6.15) -->
    <div id="notificationsModal" class="modal-overlay">
        <div class="modal-content notifications-modal-content">
            <span class="close-btn" onclick="closeNotificationsModal()" role="button" aria-label="Close">&times;</span>
            <div class="notifications-modal-header">
                <h2>Notifications</h2>
                <form method="post" style="margin:0;">
                    <button type="submit" name="mark_all_read" class="mark-read">Mark all as read</button>
                </form>
            </div>
            <div class="notifications-modal-list">
                <?php foreach ($allNotificationItems as $item): ?>
                    <?php renderNotificationListItem($item, 'modal'); ?>
                <?php endforeach; ?>
            </div>
            <div class="modal-actions notifications-modal-footer">
                <button type="button" class="btn-notifications-close" onclick="closeNotificationsModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Patient Prescription Modal -->
    <div id="patientRxModal" class="modal-overlay">
        <div class="modal-content rx-details-modal-content">
            <span class="close-btn" onclick="closePatientRxModal()" role="button" aria-label="Close">&times;</span>
            <h2>Prescription Details</h2>
            <div class="detail-list">
                <div class="detail-item">
                    <span class="detail-label">Prescription ID</span>
                    <span class="detail-value" id="patient_rx_id">—</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Appointment ID</span>
                    <span class="detail-value" id="patient_rx_appointment">—</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Diagnosed Date</span>
                    <span class="detail-value" id="patient_rx_diagnosed">—</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Doctor</span>
                    <span class="detail-value" id="patient_rx_doctor">—</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Status</span>
                    <span class="detail-value" id="patient_rx_status_wrap"></span>
                </div>
                <div id="patient_rx_details_block">
                    <div class="detail-item">
                        <span class="detail-label">Medicine</span>
                        <span class="detail-value" id="patient_rx_medicine">—</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Dosage</span>
                        <span class="detail-value" id="patient_rx_dosage">—</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Frequency</span>
                        <span class="detail-value" id="patient_rx_frequency">—</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Duration</span>
                        <span class="detail-value" id="patient_rx_duration">—</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Instructions</span>
                        <span class="detail-value" id="patient_rx_instructions">—</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Diagnosis</span>
                        <span class="detail-value" id="patient_rx_diagnosis">—</span>
                    </div>
                </div>
            </div>
            <div class="rx-info-box" id="patient_rx_preparing_box" style="display:none;">
                <i class="fas fa-hourglass-half"></i>
                <span>Your prescription is being prepared by pharmacy staff. You will be notified when it is ready for collection.</span>
            </div>
            <div class="rx-info-box" id="patient_rx_ready_box" style="display:none;">
                <i class="fas fa-info-circle"></i>
                <span>Please collect your medicine from the hospital pharmacy.</span>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-detail-close" onclick="closePatientRxModal()">Close</button>
            </div>
        </div>
    </div>

    <div id="bookingModal" class="modal-overlay">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()" role="button" aria-label="Close">&times;</span>
            <form method="post" class="booking-form" id="bookingForm">
                <h2 id="modalTitle">Book New Appointment</h2>
                <p class="modal-subtitle">Fill in the details below to schedule your appointment.</p>
                <input type="hidden" name="appointment_id" id="appointment_id">

                <div class="form-group">
                    <label for="doctor">Select Doctor</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-user-md"></i>
                        <select name="doctor" id="doctor" required onchange="updateTimeSlots()">
                            <option value="">Choose a doctor</option>
                            <?php foreach ($doctors as $doc): ?>
                                <option value="<?= $doc['doctorID'] ?>">
                                    <?= htmlspecialchars($doc['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="date">Date</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-calendar-alt"></i>
                            <input type="date" name="date" id="date" min="<?= date('Y-m-d') ?>" required onchange="updateTimeSlots()">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="time">Time</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-clock"></i>
                            <select name="time" id="time" required>
                                <option value="">Select doctor & date</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reason">Reason</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-file-medical"></i>
                        <select name="reason" id="reason" required onchange="toggleReasonOther()">
                            <option value="">Select reason</option>
                            <option value="Follow-up consultation">Follow-up consultation</option>
                            <option value="General check-up">General check-up</option>
                            <option value="Medical checkup">Medical checkup</option>
                            <option value="Asthma review">Asthma review</option>
                            <option value="X-ray">X-ray</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div id="reasonOtherWrap" style="display:none;">
                        <textarea id="reasonOther" placeholder="Please specify your reason"></textarea>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="save_appointment" class="btn-book" id="submitBtn">Book Appointment</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const modal = document.getElementById('bookingModal');
    const reasonSelect = document.getElementById('reason');
    const reasonOtherWrap = document.getElementById('reasonOtherWrap');
    const reasonOther = document.getElementById('reasonOther');
    const bookingForm = document.getElementById('bookingForm');

    function toggleReasonOther() {
        const show = reasonSelect.value === 'Other';
        reasonOtherWrap.style.display = show ? 'block' : 'none';
        reasonOther.required = show;
    }

    bookingForm.addEventListener('submit', function (e) {
        if (reasonSelect.value === 'Other') {
            const custom = reasonOther.value.trim();
            if (!custom) {
                e.preventDefault();
                alert('Please specify your reason.');
                return;
            }
            let hidden = document.getElementById('reasonHidden');
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'reason';
                hidden.id = 'reasonHidden';
                bookingForm.appendChild(hidden);
            }
            hidden.value = custom;
            reasonSelect.removeAttribute('name');
        } else {
            reasonSelect.setAttribute('name', 'reason');
            const hidden = document.getElementById('reasonHidden');
            if (hidden) hidden.remove();
        }
    });

    function updateTimeSlots() {
        const doctorID = document.getElementById('doctor').value;
        const dateValue = document.getElementById('date').value;
        const timeSelect = document.getElementById('time');
        timeSelect.innerHTML = '<option value="">Select a time slot</option>';
        if (!doctorID || !dateValue) return;

        fetch('../../application/appointment/getTimeSlots.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `doctorID=${doctorID}&date=${dateValue}`
        })
        .then(res => res.json())
        .then(slots => {
            if (slots.length === 0) {
                timeSelect.innerHTML = '<option value="">No slots available</option>';
                return;
            }
            slots.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s;
                opt.textContent = s;
                timeSelect.add(opt);
            });
        });
    }

    function setReasonValue(value) {
        const options = Array.from(reasonSelect.options).map(o => o.value);
        if (options.includes(value)) {
            reasonSelect.value = value;
            reasonOtherWrap.style.display = 'none';
            reasonOther.required = false;
        } else if (value) {
            reasonSelect.value = 'Other';
            reasonOther.value = value;
            toggleReasonOther();
        } else {
            reasonSelect.value = '';
            toggleReasonOther();
        }
    }

    function openModal() {
        document.getElementById('modalTitle').innerText = 'Book New Appointment';
        document.querySelector('.modal-subtitle').textContent = 'Fill in the details below to schedule your appointment.';
        document.getElementById('submitBtn').textContent = 'Book Appointment';
        document.getElementById('appointment_id').value = '';
        document.getElementById('doctor').value = '';
        document.getElementById('date').value = '';
        document.getElementById('time').innerHTML = '<option value="">Select doctor & date</option>';
        setReasonValue('');
        reasonSelect.setAttribute('name', 'reason');
        modal.classList.add('open');
    }

    function editAppointment(data) {
        document.getElementById('modalTitle').innerText = 'Edit Appointment';
        document.querySelector('.modal-subtitle').textContent = 'Update your appointment details below.';
        document.getElementById('submitBtn').textContent = 'Update Appointment';
        document.getElementById('appointment_id').value = data.id;
        document.getElementById('doctor').value = data.doctorID;
        document.getElementById('date').value = data.date;
        setReasonValue(data.reason);
        updateTimeSlots();
        setTimeout(() => {
            const timeSelect = document.getElementById('time');
            for (const opt of timeSelect.options) {
                if (opt.value === data.time || opt.textContent === data.time) {
                    timeSelect.value = opt.value;
                    break;
                }
            }
        }, 300);
        modal.classList.add('open');
    }

    function formatDetailDate(dateStr) {
        if (!dateStr) return '—';
        const d = new Date(dateStr + 'T00:00:00');
        return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    }

    function formatDetailTime(timeStr) {
        if (!timeStr) return '—';
        const parts = timeStr.split(':');
        if (parts.length < 2) return timeStr;
        let h = parseInt(parts[0], 10);
        const m = parts[1];
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return `${String(h).padStart(2, '0')}:${m} ${ampm}`;
    }

    function statusDetailClass(status) {
        const s = (status || '').toLowerCase();
        if (s === 'pending') return 'pending';
        if (s === 'cancelled') return 'cancelled';
        if (s === 'completed') return 'completed';
        return '';
    }

    function viewDetails(row) {
        currentAppointmentPrescription = row.prescription || null;
        document.getElementById('detail_appointmentID').textContent = row.appointmentID || '—';
        document.getElementById('detail_doctor').textContent = row.doc_name || '—';
        document.getElementById('detail_specialization').textContent = row.specialization || '—';
        document.getElementById('detail_date').textContent = formatDetailDate(row.appointmentDate);
        document.getElementById('detail_time').textContent = formatDetailTime(row.appointmentTime);
        document.getElementById('detail_reason').textContent = row.reason || '—';

        const statusWrap = document.getElementById('detail_status_wrap');
        const status = row.status || 'Pending';
        statusWrap.innerHTML = `<span class="details-status ${statusDetailClass(status)}">${status}</span>`;

        const diagnosedRow = document.getElementById('detail_diagnosed_row');
        const diagnosedDateEl = document.getElementById('detail_diagnosed_date');
        if (row.diagnosedDate) {
            diagnosedRow.style.display = 'flex';
            diagnosedDateEl.textContent = formatDetailDate(row.diagnosedDate);
        } else {
            diagnosedRow.style.display = 'none';
            diagnosedDateEl.textContent = '—';
        }

        const prescriptionRow = document.getElementById('detail_prescription_row');
        const prescriptionStatusEl = document.getElementById('detail_prescription_status');
        const viewRxBtn = document.getElementById('detail_view_prescription_btn');
        if (row.prescription) {
            prescriptionRow.style.display = 'flex';
            prescriptionStatusEl.textContent = row.prescription.status || '—';
            viewRxBtn.style.display = 'inline-flex';
        } else {
            prescriptionRow.style.display = 'none';
            prescriptionStatusEl.textContent = '—';
            viewRxBtn.style.display = 'none';
        }

        const notesEl = document.getElementById('detail_notes');
        if (status === 'Cancelled') {
            notesEl.textContent = 'This appointment has been cancelled.';
        } else if (status === 'Completed') {
            if (row.prescription && row.prescription.rawStatus === 'Ready for Collection') {
                notesEl.textContent = 'Your prescription is ready for collection at the hospital pharmacy.';
            } else if (row.prescription) {
                notesEl.textContent = 'Your prescription is being prepared by pharmacy staff.';
            } else {
                notesEl.textContent = 'This consultation has been completed.';
            }
        } else {
            notesEl.textContent = 'Please bring any recent test results or reports for review.';
        }

        const cancelForm = document.getElementById('detailCancelForm');
        if (status === 'Pending' || status === 'Confirmed') {
            document.getElementById('detail_cancel_id').value = row.appointmentID;
            cancelForm.style.display = 'inline';
        } else {
            cancelForm.style.display = 'none';
        }

        document.getElementById('detailsModal').classList.add('open');
    }

    let currentAppointmentPrescription = null;

    function openPrescriptionFromDetails() {
        if (!currentAppointmentPrescription) return;
        closeDetailsModal();
        viewPatientPrescription(currentAppointmentPrescription);
    }

    function patientRxStatusClass(status) {
        if (status === 'Ready for Collection') return 'rx-status-ready';
        if (status === 'Being Prepared') return 'rx-status-pending';
        return 'rx-status-issued';
    }

    function viewPatientPrescription(data) {
        document.getElementById('patient_rx_id').textContent = data.prescriptionID || '—';
        document.getElementById('patient_rx_appointment').textContent = data.appointmentID || '—';
        document.getElementById('patient_rx_diagnosed').textContent = data.diagnosedDate || '—';
        document.getElementById('patient_rx_doctor').textContent = data.doctor || '—';

        const statusWrap = document.getElementById('patient_rx_status_wrap');
        statusWrap.innerHTML = `<span class="${patientRxStatusClass(data.status)}">${data.status || '—'}</span>`;

        const detailsBlock = document.getElementById('patient_rx_details_block');
        const preparingBox = document.getElementById('patient_rx_preparing_box');
        const readyBox = document.getElementById('patient_rx_ready_box');
        const canView = !!data.canViewDetails;

        detailsBlock.style.display = canView ? 'block' : 'none';
        preparingBox.style.display = canView ? 'none' : 'flex';
        readyBox.style.display = (canView && data.rawStatus === 'Ready for Collection') ? 'flex' : 'none';

        if (canView) {
            document.getElementById('patient_rx_medicine').textContent = data.medicine || '—';
            document.getElementById('patient_rx_dosage').textContent = data.dosage || '—';
            document.getElementById('patient_rx_frequency').textContent = data.frequency || '—';
            document.getElementById('patient_rx_duration').textContent = data.duration || '—';
            document.getElementById('patient_rx_instructions').textContent = data.instructions || '—';
            document.getElementById('patient_rx_diagnosis').textContent = data.diagnosis || '—';
        }

        document.getElementById('patientRxModal').classList.add('open');
    }

    function closePatientRxModal() {
        document.getElementById('patientRxModal').classList.remove('open');
    }

    document.getElementById('patientRxModal').addEventListener('click', function (e) {
        if (e.target === this) closePatientRxModal();
    });

    function closeDetailsModal() {
        document.getElementById('detailsModal').classList.remove('open');
    }

    document.getElementById('detailsModal').addEventListener('click', function (e) {
        if (e.target === this) closeDetailsModal();
    });

    const medicalRecordData = <?= json_encode([
        'patientID' => $patient['patientID'] ?? $patientID,
        'patientName' => $patient['name'] ?? ($_SESSION['fullname'] ?? 'Patient'),
        'conditions' => $medicalRows,
        'allergies' => $allergyRows,
        'medications' => $medicationRows,
    ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    function openMedicalHistoryModal() {
        document.getElementById('historyModal').classList.add('open');
    }

    function closeMedicalHistoryModal() {
        document.getElementById('historyModal').classList.remove('open');
    }

    document.getElementById('historyModal').addEventListener('click', function (e) {
        if (e.target === this) closeMedicalHistoryModal();
    });

    function downloadMedicalRecord() {
        const data = medicalRecordData;
        let text = 'Sarawak General Hospital — Medical Record\n';
        text += '=====================================\n\n';
        text += `Patient: ${data.patientName}\n`;
        text += `Patient ID: ${data.patientID}\n`;
        text += `Generated: ${new Date().toLocaleString()}\n\n`;

        text += 'CONDITIONS\n----------\n';
        if (data.conditions.length) {
            data.conditions.forEach(row => {
                text += `${row.condition} | ${row.date} | ${row.notes}\n`;
            });
        } else {
            text += 'No conditions recorded.\n';
        }

        text += '\nALLERGIES\n---------\n';
        if (data.allergies.length) {
            data.allergies.forEach(a => { text += `${a}\n`; });
        } else {
            text += 'No known allergies recorded.\n';
        }

        text += '\nCURRENT MEDICATION\n------------------\n';
        if (data.medications.length) {
            data.medications.forEach(m => { text += `${m}\n`; });
        } else {
            text += 'No active medications recorded.\n';
        }

        const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `medical-record-${data.patientID}.txt`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    }

    function openNotificationsModal() {
        document.getElementById('notificationsModal').classList.add('open');
    }

    function closeNotificationsModal() {
        document.getElementById('notificationsModal').classList.remove('open');
    }

    document.getElementById('notificationsModal').addEventListener('click', function (e) {
        if (e.target === this) closeNotificationsModal();
    });

    function confirmDelete(id) {
        Swal.fire({ title: 'Are you sure?', icon: 'warning', showCancelButton: true })
            .then(r => { if (r.isConfirmed) document.getElementById('deleteForm_' + id).submit(); });
    }

    function closeModal() {
        modal.classList.remove('open');
    }
    window.onclick = function(e) { if (e.target === modal) closeModal(); };
    </script>

    <?php if ($status_message): ?>
    <script>
        Swal.fire({
            title: '<?= $status_message === 'success' ? 'Success!' : 'Error!' ?>',
            text: '<?= $status_message === 'success' ? addslashes($success_text) : addslashes($error_text) ?>',
            icon: '<?= $status_message ?>'
        }).then(() => { window.location.href = 'patientDashboard.php'; });
    </script>
    <?php endif; ?>
</body>
</html>
