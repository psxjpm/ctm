<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$conn = new mysqli("mariadb", "root", "rootpwd", "hospital");

if ($conn->connect_error) {
    die("Connection failed");
}

$staffno   = $_POST['staffno'];
$firstname = $_POST['firstname'];
$lastname  = $_POST['lastname'];
$password_doctor  = $_POST['password_doctor'];
$specialisation    = $_POST['wardid'];
$pay = $_POST['pay'];
$address = $_POST['address'];
$consultantstatus = 1;
$qualification = $_POST['qualification'];
$gender = $_POST['gender'];

$sql = "INSERT INTO doctor (staffno, firstname, lastname, password_doctor, specialisation, pay, address, consultantstatus,qualification,gender)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssiisisi", $staffno, $firstname, $lastname, $password_doctor, $specialisation, $pay, $address, $consultantstatus, $qualification, $gender);

if ($stmt->execute()) {
    header("Location: create_doctor_form.php?status=success");
    exit();
} else {
    echo "Error: " . $stmt->error; 
}
$conn->close();
exit;
// https://www.w3schools.com/php/php_sessions.asp
// https://stackoverflow.com/questions/13533296/check-php-session-issetsession-is-not-working
// https://www.w3schools.com/php/php_mysql_prepared_statements.asp
?>
