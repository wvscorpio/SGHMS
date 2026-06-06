<?php
require_once __DIR__ . '/../../includes/init.php';
requireRole('staff');

$appointmentController = new AppointmentController($conn);
$appointmentModel = new AppointmentModel($conn);
$patientModel = new PatientModel($conn);
$doctorModel = new DoctorModel($conn);

if (isset($_POST['appointmentID'], $_POST['status'])) {
    $result = $appointmentController->updateStatus($_POST['appointmentID'], $_POST['status']);
    echo ($result['status'] === 'Success') ? 'success' : 'invalid_status';
    exit;
}

$appointments = $appointmentModel->getAllWithDetails();
$patients = $patientModel->getAll();
$doctors = $doctorModel->getAll();

if (isset($_POST['create_appointment'])) {
    if (empty($_POST['patient']) || empty($_POST['doctor']) || empty($_POST['date']) || empty($_POST['time'])) {
        $_SESSION['popup'] = 'Please fill in all required fields';
        header('Location: manageAppointment.php');
        exit;
    }

    if ($_POST['date'] < date('Y-m-d')) {
        $_SESSION['popup'] = 'Invalid date selected';
        header('Location: manageAppointment.php');
        exit;
    }

    $result = $appointmentController->submitBooking([
        'patientID' => $_POST['patient'],
        'doctorID' => $_POST['doctor'],
        'date' => $_POST['date'],
        'time' => $_POST['time'],
        'reason' => $_POST['reason'],
    ]);

    $_SESSION['popup'] = ($result['status'] === 'Success')
        ? 'Appointment created successfully'
        : ($result['message'] ?? 'Failed to create appointment');
    header('Location: manageAppointment.php');
    exit;
}

if (isset($_GET['id'])) {
    $appointmentModel->delete($_GET['id']);
    $_SESSION['popup'] = 'Appointment deleted successfully';
    header('Location: manageAppointment.php');
    exit;
}

