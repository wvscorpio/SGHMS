<?php
include '../db/dbcon.php';

$doctors = [
    [
        "doctorID" => "DOC001",
        "name" => "Dr. Rizal",
        "specialization" => "Cardiology",
        "contactDetails" => "+6012-345 6789 | rizal.hassan@sarawak-hospital.my"
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
        <link rel="stylesheet" href="../css/dctStyle.css"/>
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
            <a href="manageAppointment.php">Appointments</a>
            <a class="active" href="manageDoctor.php">Doctors</a>
            <a href="managePatient.php">Patients</a>
        </div>

        <!-- Doctor Tab -->
        <div class="cardheader">
            <div class="card-header">
                <div>
                    <h3>Manage Doctors</h3>
                    <p>Add, edit, or remove doctor information</p>
                </div>
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Search doctor">
                </div>
                <div class = "button_new_dct">
                    <button onclick = "formAddDct()">Add Doctor</button>
                </div>
            </div>    
                
            <!-- Doctor Card Popup -->
            <div class="staffcard" id="staffBookCard" style="display:none;">
                <form method="post" class="form-container">

                    <input type="hidden" name="doctorID" id="doctorID">

                    <button type="button" class="close-btn" onclick="formCloseAddDct()">&times;</button>

                    <h2 id="doctorFormTitle">Add New Doctor</h2>
                    <label id="doctorFormSubtitle">Enter doctor information</label><br><br>

                    <label>Full Name<br>
                        <input type="text" name="name" id="doctorName" required>
                    </label><br><br>

                    <label>Specialization<br>
                        <select name="specialization" id="doctorSpecialization" required>
                            <option value="">Select specialization</option>
                            <?php foreach ($doctors as $doc): ?>
                                <option value="<?= $doc['specialization']; ?>">
                                    <?= $doc['specialization']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label><br><br>

                    <label>Contact Details<br>
                        <textarea name="contactDetails" id="doctorContact" required></textarea>
                    </label><br><br>

                    <div class="form-actions">
                        <button type="submit" name="save_doctor">Save</button>
                        <button type="button" id="doctorCancelBtn">Cancel</button>
                    </div>
                </form>
            </div>


             <!-- Doctor Table View -->
            <table class="doctor-table">
                <thead>
                <tr>
                    <th>Doctor ID</th>
                    <th>Name</th>
                    <th>Specialization</th>
                    <th>Contact Details</th>
                    <th>Actions</th>
                </tr>
                </thead>

                <tbody>
                    <?php foreach ($doctors as $d): ?>
                    <tr>
                        <td><?= $d['doctorID'] ?></td>
                        <td><?= $d['name'] ?></td>
                        <td><?= $d['specialization'] ?></td>
                        <td><?= $d['contactDetails'] ?></td>

                        <td class="actions">
                            <button class="btn-edit"
                                onclick="openEditDoctor(
                                    '<?= $d['doctorID'] ?>',
                                    '<?= htmlspecialchars($d['name']) ?>',
                                    '<?= htmlspecialchars($d['specialization']) ?>',
                                    '<?= htmlspecialchars($d['contactDetails']) ?>'
                                )">
                                ✏️
                            </button>

                            <button 
                                class="btn-delete" 
                                onclick="return confirm('Delete this doctor?')"
                                title="Delete Doctor">
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
            function formAddDct() {
                document.getElementById("staffBookCard").style.display = "flex";

                document.getElementById("doctorFormTitle").innerText = "Add New Doctor";
                document.getElementById("doctorFormSubtitle").innerText = "Enter doctor information";

                document.getElementById("doctorID").value = "";
                document.getElementById("doctorName").value = "";
                document.getElementById("doctorSpecialization").value = "";
                document.getElementById("doctorContact").value = "";

                let cancelBtn = document.getElementById("doctorCancelBtn");
                cancelBtn.onclick = function() {
                    document.querySelector('.form-container').reset();
                }
            }

            function openEditDoctor(id, name, specialization, contact) {
                document.getElementById("staffBookCard").style.display = "flex";

                document.getElementById("doctorFormTitle").innerText = "Edit Doctor";
                document.getElementById("doctorFormSubtitle").innerText = "Update doctor information";

                document.getElementById("doctorID").value = id;
                document.getElementById("doctorName").value = name;
                document.getElementById("doctorSpecialization").value = specialization;
                document.getElementById("doctorContact").value = contact;

                let cancelBtn = document.getElementById("doctorCancelBtn");
                cancelBtn.onclick = formCloseAddDct;
            }


            function formCloseAddDct() {
                document.getElementById("staffBookCard").style.display = "none";
            }

            document.getElementById('searchInput').addEventListener('keyup', function () {
                const keyword = this.value.toLowerCase();
                const rows = document.querySelectorAll('.doctor-table tbody tr');

                rows.forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(keyword) ? '' : 'none';
                });
            });
            </script>

</html>