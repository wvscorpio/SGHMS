<?php
require_once __DIR__ . '/../../includes/init.php';
requireRole('patient');

$patientID = resolvePatientId($conn);
$notificationController = new NotificationController($conn);

if (isset($_GET['read'])) {
    $notificationController->markAsRead($_GET['read']);
    header('Location: notifications.php');
    exit;
}

if (isset($_POST['confirm_appointment'])) {
    $notificationController->processResponse('Confirm', $_POST['appointment_id']);
    header('Location: notifications.php');
    exit;
}

if (isset($_POST['cancel_appointment'])) {
    $notificationController->processResponse('Cancel', $_POST['appointment_id']);
    header('Location: notifications.php');
    exit;
}

$notifications = $notificationController->getByPatient($patientID);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications - SGHMS</title>
    <link rel="stylesheet" href="../css/patient.css">
    <style>
        .container { padding: 30px 40px; }
        .notification { background: #fff; border: 1px solid #eee; border-radius: 8px; padding: 16px; margin-bottom: 12px; }
        .notification.unread { border-left: 4px solid #4e73df; }
        .meta { color: #888; font-size: 0.85rem; margin-top: 8px; }
        .actions { margin-top: 10px; }
        .actions button { margin-right: 8px; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-confirm { background: #1cc88a; color: #fff; }
        .btn-cancel { background: #e74a3b; color: #fff; }
    </style>
</head>
<body>
    <div class="header" style="padding:20px;background:#fff;border-bottom:1px solid #ddd;">
        <h1 style="margin:0;">Notifications</h1>
        <p><a href="patientDashboard.php">← Back to Dashboard</a></p>
    </div>
    <div class="container">
        <?php if (count($notifications) > 0): ?>
            <?php foreach ($notifications as $note): ?>
                <div class="notification <?= $note['isRead'] ? '' : 'unread' ?>">
                    <div><?= htmlspecialchars($note['messageBody']) ?></div>
                    <div class="meta"><?= htmlspecialchars($note['createdAt'] ?? '') ?>
                        <?= $note['isRead'] ? '' : ' • Unread' ?>
                        <?php if (!$note['isRead']): ?>
                            — <a href="?read=<?= urlencode($note['notificationID']) ?>">Mark as read</a>
                        <?php endif; ?>
                    </div>
                    <?php if ($note['appointmentID'] && stripos($note['messageBody'], 'Reminder') !== false): ?>
                    <div class="actions">
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="appointment_id" value="<?= htmlspecialchars($note['appointmentID']) ?>">
                            <button type="submit" name="confirm_appointment" class="btn-confirm">Confirm Appointment</button>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="appointment_id" value="<?= htmlspecialchars($note['appointmentID']) ?>">
                            <button type="submit" name="cancel_appointment" class="btn-cancel">Cancel Appointment</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No notifications available.</p>
        <?php endif; ?>
    </div>
</body>
</html>
