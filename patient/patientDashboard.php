<?php
session_start();
include '../db/dbcon.php';

// --- DISABLED LOGIN CHECK FOR TESTING ---
// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit;
// }

// Use Session ID if available, otherwise use '1' for testing
$patient_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$patient_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Test Patient';

// --- 1. HANDLE ADD / UPDATE APPOINTMENT (UC-4: Manage Appointment Booking) ---
if (isset($_POST['save_appointment'])) {
    $doctor_id = $_POST['doctor'];
    $app_date = $_POST['date'];
    $app_time = $_POST['time'];
    $reason = $_POST['reason'];
    $app_id = $_POST['appointment_id'];

    if (empty($app_id)) {
        // INSERT (Book new appointment)
        $stmt = $conn->prepare("INSERT INTO appointment (appointmentDate, appointmentTime, reason, status, patientID, doctorID) VALUES (?, ?, ?, 'Pending', ?, ?)");
        $stmt->bind_param("sssii", $app_date, $app_time, $reason, $patient_id, $doctor_id);
        $action = "booked";
    } else {
        // UPDATE (Reschedule/Edit appointment)
        $stmt = $conn->prepare("UPDATE appointment SET doctorID=?, appointmentDate=?, appointmentTime=?, reason=? WHERE appointmentID=? AND patientID=?");
        $stmt->bind_param("isssii", $doctor_id, $app_date, $app_time, $reason, $app_id, $patient_id);
        $action = "updated";
    }

    if ($stmt->execute()) {
        $status_message = "success";
        $success_text = "Appointment $action successfully!";
    } else {
        $status_message = "error";
        $error_text = $stmt->error;
    }
    $stmt->close();
}

// --- 2. HANDLE DELETE (Cancel Appointment) ---
if (isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM appointment WHERE appointmentID = ? AND patientID = ?");
    $stmt->bind_param("ii", $delete_id, $patient_id);

    if ($stmt->execute()) {
        $status_message = "success";
        $success_text = "Appointment deleted successfully!";
    } else {
        $status_message = "error";
        $error_text = $stmt->error;
    }
    $stmt->close();
}

// --- 3. FETCH DATA ---
$doctors = $conn->query("SELECT * FROM doctor");
$timeSlots = ['08:00 AM', '09:00 AM', '10:00 AM', '11:00 AM', '12:00 PM', '01:00 PM', '02:00 PM', '03:00 PM', '04:00 PM', '05:00 PM'];

