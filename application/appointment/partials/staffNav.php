<?php
/** @var string $activeTab appointments|prescriptions|users */
$activeTab = $activeTab ?? 'appointments';
$userSubTab = $userSubTab ?? 'doctor';
?>
<div class="header">
    <div>
        <h1>Sarawak General Hospital</h1>
        <p>Welcome, <?= htmlspecialchars($_SESSION['fullname'] ?? 'Staff'); ?></p>
    </div>
    <form method="post" action="../../presentation/auth/logout.php">
        <button type="submit" class="btn-logout">Logout</button>
    </form>
</div>

<nav class="topnav">
    <a class="<?= $activeTab === 'appointments' ? 'active' : '' ?>" href="manageAppointment.php">Appointments</a>
    <a class="<?= $activeTab === 'prescriptions' ? 'active' : '' ?>" href="managePrescription.php">Prescriptions</a>
    <a class="<?= $activeTab === 'users' ? 'active' : '' ?>" href="manageUsers.php?tab=doctor">User Management</a>
</nav>

<?php if ($activeTab === 'users'): ?>
<nav class="staff-subnav staff-subnav-wide">
    <a class="<?= $userSubTab === 'doctor' ? 'active' : '' ?>" href="manageUsers.php?tab=doctor">Doctors</a>
    <a class="<?= $userSubTab === 'patient' ? 'active' : '' ?>" href="manageUsers.php?tab=patient">Patients</a>
</nav>
<?php endif; ?>
