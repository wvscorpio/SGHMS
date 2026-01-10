<?php
include '../db/dbcon.php';

// ===================== HANDLE CRUD ===================== //
$sql = "
SELECT a.*, p.name AS patientName, d.name AS doctorName
FROM appointment a
JOIN patient p ON a.patientID = p.patientID
JOIN doctor d ON a.doctorID = d.doctorID
";

$result = $conn->query($sql);
$appointments = $result->fetch_all(MYSQLI_ASSOC);

$patients = $conn->query("SELECT * FROM patient")->fetch_all(MYSQLI_ASSOC);
$doctors  = $conn->query("SELECT * FROM doctor")->fetch_all(MYSQLI_ASSOC);


// CREATE / UPDATE APPOINTMENT //
function generateAppointmentID($conn) {
    $sql = "SELECT appointmentID FROM appointment ORDER BY appointmentID DESC LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastID = $row['appointmentID'];  
        $num = intval(substr($lastID, 3)) + 1;
    } else {
        $num = 1;
    }

    return 'APT' . str_pad($num, 3, '0', STR_PAD_LEFT);
}

// ================= CREATE ================= //
if (isset($_POST['create_appointment'])) {
    $newID = generateAppointmentID($conn); 
    $date = $_POST['date'];
    $time = $_POST['time'];
    $reason = $_POST['reason'];
    $status = "Pending"; // default
    $patientID = $_POST['patient'];
    $doctorID = $_POST['doctor'];

    $stmt = $conn->prepare("
        INSERT INTO appointment (appointmentID, appointmentDate, appointmentTime, reason, status, patientID, doctorID)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssssss", $newID, $date, $time, $reason, $status, $patientID, $doctorID);
    $stmt->execute();

    header("Location: manageAppointment.php");
    exit();
}

// ================= UPDATE ================= //
if (isset($_POST['update_appointment'])) {
    $id = $_POST['appointmentID'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $reason = $_POST['reason'];
    $status = $_POST['status'];
    $patientID = $_POST['patient'];
    $doctorID = $_POST['doctor'];

    $stmt = $conn->prepare("
        UPDATE appointment 
        SET appointmentDate=?, appointmentTime=?, reason=?, status=?, patientID=?, doctorID=? 
        WHERE appointmentID=?
    ");
    $stmt->bind_param("sssssss", $date, $time, $reason, $status, $patientID, $doctorID, $id);
    $stmt->execute();

    header("Location: manageAppointment.php");
    exit();
}


//DELETE APPOINTMENT
if (isset($_GET['id'])) {
    $stmt = $conn->prepare("DELETE FROM appointment WHERE appointmentID=?");
    $stmt->bind_param("s", $_GET['id']);
    $stmt->execute();

    header("Location: manageAppointment.php");
    exit();
}

//UPDATE STATUS
if (isset($_POST['appointmentID'], $_POST['status'])) {

    $stmt = $conn->prepare(
        "UPDATE appointment SET status=? WHERE appointmentID=?"
    );
    $stmt->bind_param("ss", $_POST['status'], $_POST['appointmentID']);
    $stmt->execute();

    echo "success";
}

$timeSlots = ['08:00 AM', '09:00 AM', '10:00 AM', '11:00 AM', '12:00 PM', '01:00 PM', '02:00 PM', '03:00 PM', '04:00 PM', '05:00 PM'];
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
                                <option value="<?= $pat['patientID']; ?>">
                                    <?= htmlspecialchars($pat['name']); ?>
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
                        <td><?= $a['appointmentID'] ?></td>
                        <td><?= htmlspecialchars($a['patientName']) ?></td>
                        <td><?= htmlspecialchars($a['doctorName']) ?></td>
                        <td><?= $a['appointmentDate'] ?></td>
                        <td><?= $a['appointmentTime'] ?></td>
                        <td>
                            <span class="badge confirmed">Confirmed</span>
                        </td>
                        <td>
                            <span class="badge <?= strtolower($a['status']) ?>">
                                <?= htmlspecialchars($a['status']) ?>
                            </span>
                        </td>
                        <td class="actions">
                            <select class="status-select" data-id="<?= $a['appointmentID'] ?>">
                                <option value="Pending" <?= $a['status']=="Pending"?"selected":"" ?>>Pending</option>
                                <option value="Confirmed" <?= $a['status']=="Confirmed"?"selected":"" ?>>Confirmed</option>
                                <option value="Completed" <?= $a['status']=="Completed"?"selected":"" ?>>Completed</option>
                                <option value="Cancelled" <?= $a['status']=="Cancelled"?"selected":"" ?>>Cancelled</option>
                            </select>

                            <a href="deleteAppointment.php?id=<?= $a['appointmentID'] ?>" 
                            class="btn-delete" onclick="return confirm('Delete this appointment?')">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                                    <path d="M3 6h18" stroke="white" stroke-width="2"/>
                                    <path d="M8 6v-2h8v2" stroke="white" stroke-width="2"/>
                                    <rect x="6" y="6" width="12" height="14" rx="2" stroke="white" stroke-width="2"/>
                                    <path d="M10 11v6M14 11v6" stroke="white" stroke-width="2"/>
                                </svg>
                            </a>

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

                    const appointmentID = this.dataset.id;
                    const newStatus = this.value;

                    fetch('updateAppointmentStatus.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `appointmentID=${appointmentID}&status=${newStatus}`
                    })
                    .then(res => res.text())
                    .then(response => {
                        if (response === 'success') {
                            const badge = this.closest('tr').querySelector('.badge');
                            badge.className = 'badge ' + newStatus.toLowerCase();
                            badge.textContent = newStatus;
                        } else {
                            alert('Failed to update status');
                        }
                    });
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