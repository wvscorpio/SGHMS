<?php

class PatientModel
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function generatePatientID(): string
    {
        $result = $this->conn->query(
            'SELECT patientID FROM patient ORDER BY patientID DESC LIMIT 1'
        );
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $num = (int) substr($row['patientID'], 3) + 1;
        } else {
            $num = 1;
        }
        return 'PAT' . str_pad((string) $num, 3, '0', STR_PAD_LEFT);
    }

    public function validateAndCreate(array $details): array
    {
        if (trim($details['name'] ?? '') === ''
            || !array_key_exists('age', $details)
            || trim((string) $details['age']) === ''
            || trim($details['gender'] ?? '') === ''
            || trim($details['contactNumber'] ?? '') === ''
            || !isset($details['medicalHistory'])) {
            return ['status' => 'Error', 'message' => 'Please fill in all required fields.'];
        }

        $stmt = $this->conn->prepare('SELECT patientID FROM patient WHERE name = ?');
        $stmt->bind_param('s', $details['name']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return ['status' => 'Error', 'message' => 'Patient record with this name already exists.'];
        }

        $newID = $this->generatePatientID();
        $stmt = $this->conn->prepare(
            'INSERT INTO patient (patientID, name, age, gender, contactNumber, medicalHistory)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'ssisss',
            $newID,
            $details['name'],
            $details['age'],
            $details['gender'],
            $details['contactNumber'],
            $details['medicalHistory']
        );
        $stmt->execute();
        $stmt->close();

        return ['status' => 'Success', 'patientID' => $newID];
    }

    public function getAll(): array
    {
        return $this->conn->query('SELECT * FROM patient ORDER BY patientID')->fetch_all(MYSQLI_ASSOC);
    }

    public function getById(string $patientID): ?array
    {
        $stmt = $this->conn->prepare('SELECT * FROM patient WHERE patientID = ?');
        $stmt->bind_param('s', $patientID);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function getPatientData(string $patientID): ?array
    {
        return $this->getById($patientID);
    }

    public function searchByName(string $name): array
    {
        $like = '%' . $name . '%';
        $stmt = $this->conn->prepare('SELECT * FROM patient WHERE name LIKE ?');
        $stmt->bind_param('s', $like);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function update(string $patientID, array $details): bool
    {
        $stmt = $this->conn->prepare(
            'UPDATE patient SET name=?, age=?, gender=?, contactNumber=?, medicalHistory=? WHERE patientID=?'
        );
        $stmt->bind_param(
            'sissss',
            $details['name'],
            $details['age'],
            $details['gender'],
            $details['contactNumber'],
            $details['medicalHistory'],
            $patientID
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function delete(string $patientID): bool
    {
        $stmt = $this->conn->prepare('DELETE FROM patient WHERE patientID = ?');
        $stmt->bind_param('s', $patientID);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
