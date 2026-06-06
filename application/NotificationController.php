<?php

class NotificationController
{
    private NotificationModel $notificationModel;
    private AppointmentController $appointmentController;

    public function __construct(mysqli $conn)
    {
        $this->notificationModel = new NotificationModel($conn);
        $this->appointmentController = new AppointmentController($conn);
    }

    public function getByPatient(string $patientID): array
    {
        return $this->notificationModel->getByPatient($patientID);
    }

    public function getUnreadCount(string $patientID): int
    {
        return $this->notificationModel->getUnreadCount($patientID);
    }

    public function markAsRead(string $notificationID): void
    {
        $this->notificationModel->markAsRead($notificationID);
    }

    public function markAllAsRead(string $patientID): void
    {
        $this->notificationModel->markAllAsRead($patientID);
    }

    public function processResponse(string $choice, string $appointmentID): array
    {
        return $this->appointmentController->processResponse($choice, $appointmentID);
    }

    public function sendReminder(string $appointmentID): void
    {
        $this->notificationModel->sendReminderForAppointment($appointmentID);
    }
}
