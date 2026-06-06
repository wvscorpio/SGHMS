<?php
require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

$appointmentController = new AppointmentController($conn);

if (isset($_POST['doctorID'], $_POST['date'])) {
    echo json_encode($appointmentController->getTimeSlots($_POST['doctorID'], $_POST['date']));
    exit;
}

echo json_encode([]);
