<?php
session_start();
include '../db/dbcon.php';
?>

<!DOCTYPE html>
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Patient Book Appointments</title>
        <link rel="stylesheet" href="../css/patient.css">
    </head>

    <body>
        <div class = "header">
            <div>
                <h1>Sarawak General Hospital</h1>
                <p>Welcome, </p> 
            </div>
            <form method = "post" action = "logout.php">
                <button type = "Submit">Logout</button>
            </form>
        </div>

        <div class = "button_add_apt">
            <button onclick = "formBookApt()">Book Appointment</button>
        </div>

        <!-- Booking Form -->
        <div class="card" id="bookCard" style="display:none;">
            <form method = "post">
                <h2>Book New Appointment</h2>
                <label>Fill in the details to book your <appointment>
                </label><br><br>

                <label>Patient Name<br>
                    <input type = "text" name = "pname" required>
                </label><br><br>

                <label>Select Doctor</label><br>
                    <select name = "doctor" required>
                        <option value = "">Choose a doctor</option>
                        <?php foreach ($doctors as $doc): ?>
                            <option value = "<?= $doc['doctorID']; ?>">
                                <?= htmlspecialchars($doc['name'] . " - " . $doc['specialization']); ?>
                            </option> 
                        <?php endforeach; ?>
                    </select>
                </label> <br><br>

                <label>Appointment Date</label><br>
                    <input type="date" name="date" min="<?= date('Y-m-d'); ?>" required>
                </label><br><br>

                <label>Apartment Time</label><br>
                    <select name="time" required>
                        <option value="">Choose a time slot</option>
                        <?php foreach ($timeSlots as $time): ?>
                            <option value="<?= $time; ?>"><?= $time; ?></option>
                        <?php endforeach; ?>
                    </select>
                </label><br><br>

                <label>Reason for Visit<br>
                    <textarea name="reason" placeholder="Describe your reason for the appointment" required></textarea>
                </label><br><br>

                <button type="submit" name="book_appointment">Book Appointment</button>
                <button type="reset" name="cancel_appointment">Cancel</button>
            </form>
        </div>
    </body>

      <script>
            function formBookApt() {
                var card = document.getElementById("bookCard");
                    if (card.style.display === "none") {
                        card.style.display = "block"; // Show the card
                    } else {
                        card.style.display = "none"; // Hide the card if already visible (toggle)
                    }
                }   
        </script>
</html>