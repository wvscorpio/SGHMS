<?php

class PrescriptionController
{
    private mysqli $conn;
    private PrescriptionModel $prescriptionModel;
    private PatientModel $patientModel;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->prescriptionModel = new PrescriptionModel($conn);
        $this->patientModel = new PatientModel($conn);
    }

    public function createPrescription(
        array $medDetails,
        string $patientID,
        string $doctorID,
        ?string $appointmentID = null
    ): array {
        $result = $this->prescriptionModel->createPrescription(
            $medDetails,
            $patientID,
            $doctorID,
            $appointmentID
        );

        if (($result['status'] ?? '') !== 'Success' || !$appointmentID) {
            return $result;
        }

        $appointmentModel = new AppointmentModel($this->conn);
        $appointment = $appointmentModel->getById($appointmentID);

        if ($appointment
            && ($appointment['doctorID'] ?? '') === $doctorID
            && ($appointment['patientID'] ?? '') === $patientID
            && ($appointment['status'] ?? '') === 'Confirmed') {
            $appointmentModel->markCompletedWithDiagnosis($appointmentID);
        }

        return $result;
    }

    public function markReadyForCollection(string $prescriptionID): array
    {
        $rx = $this->prescriptionModel->getById($prescriptionID);
        if (!$rx) {
            return ['status' => 'Error', 'message' => 'Prescription not found.'];
        }

        $currentStatus = normalizePrescriptionStatus($rx['status'] ?? '');
        if ($currentStatus !== RX_STATUS_PENDING) {
            return ['status' => 'Error', 'message' => 'Only prescriptions awaiting preparation can be marked ready.'];
        }

        if (!$this->prescriptionModel->updateStatus($prescriptionID, RX_STATUS_READY)) {
            return ['status' => 'Error', 'message' => 'Failed to update prescription status.'];
        }

        $message = 'Your prescription ' . $prescriptionID . ' is ready for collection at the hospital pharmacy.';
        (new NotificationModel($this->conn))->createNotification(
            $rx['patientID'],
            $rx['appointmentID'] ?? null,
            $message
        );

        return [
            'status' => 'Success',
            'message' => 'Prescription marked ready. Patient has been notified.',
        ];
    }

    public function searchPatient(string $name): array
    {
        return $this->patientModel->searchByName($name);
    }

    public function getPendingForStaff(): array
    {
        return $this->prescriptionModel->getPendingForStaff();
    }

    public function getByDoctor(string $doctorID): array
    {
        return $this->prescriptionModel->getByDoctor($doctorID);
    }

    public function getByPatient(string $patientID): array
    {
        return $this->prescriptionModel->getByPatient($patientID);
    }

    public function getByAppointment(string $appointmentID): ?array
    {
        return $this->prescriptionModel->getByAppointment($appointmentID);
    }

    public function getById(string $prescriptionID): ?array
    {
        return $this->prescriptionModel->getById($prescriptionID);
    }
}
