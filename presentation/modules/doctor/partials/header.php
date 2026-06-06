<?php
/**
 * Shared doctor dashboard header + navigation tabs.
 * Expects: $activeTab ('appointments' | 'prescriptions')
 */
$doctorHeaderId = getLinkedRefId($conn, 'doctor', $_SESSION['username'] ?? '') ?? null;
$doctorHeaderRecord = $doctorHeaderId ? (new DoctorModel($conn))->getById($doctorHeaderId) : null;
$doctorWelcomeName = htmlspecialchars(formatDoctorDisplayName(
    $doctorHeaderRecord['name'] ?? ($_SESSION['fullname'] ?? 'Doctor')
));
$activeTab = $activeTab ?? 'appointments';
?>
<div class="doc-header">
    <div>
        <h1>Sarawak General Hospital</h1>
        <p>Welcome, <?= $doctorWelcomeName ?></p>
    </div>
    <div class="doc-header-actions">
        <a href="../../../application/modules/doctor/schedule.php" class="btn-availability">
            <i class="fas fa-calendar-check"></i> Update Availability
        </a>
        <form method="post" action="../../../presentation/auth/logout.php">
            <button type="submit" class="btn-logout">Logout</button>
        </form>
    </div>
</div>

<nav class="doc-nav">
    <a href="dashboard.php" class="<?= $activeTab === 'appointments' ? 'active' : '' ?>">Appointments</a>
    <a href="prescriptions.php" class="<?= $activeTab === 'prescriptions' ? 'active' : '' ?>">Prescriptions</a>
</nav>
