<?php
include '../db/dbcon.php';

// ===================== CREATE / UPDATE DOCTOR ===================== //
function generateDoctorID($conn) {
    $sql = "SELECT doctorID FROM doctor ORDER BY doctorID DESC LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastID = $row['doctorID'];  
        $num = intval(substr($lastID, 3)) + 1;
    } else {
        $num = 1;
    }

    return 'DOC' . str_pad($num, 3, '0', STR_PAD_LEFT);
}

if (isset($_POST['save_doctor'])) {
    $id = $_POST['doctorID'] ?? null;
    $name = $_POST['name'];
    $specialization = $_POST['specialization'];
    $contact = $_POST['contactDetails'];

    if ($id) {
        // UPDATE
        $stmt = $conn->prepare("
            UPDATE doctor 
            SET name=?, specialization=?, contactDetails=?
            WHERE doctorID=?
        ");
        $stmt->bind_param("ssss", $name, $specialization, $contact, $id);
        $stmt->execute();
    } else {
        // INSERT
        $newID = generateDoctorID($conn);
        $stmt = $conn->prepare("
            INSERT INTO doctor (doctorID, name, specialization, contactDetails) VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("ssss", $newID, $name, $specialization, $contact);
        $stmt->execute();
    }

    header("Location: manageDoctor.php");
    exit();
}

// ===================== DELETE DOCTOR ===================== //
if (isset($_GET['delete'])) {

    $stmt = $conn->prepare("DELETE FROM doctor WHERE doctorID=?");
    $stmt->bind_param("s", $_GET['delete']);
    $stmt->execute();

    header("Location: manageDoctor.php");
    exit();
}

// ===================== FETCH DOCTORS ===================== //
$result = $conn->query("SELECT * FROM doctor");
$doctors = $result->fetch_all(MYSQLI_ASSOC);
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
                <p>Welcome, <?= htmlspecialchars($_SESSION['fullname'] ?? 'Staff'); ?></p>
            </div>
            <form method = "post" action = "../auth/logout.php">
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
                            <option>Cardiology</option>
                            <option>Neurology</option>
                            <option>Pediatrics</option>
                            <option>Orthopedics</option>
                            <option>Dermatology</option>
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

                            <a href="manageDoctor.php?delete=<?= $d['doctorID'] ?>"
                                class="btn-delete" onclick="return confirm('Delete this doctor?')" title="Delete Doctor">
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