function staffStatusClass(string $status): string
{
    return strtolower($status);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Staff Dashboard - Manage Appointments | SGHMS</title>
    <?php include __DIR__ . '/partials/staffHead.php'; ?>
</head>
<body class="staff-dashboard">

<?php if (isset($_SESSION['popup'])): ?>
    <div class="toast"><?= htmlspecialchars($_SESSION['popup']); ?></div>
    <?php unset($_SESSION['popup']); ?>
<?php endif; ?>

<?php $activeTab = 'appointments'; include __DIR__ . '/partials/staffNav.php'; ?>

<div class="staff-content">
    <section class="cardheader">
        <div class="card-header">
            <div class="card-header-text">
                <h3>Manage Appointments</h3>
                <p>View and manage all hospital appointments</p>
            </div>
            <div class="card-header-tools">
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Search appointment">
                </div>
                <button type="button" class="btn-new-apt" onclick="formNewApt()">
                    <i class="fas fa-plus"></i> New Appointment
                </button>
            </div>
        </div>

        <div class="table-wrap">
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
                    <?php if (count($appointments) > 0): ?>
                        <?php foreach ($appointments as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['appointmentID']) ?></td>
                            <td><?= htmlspecialchars($a['patientName']) ?></td>
                            <td><?= htmlspecialchars($a['doctorName']) ?></td>
                            <td><?= htmlspecialchars($a['appointmentDate']) ?></td>
                            <td><?= date('h:i A', strtotime($a['appointmentTime'])) ?></td>
                            <td>
                                <span class="badge <?= staffStatusClass($a['status']) ?>">
                                    <?= htmlspecialchars($a['status']) ?>
                                </span>
                            </td>
                            <td class="actions">
                                <select class="status-select" data-id="<?= htmlspecialchars($a['appointmentID']) ?>">
                                    <?php foreach (['Pending', 'Confirmed', 'Completed', 'Cancelled'] as $st): ?>
                                    <option value="<?= $st ?>" <?= $a['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <a href="manageAppointment.php?id=<?= urlencode($a['appointmentID']) ?>"
                                   class="btn-delete" title="Delete appointment"
                                   onclick="return confirm('Delete this appointment?')">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M3 6h18" stroke="white" stroke-width="2"/>
                                        <path d="M8 6V4h8v2" stroke="white" stroke-width="2"/>
                                        <rect x="6" y="6" width="12" height="14" rx="2" stroke="white" stroke-width="2"/>
                                        <path d="M10 11v6M14 11v6" stroke="white" stroke-width="2"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="empty-cell">No appointments found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<!-- New Appointment Modal -->
<div class="staffcard" id="staffBookCard">
    <form method="post" class="form-container">
        <button type="button" onclick="formCloseNewApt()" class="close-btn" aria-label="Close">&times;</button>
        <h2>Create Appointment</h2>
        <p class="form-subtitle">Schedule a new appointment</p>

        <label>Patient</label>
        <select name="patient" required>
            <option value="">Select patient</option>
            <?php foreach ($patients as $pat): ?>
                <option value="<?= $pat['patientID'] ?>"><?= htmlspecialchars($pat['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Doctor</label>
        <select name="doctor" id="doctorSelect" required>
            <option value="">Select doctor</option>
            <?php foreach ($doctors as $doc): ?>
                <option value="<?= $doc['doctorID'] ?>">
                    <?= htmlspecialchars($doc['name'] . ' - ' . $doc['specialization']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="row">
            <label>Date
                <input type="date" name="date" id="aptDate" min="<?= date('Y-m-d') ?>" required>
            </label>
            <label>Time
                <select name="time" id="timeSelect" required>
                    <option value="">Choose a time slot</option>
                </select>
            </label>
        </div>

        <label>Reason</label>
        <textarea name="reason" placeholder="Reason for appointment" required></textarea>

        <div class="form-actions">
            <button type="submit" name="create_appointment">Create Appointment</button>
            <button type="button" onclick="formCloseNewApt()">Cancel</button>
        </div>
    </form>
</div>

<script>
function formNewApt() {
    document.getElementById('staffBookCard').style.display = 'flex';
}
function formCloseNewApt() {
    document.getElementById('staffBookCard').style.display = 'none';
}

const doctorSelect = document.getElementById('doctorSelect');
const dateSelect = document.getElementById('aptDate');
const timeSelect = document.getElementById('timeSelect');

function loadTimeSlots() {
    const doctorID = doctorSelect.value;
    const date = dateSelect.value;
    if (!doctorID || !date) return;
    fetch('getTimeSlots.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `doctorID=${doctorID}&date=${date}`
    })
    .then(res => res.json())
    .then(slots => {
        timeSelect.innerHTML = '<option value="">Choose a time slot</option>';
        slots.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s;
            opt.textContent = s;
            timeSelect.appendChild(opt);
        });
    });
}
doctorSelect.addEventListener('change', loadTimeSlots);
dateSelect.addEventListener('change', loadTimeSlots);

document.querySelectorAll('.status-select').forEach(select => {
    select.addEventListener('change', function () {
        fetch('manageAppointment.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `appointmentID=${this.dataset.id}&status=${this.value}`
        })
        .then(res => res.text())
        .then(response => {
            if (response === 'success') {
                const badge = this.closest('tr').querySelector('.badge');
                badge.className = 'badge ' + this.value.toLowerCase();
                badge.textContent = this.value;
            } else {
                alert('Failed to update status');
            }
        });
    });
});

document.getElementById('searchInput').addEventListener('keyup', function () {
    const keyword = this.value.toLowerCase();
    document.querySelectorAll('.appointment-table tbody tr').forEach(row => {
        if (row.querySelector('.empty-cell')) return;
        row.style.display = row.innerText.toLowerCase().includes(keyword) ? '' : 'none';
    });
});
</script>
</body>
</html>
