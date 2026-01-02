<?php
session_start();
include '../db/dbcon.php'; 
?>

<!DOCTYPE html>
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Doctor</title>
        <link rel="stylesheet" type="text/css" href="../css/style.css"/>
    </head>

    <body>
        <div class = "header">
            <div>
                <h1>Sarawak General Hospital</h1>
                <p>Welcome,  Dr</p>
            </div>
            <form method = "post" action = "logout.php">
                <button type = "Submit">Logout</button>
            </form>
        </div>

        <div class="topnav">
            <a class="active" href="doctorAppointment.php">Appointments</a>
            <a href="#doctorSchedule">Schedule</a>
            <a href="#doctorProfile">Profile</a>
        </div>

        <!-- Appointment Tab -->

        </div>
    </body>
</html>