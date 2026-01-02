<?php
include '../db/dbcon.php';

// ===================== HANDLE CRUD ===================== //
// DOCTOR CRUD
/*if (isset($_POST['save_doctor'])) {
    $id = $_POST['doctorID'] ?? null;
    $name = $_POST['name'];
    $specialization = $_POST['specialization'];
    $contact = $_POST['contactDetails'];

    if ($id) {
        $stmt = $pdo->prepare("UPDATE doctor SET name=?, specialization=?, contactDetails=? WHERE doctorID=?");
        $stmt->execute([$name, $specialization, $contact, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO doctor (name, specialization, contactDetails) VALUES (?, ?, ?)");
        $stmt->execute([$name, $specialization, $contact]);
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET['delete_doctor'])) {
    $stmt = $pdo->prepare("DELETE FROM doctor WHERE doctorID=?");
    $stmt->execute([$_GET['delete_doctor']]);
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// PATIENT CRUD
if (isset($_POST['save_patient'])) {
    $id = $_POST['patientID'] ?? null;
    $name = $_POST['name'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $contact = $_POST['contactNumber'];
    $history = $_POST['medicalHistory'];

    if ($id) {
        $stmt = $pdo->prepare("UPDATE patient SET name=?, age=?, gender=?, contactNumber=?, medicalHistory=? WHERE patientID=?");
        $stmt->execute([$name, $age, $gender, $contact, $history, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO patient (name, age, gender, contactNumber, medicalHistory) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $age, $gender, $contact, $history]);
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET['delete_patient'])) {
    $stmt = $pdo->prepare("DELETE FROM patient WHERE patientID=?");
    $stmt->execute([$_GET['delete_patient']]);
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// APPOINTMENT CRUD
if (isset($_POST['save_appointment'])) {
    $id = $_POST['appointmentID'] ?? null;
    $patientID = $_POST['patientID'];
    $doctorID = $_POST['doctorID'];
    $date = $_POST['appointmentDate'];
    $time = $_POST['appointmentTime'];
    $reason = $_POST['reason'];
    $status = $_POST['status'];

    if ($id) {
        $stmt = $pdo->prepare("UPDATE appointment SET patientID=?, doctorID=?, appointmentDate=?, appointmentTime=?, reason=?, status=? WHERE appointmentID=?");
        $stmt->execute([$patientID, $doctorID, $date, $time, $reason, $status, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO appointment (patientID, doctorID, appointmentDate, appointmentTime, reason, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$patientID, $doctorID, $date, $time, $reason, $status]);
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET['delete_appointment'])) {
    $stmt = $pdo->prepare("DELETE FROM appointment WHERE appointmentID=?");
    $stmt->execute([$_GET['delete_appointment']]);
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// ===================== FETCH DATA ===================== //
$doctors = $pdo->query("SELECT * FROM doctor")->fetchAll(PDO::FETCH_ASSOC);
$patients = $pdo->query("SELECT * FROM patient")->fetchAll(PDO::FETCH_ASSOC);
$appointments = $pdo->query("
    SELECT a.*, p.name AS patientName, d.name AS doctorName 
    FROM appointment a
    JOIN patient p ON a.patientID = p.patientID
    JOIN doctor d ON a.doctorID = d.doctorID
")->fetchAll(PDO::FETCH_ASSOC);


// FUNCTION FOR BADGE COLORS
function getStatusColor($status) {
    switch($status) {
        case "pending": return "yellow";
        case "confirmed": return "blue";
        case "completed": return "green";
        case "cancelled": return "red";
        default: return "gray";
    }
}*/
?>


