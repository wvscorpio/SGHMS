<?php

class PrescriptionModel
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function generatePrescriptionID(): string
    {
        $result = $this->conn->query(
            "SELECT prescriptionID FROM prescription WHERE prescriptionID LIKE 'RX%' ORDER BY prescriptionID DESC LIMIT 1"
        );
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $num = (int) substr($row['prescriptionID'], 2) + 1;
        } else {
            $num = 1;
        }
        return 'RX' . str_pad((string) $num, 3, '0', STR_PAD_LEFT);
    }

    public function checkAllergies(string $medicalHistory, string $medicineName): bool
    {
        if (empty($medicalHistory)) {
            return false;
        }
        return stripos($medicalHistory, $medicineName) !== false;
    }

    public function createPrescription(
        array $medDetails,
        string $patientID,
        string $doctorID,
        ?string $appointmentID = null
    ): array {
        $patientModel = new PatientModel($this->conn);
        $patientData = $patientModel->getPatientData($patientID);

        if (!$patientData) {
            return ['status' => 'Error', 'message' => 'Patient not found.'];
        }

        if ($this->checkAllergies($patientData['medicalHistory'], $medDetails['medicineName'])) {
            return ['status' => 'Error', 'message' => 'Patient has a recorded allergy to this medication.'];
        }

        if ($appointmentID) {
            $existing = $this->getByAppointment($appointmentID);
            if ($existing) {
                return ['status' => 'Error', 'message' => 'A prescription already exists for this appointment.'];
            }
        }

        $prescriptionID = $this->generatePrescriptionID();
        $status = RX_STATUS_PENDING;
        $stmt = $this->conn->prepare(
            'INSERT INTO prescription (prescriptionID, patientID, doctorID, appointmentID, medicineName, dosage, duration, instructions, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'sssssssss',
            $prescriptionID,
            $patientID,
            $doctorID,
            $appointmentID,
            $medDetails['medicineName'],
            $medDetails['dosage'],
            $medDetails['duration'],
            $medDetails['instructions'],
            $status
        );
        $stmt->execute();
        $stmt->close();

        return [
            'status' => 'Success',
            'message' => 'Prescription submitted to pharmacy staff.',
            'prescriptionID' => $prescriptionID,
        ];
    }

    public function updateStatus(string $prescriptionID, string $status): bool
    {
        $valid = [RX_STATUS_PENDING, RX_STATUS_READY, RX_STATUS_COLLECTED, 'Active', 'Issued'];
        if (!in_array($status, $valid, true)) {
            return false;
        }

        $stmt = $this->conn->prepare('UPDATE prescription SET status = ? WHERE prescriptionID = ?');
        $stmt->bind_param('ss', $status, $prescriptionID);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function getByAppointment(string $appointmentID): ?array
    {
        $stmt = $this->conn->prepare(
            'SELECT pr.*, p.name AS patientName, d.name AS doctorName
             FROM prescription pr
             LEFT JOIN patient p ON pr.patientID = p.patientID
             LEFT JOIN doctor d ON pr.doctorID = d.doctorID
             WHERE pr.appointmentID = ?
             ORDER BY pr.createdAt DESC
             LIMIT 1'
        );
        $stmt->bind_param('s', $appointmentID);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function getPendingForStaff(): array
    {
        $pending = RX_STATUS_PENDING;
        $result = $this->conn->query(
            "SELECT pr.*, p.name AS patientName, d.name AS doctorName, a.appointmentDate, a.appointmentTime
             FROM prescription pr
             LEFT JOIN patient p ON pr.patientID = p.patientID
             LEFT JOIN doctor d ON pr.doctorID = d.doctorID
             LEFT JOIN appointment a ON pr.appointmentID = a.appointmentID
             WHERE pr.status IN ('{$pending}', 'Active')
             ORDER BY pr.createdAt ASC"
        );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getByPatient(string $patientID): array
    {
        $stmt = $this->conn->prepare(
            'SELECT pr.*, d.name AS doctorName, a.appointmentDate, a.diagnosedDate
             FROM prescription pr
             LEFT JOIN doctor d ON pr.doctorID = d.doctorID
             LEFT JOIN appointment a ON pr.appointmentID = a.appointmentID
             WHERE pr.patientID = ?
             ORDER BY pr.createdAt DESC'
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
            'SELECT pr.*, p.name AS patientName, a.appointmentDate
             FROM prescription pr
             LEFT JOIN patient p ON pr.patientID = p.patientID
             LEFT JOIN appointment a ON pr.appointmentID = a.appointmentID
             WHERE pr.doctorID = ?
             ORDER BY pr.createdAt DESC'
        );
        $stmt->bind_param('s', $doctorID);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function getById(string $prescriptionID): ?array
    {
        $stmt = $this->conn->prepare(
            'SELECT pr.*, p.name AS patientName, d.name AS doctorName, a.appointmentDate, a.diagnosedDate
             FROM prescription pr
             LEFT JOIN patient p ON pr.patientID = p.patientID
             LEFT JOIN doctor d ON pr.doctorID = d.doctorID
             LEFT JOIN appointment a ON pr.appointmentID = a.appointmentID
             WHERE pr.prescriptionID = ?'
        );
        $stmt->bind_param('s', $prescriptionID);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }
}
