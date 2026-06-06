<?php
require_once __DIR__ . '/../../includes/init.php';
requireRole('staff');
header('Location: manageUsers.php?tab=patient');
exit;
