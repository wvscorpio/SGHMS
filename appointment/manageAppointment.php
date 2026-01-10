<?php
include '../db/dbcon.php';

$appointments = [
    [
        "id" => "APT001",
        "patient" => "Ahmad Rizal",
        "doctor" => "Dr. Ahmad Hassan",
        "date" => "1/5/2025",
        "time" => "10:00",
        "status" => "confirmed"
    ]
];
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
        <link rel="stylesheet" href="../css/aptstyle.css"/>
    </head>

    <body>
        <div class = "header">
            <div>
                <h1>Sarawak General Hospital</h1>
                <p>Welcome, </p>
            </div>
            <form method = "post" action = "logout.php">
                <button type = "Submit">Logout</button>
            </form>
        </div>

        <div class="topnav">
            <a class="active" href="manageAppointment.php">Appointments</a>
            <a href="manageDoctor.php">Doctors</a>
            <a href="managePatient.php">Patients</a>
        </div>

        <!-- Appointment Tab -->
        <div class="cardheader">
            <div class="card-header">
                <div>
                    <h3>Manage Appointments</h3>
                    <p>View and manage all hospital appointments</p>
                </div>
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Search appointment">
                </div>
                <div class = "button_new_apt">
                    <button onclick = "formNewApt()">New Appointment</button>
                </div>
            </div>    
                
            <!-- Appointment Card Popup -->
            <div class="staffcard" id="staffBookCard" style="display:none;">
                <form method = "post" class="form-container">

                    <div class = "button_closenew_apt">
                        <button onclick = "formCloseNewApt()" class="close-btn" type="button">&times;</button>
                    </div>

                    <h2>Create Appointment</h2>
                    <label>Schedule a new appointment</label><br><br>

                    <label>Patient<br>
                        <select name = "patient" required>
                            <option value = "">Select patient</option>
                            <?php foreach ($patients as $pat): ?>
                                <option value = "<?= $pat['patientID']; ?>">
                                </option> 
                            <?php endforeach; ?>
                        </select>
                    </label><br><br>

                    <label>Doctor<br>
                        <select name = "doctor" required>
                            <option value = "">Select doctor</option>
                            <?php foreach ($doctors as $doc): ?>
                                <option value = "<?= $doc['doctorID']; ?>">
                                    <?= htmlspecialchars($doc['name'] . " - " . $doc['specialization']); ?>
                                </option> 
                            <?php endforeach; ?>
                        </select>
                    </label> <br>

                    <div class = "row">
                        <label>Date<br>
                            <input type="date" name="date" min="<?= date('Y-m-d'); ?>" required>
                        </label>

                        <label>Time
                            <select name="time" required>
                                <option value="">Choose a time slot</option>
                                <?php foreach ($timeSlots as $time): ?>
                                    <option value="<?= $time; ?>"><?= $time; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>

                    <br>
                    <label>Reason<br>
                        <textarea name="reason" placeholder="Reason for appointment" required></textarea>
                    </label><br><br>

                    <div class="form-actions">
                        <button type="submit" name="create_appointment">Create Appointment</button>
                        <button type="reset" name="cancel_appointment">Cancel</button>
                    </div>
                </form>
            </div>
            
            <!-- Appointment Table View -->
            <table class="appointment-table">
                <thead>
                <tr>
                    <th>Appointment ID</th>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>

                <tbody>
                    <?php foreach ($appointments as $a): ?>
                    <tr>
                        <td><?= $a['id'] ?></td>
                        <td><?= $a['patient'] ?></td>
                        <td><?= $a['doctor'] ?></td>
                        <td><?= $a['date'] ?></td>
                        <td><?= $a['time'] ?></td>
                        <td>
                            <span class="badge confirmed">Confirmed</span>
                        </td>
                        <td class="actions">
                            <select class="status-select">
                                <option value="confirmed" selected>Confirmed</option>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <button class="btn-delete" title="Delete">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                                    <path d="M3 6h18" stroke="white" stroke-width="2"/>
                                    <path d="M8 6v-2h8v2" stroke="white" stroke-width="2"/>
                                    <rect x="6" y="6" width="12" height="14" rx="2" stroke="white" stroke-width="2"/>
                                    <path d="M10 11v6M14 11v6" stroke="white" stroke-width="2"/>
                                </svg>
                            </button>

                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </body>

      <script>
            function formNewApt() {
                document.getElementById("staffBookCard").style.display = "block";
            }   

            function formCloseNewApt(){
                document.getElementById("staffBookCard").style.display = "none";
            }

            document.querySelectorAll('.status-select').forEach(select => {
                select.addEventListener('change', function () {
                    const row = this.closest('tr');
                    const badge = row.querySelector('.badge');

                    const newStatus = this.value;
                    badge.className = 'badge ' + newStatus;
                    badge.textContent = newStatus;
                });
            });

            document.getElementById('searchInput').addEventListener('keyup', function () {
                const keyword = this.value.toLowerCase();
                const rows = document.querySelectorAll('.appointment-table tbody tr');

                rows.forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(keyword) ? '' : 'none';
                });
            });
        </script>
</html>