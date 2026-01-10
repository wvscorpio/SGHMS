<?php
include '../db/dbcon.php';

/* ===================== SAVE / UPDATE PATIENT ===================== */
function generatePatientID($conn) {
    $sql = "SELECT patientID FROM patient ORDER BY patientID DESC LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastID = $row['patientID'];   
        $num = intval(substr($lastID, 3)) + 1;
    } else {
        $num = 1;
    }

    return 'PAT' . str_pad($num, 3, '0', STR_PAD_LEFT);
}

if (isset($_POST['save_patient'])) {
    $id = $_POST['patientID'] ?? null;
    $name = $_POST['name'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $contact = $_POST['contactNumber'];
    $history = $_POST['medicalHistory'];

    if ($id) {
        // UPDATE
        $stmt = $conn->prepare("
            UPDATE patient 
            SET name=?, age=?, gender=?, contactNumber=?, medicalHistory=? 
            WHERE patientID=?
        ");
        $stmt->bind_param("sissss", $name, $age, $gender, $contact, $history, $id);
        $stmt->execute();
    } else {
        // INSERT
        $newID = generatePatientID($conn);

        $stmt = $conn->prepare("
            INSERT INTO patient (patientID, name, age, gender, contactNumber, medicalHistory)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssisss", $newID, $name, $age, $gender, $contact, $history);
        $stmt->execute();
    }

    header("Location: managePatient.php");
    exit;
}

/* ===================== DELETE PATIENT ===================== */
if (isset($_GET['delete_patient'])) {
    $stmt = $conn->prepare("DELETE FROM patient WHERE patientID=?");
    $stmt->bind_param("s", $_GET['delete_patient']);
    $stmt->execute();

    header("Location: managePatient.php");
    exit;
}

/* ===================== FETCH PATIENT DATA ===================== */
$patients = $conn->query("SELECT * FROM patient")->fetch_all(MYSQLI_ASSOC);
?>


<!DOCTYPE html>
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Staff Manage Appointments</title>
        <link rel="stylesheet" href="../css/patStyle.css"/>
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
            <a href="manageDoctor.php">Doctors</a>
            <a class="active" href="managePatient.php">Patients</a>
        </div>

        <!-- Patient Tab -->
        <div class="cardheader">
            <div class="card-header">
                <div>
                    <h3>Manage Patients</h3>
                    <p>Add, edit, or remove patient information</p>
                </div>
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Search patient">
                </div>
                <div class = "button_add_ptn">
                    <button onclick = "formAddPtn()">Add Patient</button>
                </div>
            </div>    
                
            <!-- Patient Card Popup -->
            <div class="staffcard" id="staffBookCard" style="display:none;">
                <form method="post" class="form-container">
                    <input type="hidden" name="patientID" id="patientID">

                    <button type="button" class="close-btn" onclick="formClosePtn()">&times;</button>

                    <h2 id="patientFormTitle">Add New Patient</h2>
                    <label id="patientFormSubtitle">Enter patient information</label><br><br>

                    <label>Full Name<br>
                        <input type="text" name="name" id="patientName" required>
                    </label><br><br>

                    <div class="row">
                        <label>Age<br>
                            <input type="number" name="age" id="patientAge" required>
                        </label>

                        <label>Gender<br>
                            <select name="gender" id="patientGender" required>
                                <option value="">Select</option>
                                <option value="Female">Female</option>
                                <option value="Male">Male</option>
                                <option value="Other">Other</option>
                            </select>
                        </label>
                    </div>

                    <label>Contact Number<br>
                        <input type="text" name="contactNumber" id="patientContact" required>
                    </label><br><br>

                    <label>Medical History<br>
                        <textarea name="medicalHistory" id="patientHistory" placeholder="Enter relevant medical history" required></textarea>
                    </label><br><br>

                    <div class="form-actions">
                        <button type="submit" name="save_patient">Save</button>
                        <button type="button" id="patientCancelBtn">Cancel</button>
                    </div>
                </form>
            </div>
            
            <!-- Patient Table View -->
            <table class="patient-table">
                <thead>
                <tr>
                    <th>Patient ID</th>
                    <th>Name</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>Contact</th>
                    <th>Medical History</th>
                    <th>Actions</th>
                </tr>
                </thead>

                <tbody>
                    <?php foreach ($patients as $p): ?>
                    <tr>
                        <td><?= $p['patientID'] ?></td>
                        <td><?= $p['name'] ?></td>
                        <td><?= $p['age'] ?></td>
                        <td><?= $p['gender'] ?></td>
                        <td><?= $p['contactNumber'] ?></td>
                        <td><?= $p['medicalHistory'] ?></td>

                        <td class="actions">
                            <button class="btn-edit"
                                onclick="openEditPtn(
                                    '<?= $p['patientID'] ?>',
                                    '<?= htmlspecialchars($p['name']) ?>',
                                    '<?= $p['age'] ?>',
                                    '<?= $p['gender'] ?>',
                                    '<?= htmlspecialchars($p['contactNumber']) ?>',
                                    '<?= htmlspecialchars($p['medicalHistory']) ?>'
                                )">
                                ✏️
                            </button>


                            <a href="managePatient.php?delete_patient=<?= $p['patientID'] ?>" 
                            class="btn-delete" onclick="return confirm('Delete this patient?')" title="Delete Patient">
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
            function formAddPtn() {
                document.getElementById("staffBookCard").style.display = "flex";

                document.getElementById("patientFormTitle").innerText = "Add New Patient";
                document.getElementById("patientFormSubtitle").innerText = "Enter patient information";

                document.getElementById("patientID").value = "";
                document.getElementById("patientName").value = "";
                document.getElementById("patientAge").value = "";
                document.getElementById("patientGender").value = "";
                document.getElementById("patientContact").value = "";
                document.getElementById("patientHistory").value = "";

                // Cancel button in Add mode = reset form
                document.getElementById("patientCancelBtn").onclick = function() {
                    document.querySelector('.form-container').reset();
                }
            }

            function openEditPtn(id, name, age, gender, contact, history) {
                document.getElementById("staffBookCard").style.display = "flex";

                document.getElementById("patientFormTitle").innerText = "Edit Patient";
                document.getElementById("patientFormSubtitle").innerText = "Update patient information";

                document.getElementById("patientID").value = id;
                document.getElementById("patientName").value = name;
                document.getElementById("patientAge").value = age;
                document.getElementById("patientGender").value = gender;
                document.getElementById("patientContact").value = contact;
                document.getElementById("patientHistory").value = history;

                // Cancel button in Edit mode = close modal
                document.getElementById("patientCancelBtn").onclick = formClosePtn;
            }

            function formClosePtn() {
                document.getElementById("staffBookCard").style.display = "none";
            }

            document.getElementById('searchInput').addEventListener('keyup', function () {
                const keyword = this.value.toLowerCase();
                const rows = document.querySelectorAll('.patient-table tbody tr');

                rows.forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(keyword) ? '' : 'none';
                });
            });

            /*document.querySelectorAll('.btn-delete').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    if(!confirm('Delete this patient?')) e.preventDefault();
                });
            });*/
        </script>
</html>