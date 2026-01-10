<?php
$servername="localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "re_sghms"; 

$conn = new mysqli($servername, $username, $password, $dbname,3307); 
if($conn->connect_error){ 
    die("Connection failed: ". $conn->connect_error); 
} 
//echo "Connected Successfully"; 
?>