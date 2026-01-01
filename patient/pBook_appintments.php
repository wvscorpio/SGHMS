<?php
session_start();
require 'db/dbcon.php';

$userName = "Aqilah Sairi";
$patientID = 1; // assuming patientID from your database (int)

// Fetch patient info
$stmt = $pdo->prepare("SELECT * FROM patient WHERE patientID = ?");
$stmt->execute([$patientID]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);
if ($patient) {
    $userName = $patient['name'];
}

// Fetch doctors from database
$doctors = $pdo->query("SELECT * FROM doctor")->fetchAll(PDO::FETCH_ASSOC);

// Time slots (you can also make this dynamic)
$timeSlots = ["09:00", "10:00", "11:00", "14:00", "15:00", "16:00"];

// Handle new appointment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $selectedDoctorID = $_POST['doctor'];
    $selectedDate = $_POST['date'];
    $selectedTime = $_POST['time'];
    $reason = $_POST['reason'];

    // Insert new appointment into database
    $stmt = $pdo->prepare("INSERT INTO appointment (appointmentDate, appointmentTime, reason, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$selectedDate, $selectedTime, $reason, 'pending']);

    // Get the inserted appointment ID
    $appointmentID = $pdo->lastInsertId();

    // Assign doctor & patient to this appointment using junction table logic (or update if appointment table has doctorID & patientID)
    $pdo->prepare("UPDATE appointment SET patientID=?, doctorID=? WHERE appointmentID=?")
        ->execute([$patientID, $selectedDoctorID, $appointmentID]);

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch patient's appointments
$appointments = $pdo->prepare("
    SELECT a.*, d.name AS doctorName, d.specialization 
    FROM appointment a
    JOIN doctor d ON a.doctorID = d.doctorID
    WHERE a.patientID = ?
    ORDER BY a.appointmentDate DESC, a.appointmentTime DESC
");
$appointments->execute([$patientID]);
$appointments = $appointments->fetchAll(PDO::FETCH_ASSOC);

// Helper function for status color
function getStatusColor($status) {
    switch ($status) {
        case "pending": return "yellow";
        case "confirmed": return "blue";
        case "completed": return "green";
        case "cancelled": return "red";
        default: return "gray";
    }
}

?>

<!DOCTYPE html>
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Patient Book Appointments</title>
        <link rel="stylesheet" href="#" />

        <style>
            body { font-family: Arial, sans-serif; background: #f0f0f0; }
            .header { background: #fff; padding: 1rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ccc; }
            .card { background: #fff; padding: 1rem; margin-bottom: 1rem; border-radius: 5px; }
            .badge { padding: 0.25rem 0.5rem; border-radius: 3px; color: #fff; }
            .badge.yellow { background: #f59e0b; }
            .badge.blue { background: #3b82f6; }
            .badge.green { background: #10b981; }
            .badge.red { background: #ef4444; }
        </style>
    </head>

    <body>
        <div class = "header">
            <div>
                <h1>Sarawak General Hospital</h1>
                <p> Welcome, <?php echo #;?> </p>
            </div>
            <form method = "post" action = "logout.php">
                <button type = "Submit">Logout</button>
            </form>
        </div>

        <!-- Booking Form -->
        <div class="card">
            <h3>Book New Appointment</h3>
            <form method="post">
                <label>Select Doctor<br>
                    <select name="doctor" required>
                        <option value="">Choose a doctor</option>
                        <?php foreach ($doctors as $doc): ?>
                            <option value="<?= $doc['doctorID']; ?>">
                                <?= htmlspecialchars($doc['name'] . " - " . $doc['specialization']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label><br><br>

                <label>Appointment Date<br>
                    <input type="date" name="date" min="<?= date('Y-m-d'); ?>" required>
                </label><br><br>

                <label>Appointment Time<br>
                    <select name="time" required>
                        <option value="">Choose a time slot</option>
                        <?php foreach ($timeSlots as $time): ?>
                            <option value="<?= $time; ?>"><?= $time; ?></option>
                        <?php endforeach; ?>
                    </select>
                </label><br><br>

                <label>Reason for Visit<br>
                    <textarea name="reason" required></textarea>
                </label><br><br>

                <button type="submit" name="book_appointment">Book Appointment</button>
            </form>
        </div>
    </body>
</html>