$myAppointments = $conn->query("
    SELECT a.*, d.name as doc_name, d.specialization 
    FROM appointment a 
    JOIN doctor d ON a.doctorID = d.doctorID 
    WHERE a.patientID = '$patient_id'
    ORDER BY a.appointmentDate DESC, a.appointmentTime ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Patient Dashboard</title>
    <link rel="stylesheet" href="../css/patient.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* --- CARD & DASHBOARD STYLES --- */
        .appointment-container {
            display: flex;
            flex-direction: column; 
            gap: 20px;
            padding: 20px;
        }

        /* The New Card Design */
        .app-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border: 1px solid #eee;
            width: 100%;
            box-sizing: border-box;
            position: relative;
        }

        .card-header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
        }
        
        .doc-name { font-size: 1.1rem; font-weight: bold; color: #000; }
        .doc-spec { color: #888; font-size: 0.9rem; margin-top: 2px; }

        .status-badge {
            background: #4e73df;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .card-details-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .card-details-grid { grid-template-columns: 1fr 1fr; }
        }

        .detail-group { display: flex; flex-direction: column; }
        .detail-label { font-size: 0.85rem; color: #666; margin-bottom: 5px; }
        .detail-value { font-size: 1rem; color: #333; font-weight: 500; }

        .reason-section {
            border-top: 1px solid #f0f0f0;
            padding-top: 15px;
            margin-bottom: 15px;
        }

        .card-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .btn-edit { background-color: #f6c23e; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; }
        .btn-delete { background-color: #e74a3b; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; }
    </style>
</head>

<body>
    <div class="header" style="display: flex; justify-content: space-between; align-items: center; padding: 20px; background-color: #f4f4f4; border-bottom: 1px solid #ddd;">
        <div>
            <h1 style="margin: 0; font-size: 24px;">Sarawak General Hospital</h1>
            <p style="margin: 5px 0 0 0; color: #666;">
                Welcome, 
                <strong><?= htmlspecialchars($patient_name); ?></strong>
            </p> 
        </div>
        <form method="post" action="logout.php">
            <button type="submit" class="btn-cancel">Logout</button>
        </form>
    </div>

    <div class="button_add_apt" style="justify-content: space-between; align-items: center;">
        <h2 style="margin: 0; font-size: 1.5rem; color: #333;">Patient Dashboard</h2>
        <button onclick="openModal()">
            <i class="fas fa-plus"></i> Book Appointment
        </button>
    </div>

    <div class="appointment-container">
        <?php if ($myAppointments && $myAppointments->num_rows > 0): ?>
            <?php while($row = $myAppointments->fetch_assoc()): ?>
                <div class="app-card">
                    
                    <div class="card-header-row">
                        <div>
                            <div class="doc-name">Dr. <?= htmlspecialchars($row['doc_name']); ?></div>
                            <div class="doc-spec"><?= htmlspecialchars($row['specialization']); ?></div>
                        </div>
                        <span class="status-badge" 
                              style="background-color: <?= ($row['status'] == 'Pending') ? '#f6c23e' : '#4e73df'; ?>">
                            <?= htmlspecialchars($row['status']); ?>
                        </span>
                    </div>

                    <div class="card-details-grid">
                        <div class="detail-group">
                            <span class="detail-label">Appointment ID</span>
                            <span class="detail-value">APT<?= str_pad($row['appointmentID'], 3, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <div class="detail-group">
                            <span class="detail-label">Patient Name</span>
                            <span class="detail-value"><?= htmlspecialchars($patient_name); ?></span>
                        </div>
                        <div class="detail-group">
                            <span class="detail-label">Date</span>
                            <span class="detail-value"><?= date('d/m/Y', strtotime($row['appointmentDate'])); ?></span>
                        </div>
                        <div class="detail-group">
                            <span class="detail-label">Time</span>
                            <span class="detail-value"><?= date('H:i', strtotime($row['appointmentTime'])); ?></span>
                        </div>
                    </div>

                    <div class="reason-section">
                        <div class="detail-group">
                            <span class="detail-label">Reason</span>
                            <span class="detail-value"><?= htmlspecialchars($row['reason']); ?></span>
                        </div>
                    </div>

                    <?php if($row['status'] == 'Pending'): ?>
                        <div class="card-actions">
                            <button class="btn-edit" onclick="editAppointment(
                                '<?= $row['appointmentID']; ?>',
                                '<?= $row['doctorID']; ?>',
                                '<?= $row['appointmentDate']; ?>',
                                '<?= date('h:i A', strtotime($row['appointmentTime'])); ?>',
                                '<?= addslashes($row['reason']); ?>'
                            )">Edit</button>

                            <form id="deleteForm_<?= $row['appointmentID']; ?>" method="post" style="display:none;">
                                <input type="hidden" name="delete_id" value="<?= $row['appointmentID']; ?>">
                            </form>
                            <button class="btn-delete" onclick="confirmDelete('<?= $row['appointmentID']; ?>')">Delete</button>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="padding: 20px;">No appointments booked yet.</p>
        <?php endif; ?>
    </div>

    <div id="bookingModal" class="modal-overlay">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <form method="post">
                <h2 id="modalTitle">Book New Appointment</h2>
                <input type="hidden" name="appointment_id" id="appointment_id">
                
                <div class="form-group">
                    <label>Select Doctor</label>
                    <select name="doctor" id="doctor" required>
                        <option value="">Choose a doctor</option>
                        <?php if(isset($doctors)): ?>
                            <?php foreach ($doctors as $doc): ?>
                                <option value="<?= $doc['doctorID']; ?>"><?= htmlspecialchars($doc['name'] . " - " . $doc['specialization']); ?></option> 
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date" id="date" min="<?= date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Time</label>
                    <select name="time" id="time" required>
                        <?php foreach ($timeSlots as $time): ?>
                            <option value="<?= $time; ?>"><?= $time; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Reason</label>
                    <textarea name="reason" id="reason" placeholder="Details..." required></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="save_appointment" class="btn-submit" id="submitBtn">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        var modal = document.getElementById("bookingModal");
        function openModal() {
            document.getElementById("modalTitle").innerText = "Book New Appointment";
            document.getElementById("submitBtn").innerText = "Book Appointment";
            document.getElementById("appointment_id").value = ""; 
            document.getElementById("doctor").value = "";
            document.getElementById("date").value = "";
            document.getElementById("time").value = "";
            document.getElementById("reason").value = "";
            modal.style.display = "block";
        }
        function editAppointment(id, docID, date, time, reason) {
            document.getElementById("modalTitle").innerText = "Edit Appointment";
            document.getElementById("submitBtn").innerText = "Update";
            document.getElementById("appointment_id").value = id;
            document.getElementById("doctor").value = docID;
            document.getElementById("date").value = date;
            document.getElementById("time").value = time;
            document.getElementById("reason").value = reason;
            modal.style.display = "block";
        }
        function confirmDelete(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "Delete this appointment?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('deleteForm_' + id).submit();
                }
            })
        }
        function closeModal() { modal.style.display = "none"; }
        window.onclick = function(event) { if (event.target == modal) { modal.style.display = "none"; } }
    </script>

    <?php if (isset($status_message)): ?>
    <script>
        Swal.fire({
            title: '<?= ($status_message == "success") ? "Success!" : "Error!"; ?>',
            text: '<?= ($status_message == "success") ? $success_text : addslashes($error_text); ?>',
            icon: '<?= $status_message; ?>',
            confirmButtonColor: '#4CAF50'
        }).then(() => { window.location.href = 'patientDashboard.php'; });
    </script>
    <?php endif; ?>
</body>
</html>