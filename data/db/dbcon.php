<?php
$servername="localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "re_sghms"; 

$conn = new mysqli($servername, $username, $password, $dbname); 
if($conn->connect_error){ 
    die("Connection failed: ". $conn->connect_error); 
}

$conn->query("ALTER TABLE appointment ADD COLUMN IF NOT EXISTS diagnosedDate DATE NULL DEFAULT NULL");
$conn->query("ALTER TABLE prescription ADD COLUMN IF NOT EXISTS appointmentID VARCHAR(10) NULL DEFAULT NULL AFTER doctorID");
?>