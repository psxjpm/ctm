<?php
// reference: https://www.voxfor.com/how-to-build-a-admin-dashboard-with-php-bootstrap/
// https://www.w3schools.com/html/default.asp
// https://www.w3schools.com/php/default.asp
// references:https://stackoverflow.com/questions/13533296/check-php-session-issetsession-is-not-working
// https://www.geeksforgeeks.org/php/creating-a-registration-and-login-system-with-php-and-mysql/
//
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Administrator Dashboard</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; }
        .container { width: 500px; margin: 100px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 0 10px #aaa; }
        a { display: block; margin: 10px 0; text-decoration: none; color: #2a6df4; font-weight: bold; }
        button { padding: 10px; width: 100%; margin-top: 10px; background: #2a6df4; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Welcome <?php echo $username; ?>!</h2>

        <a href="profile.php">Update Profile / Change Password</a>
        <a href="patient_search.php">Patient Search</a>
        <a href="add_test_form.php">Add Test</a>
        <a href="request_parking_form.php">Request Parking Permit</a>
        <a href="create_doctor_form.php">Create New Doctor Account</a>
        <a href="approve_parking_form.php">Approve / Reject Parking Requests</a>
                
        <form method="POST" action="logout.php">
            <button type="submit">Logout</button>
        </form>
    </div>
</body>
</html>
