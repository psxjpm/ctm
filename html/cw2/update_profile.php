<?php
session_start();


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
if ($_SESSION['role'] === 'admin') {
    $current_name = $_SESSION['username']; // Admin uses 'username'
} else {
    $current_name = $_SESSION['firstname']; // Doctor uses 'firstname'
}

$sql_update_adm = "UPDATE adminlogin SET username = ?, password = ? WHERE username = ?";
$stmt_update_adm = $conn->prepare($sql_update_adm);
$stmt_update_adm->bind_param("sss", $username, $password, $current_name);

$sql_update_dr = "UPDATE doctor SET firstname = ?, password_doctor = ? WHERE firstname = ?";
$stmt_update_dr = $conn->prepare($sql_update_dr);
$stmt_update_dr->bind_param("sss", $username, $password, $current_name);

if ($_SESSION['role'] === 'admin') {
    if ($stmt_update_adm->execute()) {
        $_SESSION['username'] = $username;
        header("Location: profile.php?status=success");
        exit;
    } else {
        echo "Error updating record: " . $conn->error;
    }
} elseif ($_SESSION['role'] === 'doctor') {
    if ($stmt_update_dr->execute()) {
        $_SESSION['firstname'] = $username;
        header("Location: profile.php?status=success");
        exit;
    } else {
        echo "Error updating record: " . $conn->error;
    }
}

$conn->close();
?>