<!DOCTYPE html>
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Staff Manage Appointments</title>
        <link rel="stylesheet" href="../css/style.css" />

        <style>
            body { font-family: Arial; background:#f5f5f5; }
            .container { max-width:1200px; margin:20px auto; padding:20px; background:#fff; border-radius:8px; }
            table { width:100%; border-collapse:collapse; margin-bottom:30px; }
            th, td { border:1px solid #ddd; padding:8px; }
            th { background:#eee; }
            .badge { padding:3px 8px; border-radius:4px; color:#fff; }
            .badge.yellow { background:#f0ad4e; }
            .badge.blue { background:#0275d8; }
            .badge.green { background:#5cb85c; }
            .badge.red { background:#d9534f; }
            form { margin-bottom:20px; }
            input, select, textarea, button { padding:5px; margin-top:5px; width:100%; box-sizing:border-box; }
            button { cursor:pointer; }
        </style>
    </head>

    <body>
       <div class="container">
            <h1>Staff Dashboard</h1>

            <!-- DOCTOR FORM -->
            <h2>Doctors</h2>
            <form method="POST">
            <input type="hidden" name="doctorID" value="<?= $_GET['edit_doctor'] ?? '' ?>">
            <label>Name</label>
            <input type="text" name="name" value="<?= $_GET['edit_doctor'] ? $pdo->query("SELECT * FROM doctor WHERE doctorID=".$_GET['edit_doctor'])->fetch()['name'] : '' ?>" required>
            <label>Specialization</label>
            <select name="specialization">
            <option value="General Practitioner">General Practitioner</option>
            <option value="Cardiology">Cardiology</option>
            <option value="Neurology">Neurology</option>
            <option value="Orthopedics">Orthopedics</option>
            <option value="Pediatrics">Pediatrics</option>
            <option value="Dermatology">Dermatology</option>
            </select>
            <label>Contact Details</label>
            <input type="text" name="contactDetails" value="<?= $_GET['edit_doctor'] ? $pdo->query("SELECT * FROM doctor WHERE doctorID=".$_GET['edit_doctor'])->fetch()['contactDetails'] : '' ?>" required>
            <button type="submit" name="save_doctor">Save Doctor</button>
            </form>

        <table>
        <tr><th>ID</th><th>Name</th><th>Specialization</th><th>Contact</th><th>Action</th></tr>
        <?php foreach($doctors as $doc): ?>
        <tr>
        <td><?= $doc['doctorID'] ?></td>
        <td><?= $doc['name'] ?></td>
        <td><?= $doc['specialization'] ?></td>
        <td><?= $doc['contactDetails'] ?></td>
        <td>
        <a href="?edit_doctor=<?= $doc['doctorID'] ?>">Edit</a> |
        <a href="?delete_doctor=<?= $doc['doctorID'] ?>" onclick="return confirm('Delete this doctor?')">Delete</a>
        </td>
        </tr>
        <?php endforeach; ?>
        </table>

        <!-- PATIENT FORM -->
        <h2>Patients</h2>
        <form method="POST">
        <input type="hidden" name="patientID" value="<?= $_GET['edit_patient'] ?? '' ?>">
        <label>Name</label>
        <input type="text" name="name" value="<?= $_GET['edit_patient'] ? $pdo->query("SELECT * FROM patient WHERE patientID=".$_GET['edit_patient'])->fetch()['name'] : '' ?>" required>
        <label>Age</label>
        <input type="number" name="age" value="<?= $_GET['edit_patient'] ? $pdo->query("SELECT * FROM patient WHERE patientID=".$_GET['edit_patient'])->fetch()['age'] : '' ?>" required>
        <label>Gender</label>
        <select name="gender">
        <option value="Male">Male</option>
        <option value="Female">Female</option>
        <option value="Other">Other</option>
        </select>
        <label>Contact Number</label>
        <input type="text" name="contactNumber" value="<?= $_GET['edit_patient'] ? $pdo->query("SELECT * FROM patient WHERE patientID=".$_GET['edit_patient'])->fetch()['contactNumber'] : '' ?>" required>
        <label>Medical History</label>
        <textarea name="medicalHistory"><?= $_GET['edit_patient'] ? $pdo->query("SELECT * FROM patient WHERE patientID=".$_GET['edit_patient'])->fetch()['medicalHistory'] : '' ?></textarea>
        <button type="submit" name="save_patient">Save Patient</button>
        </form>

        <table>
        <tr><th>ID</th><th>Name</th><th>Age</th><th>Gender</th><th>Contact</th><th>History</th><th>Action</th></tr>
        <?php foreach($patients as $pat): ?>
        <tr>
        <td><?= $pat['patientID'] ?></td>
        <td><?= $pat['name'] ?></td>
        <td><?= $pat['age'] ?></td>
        <td><?= $pat['gender'] ?></td>
        <td><?= $pat['contactNumber'] ?></td>
        <td><?= $pat['medicalHistory'] ?></td>
        <td>
        <a href="?edit_patient=<?= $pat['patientID'] ?>">Edit</a> |
        <a href="?delete_patient=<?= $pat['patientID'] ?>" onclick="return confirm('Delete this patient?')">Delete</a>
        </td>
        </tr>
        <?php endforeach; ?>
        </table>

        <!-- APPOINTMENT FORM -->
        <h2>Appointments</h2>
        <form method="POST">
        <input type="hidden" name="appointmentID" value="<?= $_GET['edit_appointment'] ?? '' ?>">
        <label>Patient</label>
        <select name="patientID">
        <?php foreach($patients as $pat): ?>
        <option value="<?= $pat['patientID'] ?>"><?= $pat['name'] ?></option>
        <?php endforeach; ?>
        </select>
        <label>Doctor</label>
        <select name="doctorID">
        <?php foreach($doctors as $doc): ?>
        <option value="<?= $doc['doctorID'] ?>"><?= $doc['name'] ?></option>
        <?php endforeach; ?>
        </select>
        <label>Date</label>
        <input type="date" name="appointmentDate" required>
        <label>Time</label>
        <input type="time" name="appointmentTime" required>
        <label>Reason</label>
        <textarea name="reason"></textarea>
        <label>Status</label>
        <select name="status">
        <option value="pending">Pending</option>
        <option value="confirmed">Confirmed</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
        </select>
        <button type="submit" name="save_appointment">Save Appointment</button>
        </form>

        <table>
        <tr><th>ID</th><th>Patient</th><th>Doctor</th><th>Date</th><th>Time</th><th>Status</th><th>Action</th></tr>
        <?php foreach($appointments as $apt): ?>
        <tr>
        <td><?= $apt['appointmentID'] ?></td>
        <td><?= $apt['patientName'] ?></td>
        <td><?= $apt['doctorName'] ?></td>
        <td><?= $apt['appointmentDate'] ?></td>
        <td><?= $apt['appointmentTime'] ?></td>
        <td><span class="badge <?= getStatusColor($apt['status']) ?>"><?= ucfirst($apt['status']) ?></span></td>
        <td>
        <a href="?edit_appointment=<?= $apt['appointmentID'] ?>">Edit</a> |
        <a href="?delete_appointment=<?= $apt['appointmentID'] ?>" onclick="return confirm('Delete this appointment?')">Delete</a>
        </td>
        </tr>
        <?php endforeach; ?>
        </table>
        </div>
    </body>
    
</html>