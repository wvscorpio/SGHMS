<?php
include '../db/dbcon.php';
function getDoctorTimeSlots($conn, $doctorID, $date){
    $dayOfWeek = date('l', strtotime($date));
    $stmt = $conn->prepare("SELECT startTime, endTime FROM doctor_schedule WHERE doctorID=? AND dayOfWeek=?");
    $stmt->bind_param("ss", $doctorID, $dayOfWeek);
    $stmt->execute();
    $result = $stmt->get_result();
    $slots = [];
    while($row = $result->fetch_assoc()){
        $start = strtotime($row['startTime']);
        $end = strtotime($row['endTime']);
        while($start < $end){
            $slots[] = date('H:i', $start);
            $start = strtotime('+30 minutes', $start);
        }
    }
    $stmt2 = $conn->prepare("SELECT appointmentTime FROM appointment WHERE doctorID=? AND appointmentDate=?");
    $stmt2->bind_param("ss", $doctorID, $date);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    $booked = [];
    while($r = $result2->fetch_assoc()){
        $booked[] = date('H:i', strtotime($r['appointmentTime']));
    }
    echo json_encode(array_values(array_diff($slots, $booked)));
}
if(isset($_POST['doctorID'], $_POST['date'])){
    getDoctorTimeSlots($conn, $_POST['doctorID'], $_POST['date']);
}
?>
