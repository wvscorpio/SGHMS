<?php

class NotificationModel
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function generateNotificationID(): string
    {
        $result = $this->conn->query(
            "SELECT notificationID FROM notification WHERE notificationID LIKE 'NTF%' ORDER BY notificationID DESC LIMIT 1"
        );
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $num = (int) substr($row['notificationID'], 3) + 1;
        } else {
            $num = 1;
        }
        return 'NTF' . str_pad((string) $num, 3, '0', STR_PAD_LEFT);
    }

    public function createNotification(string $patientID, ?string $appointmentID, string $message): bool
    {
        $notificationID = $this->generateNotificationID();
        $stmt = $this->conn->prepare(
            'INSERT INTO notification (notificationID, patientID, appointmentID, messageBody, isRead)
             VALUES (?, ?, ?, ?, 0)'
        );
        $stmt->bind_param('ssss', $notificationID, $patientID, $appointmentID, $message);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function getByPatient(string $patientID): array
    {
        $stmt = $this->conn->prepare(
            'SELECT * FROM notification WHERE patientID = ? ORDER BY createdAt DESC'
        );
        $stmt->bind_param('s', $patientID);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function markAsRead(string $notificationID): void
    {
        $stmt = $this->conn->prepare('UPDATE notification SET isRead = 1 WHERE notificationID = ?');
        $stmt->bind_param('s', $notificationID);
        $stmt->execute();
        $stmt->close();
    }

    public function markAllAsRead(string $patientID): void
    {
        $stmt = $this->conn->prepare('UPDATE notification SET isRead = 1 WHERE patientID = ?');
        $stmt->bind_param('s', $patientID);
        $stmt->execute();
        $stmt->close();
    }

    public function getUnreadCount(string $patientID): int
    {
        $stmt = $this->conn->prepare(
            'SELECT COUNT(*) AS cnt FROM notification WHERE patientID = ? AND isRead = 0'
        );
        $stmt->bind_param('s', $patientID);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int) ($row['cnt'] ?? 0);
    }

    public function sendReminderForAppointment(string $appointmentID): void
    {
        $stmt = $this->conn->prepare(
            'SELECT a.*, p.name AS patientName, d.name AS doctorName
             FROM appointment a
             JOIN patient p ON a.patientID = p.patientID
             JOIN doctor d ON a.doctorID = d.doctorID
             WHERE a.appointmentID = ?'
        );
        $stmt->bind_param('s', $appointmentID);
        $stmt->execute();
        $appt = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$appt) {
            return;
        }

        $date = date('d/m/Y', strtotime($appt['appointmentDate']));
        $time = date('h:i A', strtotime($appt['appointmentTime']));
        $message = "Reminder: Appointment with Dr. {$appt['doctorName']} on {$date} at {$time}. Please confirm or cancel.";
        $this->createNotification($appt['patientID'], $appointmentID, $message);
    }
}
