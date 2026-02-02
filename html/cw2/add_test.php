<?php
session_start();

$servername = "mariadb";
$username_db = "root";
$password_db = "rootpwd";
$dbname = "hospital";

$conn = new mysqli("mariadb", "root", "rootpwd", "hospital");
if ($conn->connect_error) {
    die("Connection failed");
}

$patient_type = $_POST['patient_type'];

$pid = $_POST['pid'];  
$firstname = $_POST['firstname'];
$lastname = $_POST['lastname'];
$testid = $_POST['testid'];
$date = $_POST['date'];

// Add new patient 

if ($patient_type === 'new') {

    // temporary password for new patient because password NOT NULL and none default
    $temp_password = "QCM2025";

    // add new patient to patient table
    $sql_patient = "INSERT INTO patient (NHSno, firstname, lastname, password) VALUES (?, ?, ?, ?)";
    $stmt_patient = $conn->prepare($sql_patient);
    $stmt_patient->bind_param("ssss", $pid, $firstname, $lastname, $temp_password);
    if ($stmt_patient->execute()) {
    header("Location: add_test_form.php?status=success");
    exit();
} else {
    echo "Error: " . $stmt_patient->error; 
}


    

    // Add new patient to patientexamination
    //$sql_pe = "INSERT INTO patientexamination (patientid) VALUES (?)";
    //$stmt_pe = $conn->prepare($sql_pe);
    //$stmt_pe->bind_param("s", $pid);
    //$stmt_pe->execute();

    // Add new patient to wardpatientaddmission
    //$sql_wpa = "INSERT INTO wardpatientaddmission (pid) VALUES (?)";
    //$stmt_wpa = $conn->prepare($sql_wpa);
    //$stmt_wpa->bind_param("s", $pid);
    //$stmt_wpa->execute();
}



// Add existing patient 
$sql_ex= "INSERT INTO patient_test (pid, testid, date) VALUES (?, ?, ?)";
$stmt_ex = $conn->prepare($sql_ex);
$stmt_ex->bind_param("sis", $pid, $testid, $date);
if ($stmt_ex->execute()) {
    header("Location: add_test_form.php?status=success");
    exit();
} else {
    echo "Error: " . $stmt_ex->error; 
}

$conn->close();
?>