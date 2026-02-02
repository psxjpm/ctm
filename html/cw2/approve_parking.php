<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

$conn = new mysqli("mariadb", "root", "rootpwd", "hospital");
if ($conn->connect_error) {
    die("Connection failed");
}

$parkingid = $_POST['parkingid'];


$sql = "UPDATE parkingpermit SET status = 'approved' WHERE parkingid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $parkingid);

if ($stmt->execute()) {
    header("Location: approve_parking_form.php?status=success");
    exit();
} else {
    echo "Error: " . $stmt->error; 
}
$conn->close();
exit;
?>
