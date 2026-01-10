<?php
session_start();
session_destroy();
header("Location: http://localhost/SGHMS/auth/login.php");
exit();
