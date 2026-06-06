<?php

class DoctorModel
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function generateDoctorID(): string
    {
        $result = $this->conn->query(
            'SELECT doctorID FROM doctor ORDER BY doctorID DESC LIMIT 1'
        );
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $num = (int) substr($row['doctorID'], 3) + 1;
        } else {
            $num = 1;
        }
        return 'DOC' . str_pad((string) $num, 3, '0', STR_PAD_LEFT);
    }

    public function getAll(): array
    {
        return $this->conn->query('SELECT * FROM doctor ORDER BY doctorID')->fetch_all(MYSQLI_ASSOC);
    }

    public function createFromRegistration(string $name, string $contactDetails): array
    {
        $stmt = $this->conn->prepare('SELECT doctorID FROM doctor WHERE name = ?');
        $stmt->bind_param('s', $name);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return ['status' => 'Error', 'message' => 'Doctor profile with this name already exists.'];
        }

        $doctorID = $this->generateDoctorID();
        $specialization = 'General Practice';
        $stmt = $this->conn->prepare(
            'INSERT INTO doctor (doctorID, name, specialization, contactDetails) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('ssss', $doctorID, $name, $specialization, $contactDetails);
        $stmt->execute();
        $stmt->close();

        return ['status' => 'Success', 'doctorID' => $doctorID];
    }

    public function update(string $doctorID, array $details): bool
    {
        $stmt = $this->conn->prepare(
            'UPDATE doctor SET name=?, specialization=?, contactDetails=? WHERE doctorID=?'
        );
        $stmt->bind_param(
            'ssss',
            $details['name'],
            $details['specialization'],
            $details['contactDetails'],
            $doctorID
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function getById(string $doctorID): ?array
    {
        $stmt = $this->conn->prepare('SELECT * FROM doctor WHERE doctorID = ?');
        $stmt->bind_param('s', $doctorID);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function retrieveSchedule(string $doctorID): array
    {
        $stmt = $this->conn->prepare(
            'SELECT scheduleID, dayOfWeek, startTime, endTime FROM doctor_schedule
             WHERE doctorID = ? ORDER BY FIELD(dayOfWeek,
             "Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"), startTime'
        );
        $stmt->bind_param('s', $doctorID);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function checkAvailability(string $doctorID, string $date, string $time): bool
    {
        $dayOfWeek = date('l', strtotime($date));
        $timeFormatted = date('H:i:s', strtotime($time));

        $stmt = $this->conn->prepare(
            'SELECT startTime, endTime FROM doctor_schedule WHERE doctorID = ? AND dayOfWeek = ?'
        );
        $stmt->bind_param('ss', $doctorID, $dayOfWeek);
        $stmt->execute();
        $schedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $inSchedule = false;
        foreach ($schedules as $schedule) {
            if ($timeFormatted >= $schedule['startTime'] && $timeFormatted < $schedule['endTime']) {
                $inSchedule = true;
                break;
            }
        }
        if (!$inSchedule) {
            return false;
        }

        $stmt = $this->conn->prepare(
            'SELECT appointmentID FROM appointment
             WHERE doctorID = ? AND appointmentDate = ? AND appointmentTime = ?
             AND status NOT IN ("Cancelled")'
        );
        $stmt->bind_param('sss', $doctorID, $date, $timeFormatted);
        $stmt->execute();
        $booked = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        return !$booked;
    }

    public function getTimeSlots(string $doctorID, string $date): array
    {
        $dayOfWeek = date('l', strtotime($date));
        $stmt = $this->conn->prepare(
            'SELECT startTime, endTime FROM doctor_schedule WHERE doctorID = ? AND dayOfWeek = ?'
        );
        $stmt->bind_param('ss', $doctorID, $dayOfWeek);
        $stmt->execute();
        $result = $stmt->get_result();

        $slots = [];
        while ($row = $result->fetch_assoc()) {
            $start = strtotime($row['startTime']);
            $end = strtotime($row['endTime']);
            while ($start < $end) {
                $slots[] = date('H:i', $start);
                $start = strtotime('+30 minutes', $start);
            }
        }
        $stmt->close();

        $stmt = $this->conn->prepare(
            'SELECT appointmentTime FROM appointment
             WHERE doctorID = ? AND appointmentDate = ? AND status NOT IN ("Cancelled")'
        );
        $stmt->bind_param('ss', $doctorID, $date);
        $stmt->execute();
        $result2 = $stmt->get_result();
        $booked = [];
        while ($r = $result2->fetch_assoc()) {
            $booked[] = date('H:i', strtotime($r['appointmentTime']));
        }
        $stmt->close();

        return array_values(array_diff($slots, $booked));
    }

    public function setSchedule(string $doctorID, string $day, string $startTime, string $endTime): array
    {
        if ($startTime >= $endTime) {
            return ['status' => 'Error', 'message' => 'End time must be later than start time.'];
        }

        $checkSql = '
            SELECT scheduleID FROM doctor_schedule
            WHERE doctorID = ? AND dayOfWeek = ?
            AND NOT (endTime <= ? OR startTime >= ?)
        ';
        $stmt = $this->conn->prepare($checkSql);
        $stmt->bind_param('ssss', $doctorID, $day, $startTime, $endTime);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return ['status' => 'Error', 'message' => 'Schedule conflict detected.'];
        }

        $stmt = $this->conn->prepare(
            'INSERT INTO doctor_schedule (doctorID, dayOfWeek, startTime, endTime) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('ssss', $doctorID, $day, $startTime, $endTime);
        $stmt->execute();
        $stmt->close();

        return ['status' => 'Success', 'message' => 'Availability updated successfully.'];
    }
}
