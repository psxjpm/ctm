<?php
session_start();
// https://www.geeksforgeeks.org/php/get-and-post-in-php/


$servername = "mariadb";
$username_db = "root";
$password_db = "rootpwd";
$dbname = "hospital";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$username = $_POST['username'];
$password = $_POST['password'];


$sql_admin = "SELECT * FROM adminlogin WHERE username = ? AND password = ?";
$stmt_admin = $conn->prepare($sql_admin);
$stmt_admin->bind_param("ss", $username, $password);
$stmt_admin->execute();
$result_admin = $stmt_admin->get_result();

if ($result_admin->num_rows > 0) {
    $admin_data = $result_admin->fetch_assoc(); 
    $_SESSION['staffno'] = $admin_data['staffno'];
    $_SESSION['loggedin'] = true;
    $_SESSION['role'] = "admin";
    $_SESSION['username'] = $username;
    header("Location: dashboard_admin.php");
    exit();
}

$sql_doctor = "SELECT * FROM doctor WHERE firstname = ? AND password_doctor = ?";
$stmt_doc = $conn->prepare($sql_doctor);
$stmt_doc->bind_param("ss", $username, $password);
$stmt_doc->execute();
$result_doc = $stmt_doc->get_result();

if ($result_doc->num_rows > 0) {
    $doctor_data = $result_doc->fetch_assoc(); 
    $_SESSION['staffno'] = $doctor_data['staffno'];
    $_SESSION['loggedin'] = true;
    $_SESSION['role'] = "doctor";
    $_SESSION['firstname'] = $username;
    header("Location: dashboard_dr.php");
    exit();
}


$conn->close();
?>
