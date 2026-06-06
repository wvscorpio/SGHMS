<?php

class AppointmentController
{
    private AppointmentModel $appointmentModel;
    private DoctorModel $doctorModel;
    private NotificationModel $notificationModel;
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->appointmentModel = new AppointmentModel($conn);
        $this->doctorModel = new DoctorModel($conn);
        $this->notificationModel = new NotificationModel($conn);
    }

    public function submitBooking(array $details): array
    {
        $result = $this->appointmentModel->createBooking($details);

        if ($result['status'] === 'Success') {
            $this->notificationModel->createNotification(
                $details['patientID'],
                $result['bookingID'],
                'Your appointment has been booked successfully and is pending confirmation.'
            );
        }

        return $result;
    }

    public function updateStatus(string $appointmentID, string $status): array
    {
        if (!$this->appointmentModel->updateStatus($appointmentID, $status)) {
            return ['status' => 'Error', 'message' => 'Invalid status selected.'];
        }

        $appt = $this->appointmentModel->getById($appointmentID);
        if ($appt) {
            $messages = [
                'Confirmed' => 'Your appointment has been confirmed.',
                'Cancelled' => 'Your appointment has been cancelled.',
                'Completed' => 'Your appointment has been marked as completed.',
                'Pending' => 'Your appointment status is now pending.',
            ];
            if (isset($messages[$status])) {
                $this->notificationModel->createNotification(
                    $appt['patientID'],
                    $appointmentID,
                    $messages[$status]
                );
            }
        }

        return ['status' => 'Success'];
    }

    public function processResponse(string $choice, string $appointmentID): array
    {
        $appt = $this->appointmentModel->getById($appointmentID);
        if (!$appt) {
            return ['status' => 'Error', 'message' => 'Appointment not found.'];
        }

        if ($choice === 'Confirm') {
            $this->appointmentModel->updateStatus($appointmentID, 'Confirmed');
            $this->notificationModel->createNotification(
                $appt['patientID'],
                $appointmentID,
                'You confirmed your appointment.'
            );
            return ['status' => 'Success', 'message' => 'Appointment confirmed.'];
        }

        if ($choice === 'Cancel') {
            $this->appointmentModel->updateStatus($appointmentID, 'Cancelled');
            $this->notificationModel->createNotification(
                $appt['patientID'],
                $appointmentID,
                'You cancelled your appointment.'
            );
            return ['status' => 'Success', 'message' => 'Appointment cancelled.'];
        }

        return ['status' => 'Error', 'message' => 'Invalid choice.'];
    }

    public function getTimeSlots(string $doctorID, string $date): array
    {
        return $this->doctorModel->getTimeSlots($doctorID, $date);
    }
}
