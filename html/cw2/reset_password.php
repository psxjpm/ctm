<?php
session_start();

$conn = new mysqli("mariadb", "root", "rootpwd", "hospital");
if ($conn->connect_error) {
    die("Connection failed");
}
// https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/select
// https://www.geeksforgeeks.org/php/creating-a-registration-and-login-system-with-php-and-mysql/
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $role = $_POST['role'];
    $identifier = $_POST['identifier'];
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $message = "Passwords do not match";
    } else {

        if ($role === "admin") {
            $sql = "UPDATE adminlogin SET password = ? WHERE username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $password, $identifier);
        }

        if ($role === "doctor") {
            $sql = "UPDATE doctor SET password_doctor = ? WHERE firstname = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $password, $identifier);
        }

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "Password reset successfully";
        } else {
            $message = "User not found";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

<h2>Reset Password</h2>

<form method="post">

    <div class="container">

        <label><b>Username / Firstname</b></label>
        <input type="text" name="identifier" required>

        <label><b>New Password</b></label>
        <input type="password" name="password" required>

        <label><b>Confirm Password</b></label>
        <input type="password" name="confirm_password" required>

        <label><b>User Type</b></label>
        <select name="role" required>
            <option value="">-- Select --</option>
            <option value="admin">Administrator</option>
            <option value="doctor">Doctor</option>
        </select>

        <button type="submit">Reset Password</button>

        <?php if ($message != ""): ?>
            <p style="color:red;"><?php echo $message; ?></p>
        <?php endif; ?>

    </div>

    <div class="container" style="background-color:#f1f1f1">
        <a href="index.php" class="cancelbtn">Back to Login</a>
    </div>

</form>

</body>
</html>
