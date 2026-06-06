<?php

class AppointmentModel
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function generateAppointmentID(): string
    {
        $result = $this->conn->query(
            "SELECT appointmentID FROM appointment WHERE appointmentID LIKE 'APT%' ORDER BY appointmentID DESC LIMIT 1"
        );
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $num = (int) substr($row['appointmentID'], 3) + 1;
        } else {
            $num = 1;
        }
        return 'APT' . str_pad((string) $num, 3, '0', STR_PAD_LEFT);
    }

    public function createBooking(array $details): array
    {
        $doctorModel = new DoctorModel($this->conn);
        if (!$doctorModel->checkAvailability($details['doctorID'], $details['date'], $details['time'])) {
            return ['status' => 'Error', 'message' => 'Time slot is no longer available.'];
        }

        $bookingID = $this->generateAppointmentID();
        $time = date('H:i:s', strtotime($details['time']));
        $status = $details['status'] ?? 'Pending';

        $stmt = $this->conn->prepare(
            'INSERT INTO appointment (appointmentID, appointmentDate, appointmentTime, reason, status, patientID, doctorID)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'sssssss',
            $bookingID,
            $details['date'],
            $time,
            $details['reason'],
            $status,
            $details['patientID'],
            $details['doctorID']
        );
        $stmt->execute();
        $stmt->close();

        return ['status' => 'Success', 'bookingID' => $bookingID];
    }

    public function getAllWithDetails(): array
    {
        $sql = '
            SELECT a.*, p.name AS patientName, d.name AS doctorName
            FROM appointment a
            INNER JOIN patient p ON a.patientID = p.patientID
            INNER JOIN doctor d ON a.doctorID = d.doctorID
            ORDER BY a.appointmentDate DESC, a.appointmentTime ASC
        ';
        return $this->conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function getByPatient(string $patientID): array
    {
        $stmt = $this->conn->prepare(
            'SELECT a.*, d.name AS doc_name, d.specialization
             FROM appointment a
             LEFT JOIN doctor d ON a.doctorID = d.doctorID
             WHERE a.patientID = ?
             ORDER BY a.appointmentDate DESC, a.appointmentTime ASC'
        );
        $stmt->bind_param('s', $patientID);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function getByDoctor(string $doctorID): array
    {
        $stmt = $this->conn->prepare(
            'SELECT a.*, p.name AS patientName
             FROM appointment a
             LEFT JOIN patient p ON a.patientID = p.patientID
             WHERE a.doctorID = ?
             ORDER BY a.appointmentDate, a.appointmentTime'
        );
        $stmt->bind_param('s', $doctorID);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function getById(string $appointmentID): ?array
    {
        $stmt = $this->conn->prepare('SELECT * FROM appointment WHERE appointmentID = ?');
        $stmt->bind_param('s', $appointmentID);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function markCompletedWithDiagnosis(string $appointmentID): bool
    {
        $today = date('Y-m-d');
        $status = 'Completed';
        $stmt = $this->conn->prepare(
            'UPDATE appointment SET status = ?, diagnosedDate = ? WHERE appointmentID = ?'
        );
        $stmt->bind_param('sss', $status, $today, $appointmentID);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function updateStatus(string $appointmentID, string $status): bool
    {
        $valid = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];
        if (!in_array($status, $valid, true)) {
            return false;
        }
        $stmt = $this->conn->prepare('UPDATE appointment SET status = ? WHERE appointmentID = ?');
        $stmt->bind_param('ss', $status, $appointmentID);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function update(string $appointmentID, array $details): bool
    {
        $time = date('H:i:s', strtotime($details['time']));
        $stmt = $this->conn->prepare(
            'UPDATE appointment SET doctorID=?, appointmentDate=?, appointmentTime=?, reason=?, status=?, patientID=?
             WHERE appointmentID=?'
        );
        $stmt->bind_param(
            'sssssss',
            $details['doctorID'],
            $details['date'],
            $time,
            $details['reason'],
            $details['status'],
            $details['patientID'],
            $appointmentID
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function delete(string $appointmentID): bool
    {
        $stmt = $this->conn->prepare('DELETE FROM appointment WHERE appointmentID = ?');
        $stmt->bind_param('s', $appointmentID);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
