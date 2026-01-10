<?php
include '../db/dbcon.php';

// ================= UPDATE STATUS ================= //
if (isset($_POST['appointmentID'], $_POST['status'])) {
    $stmt = $conn->prepare("UPDATE appointment SET status=? WHERE appointmentID=?");
    $stmt->bind_param("ss", $_POST['status'], $_POST['appointmentID']);
    $stmt->execute();

    echo "success";
    exit;
}

// ================= GET APPOINTMENTS ================= //
$sql = "
SELECT DISTINCT a.appointmentID,
       a.appointmentDate,
       a.appointmentTime,
       a.reason,
       a.status,
       a.patientID,
       a.doctorID,
       p.name AS patientName,
       d.name AS doctorName
FROM appointment a
INNER JOIN patient p ON a.patientID = p.patientID
INNER JOIN doctor d ON a.doctorID = d.doctorID
";
$result = $conn->query($sql);
$appointments = $result->fetch_all(MYSQLI_ASSOC);

// ================= GET PATIENTS & DOCTORS ================= //
$patients = $conn->query("SELECT * FROM patient")->fetch_all(MYSQLI_ASSOC);
$doctors  = $conn->query("SELECT * FROM doctor")->fetch_all(MYSQLI_ASSOC);

// ================= GENERATE APPOINTMENT ID ================= //
function generateAppointmentID($conn) {
    $sql = "SELECT appointmentID FROM appointment ORDER BY appointmentID DESC LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastID = $row['appointmentID'];   
        $num = intval(substr($lastID, 3)) + 1;
    } else {
        $num = 1;
    }
    return 'APT' . str_pad($num, 3, '0', STR_PAD_LEFT);
}

// ================= CREATE APPOINTMENT ================= //
if (isset($_POST['create_appointment'])) {
    $newID = generateAppointmentID($conn); 
    $date = $_POST['date'];
    $time = date("H:i:s", strtotime($_POST['time']));
    $reason = $_POST['reason'];
    $status = "Pending"; 
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

// ================= UPDATE APPOINTMENT ================= //
if (isset($_POST['update_appointment'])) {
    $id = $_POST['appointmentID'];
    $date = $_POST['date'];
    $time = date("H:i:s", strtotime($_POST['time']));
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

// ================= DELETE APPOINTMENT ================= //
if (isset($_GET['id'])) {
    $stmt = $conn->prepare("DELETE FROM appointment WHERE appointmentID=?");
    $stmt->bind_param("s", $_GET['id']);
    $stmt->execute();

    header("Location: manageAppointment.php");
    exit();
}

// ================= FUNCTION: GET DOCTOR TIME SLOTS ================= //
function getDoctorTimeSlots($conn, $doctorID, $date){
    $dayOfWeek = date('l', strtotime($date));

    // Ambil schedule doktor
    $stmt = $conn->prepare("SELECT startTime, endTime FROM doctor_schedule WHERE doctorID=? AND dayOfWeek=?");
    $stmt->bind_param("ss", $doctorID, $dayOfWeek);
    $stmt->execute();
    $result = $stmt->get_result();

    $slots = [];
    while($row = $result->fetch_assoc()){
        $start = strtotime($row['startTime']);
        $end   = strtotime($row['endTime']);
        while($start < $end){
            $slots[] = date('H:i', $start);
            $start = strtotime('+30 minutes', $start);
        }
    }

    // Hapus slot yang dah ditempah
    $stmt2 = $conn->prepare("SELECT appointmentTime FROM appointment WHERE doctorID=? AND appointmentDate=?");
    $stmt2->bind_param("ss", $doctorID, $date);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    $booked = [];
    while($r = $result2->fetch_assoc()){
        $booked[] = date('H:i', strtotime($r['appointmentTime']));
    }

    return array_values(array_diff($slots, $booked));
}
?>

<!DOCTYPE html>
<html>
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
        <p>Welcome, <?= htmlspecialchars($_SESSION['fullname'] ?? 'Staff'); ?></p>
    </div>
    <form method = "post" action = "../auth/logout.php">
        <button type = "Submit">Logout</button>
    </form>
</div>

<div class="topnav">
    <a class="active" href="manageAppointment.php">Appointments</a>
    <a href="manageDoctor.php">Doctors</a>
    <a href="managePatient.php">Patients</a>
</div>

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
                <select name = "doctor" id="doctorSelect" required>
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
                    <input type="date" name="date" id="aptDate" min="<?= date('Y-m-d'); ?>" required>
                </label>

                <label>Time
                    <select name="time" id="timeSelect" required>
                        <option value="">Choose a time slot</option>
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
                <td><?= date('H:i A', strtotime($a['appointmentTime'])) ?></td>
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

                    <a href="manageAppointment.php?id=<?= $a['appointmentID'] ?>" 
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

<script>
function formNewApt() {
    document.getElementById("staffBookCard").style.display = "block";
}   

function formCloseNewApt(){
    document.getElementById("staffBookCard").style.display = "none";
}

// ================= DYNAMIC TIME SLOTS ================= //
const doctorSelect = document.getElementById('doctorSelect');
const dateSelect = document.getElementById('aptDate');
const timeSelect = document.getElementById('timeSelect');

function loadTimeSlots() {
    const doctorID = doctorSelect.value;
    const date = dateSelect.value;
    if (!doctorID || !date) return;

    fetch('getTimeSlots.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body:`doctorID=${doctorID}&date=${date}`
    })
    .then(res=>res.json())
    .then(slots=>{
        timeSelect.innerHTML = '<option value="">Choose a time slot</option>';
        slots.forEach(s=>{
            const opt = document.createElement('option');
            opt.value = s;
            opt.textContent = s;
            timeSelect.appendChild(opt);
        });
    });
}

doctorSelect.addEventListener('change', loadTimeSlots);
dateSelect.addEventListener('change', loadTimeSlots);

// ================= UPDATE STATUS ================= //
document.querySelectorAll('.status-select').forEach(select => {
    select.addEventListener('change', function () {
        const appointmentID = this.dataset.id;
        const newStatus = this.value;

        fetch('manageAppointment.php', {
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

// ================= SEARCH FILTER ================= //
document.getElementById('searchInput').addEventListener('keyup', function () {
    const keyword = this.value.toLowerCase();
    const rows = document.querySelectorAll('.appointment-table tbody tr');

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(keyword) ? '' : 'none';
    });
});
</script>
</body>
</html>
