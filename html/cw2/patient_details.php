<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

// https://www.w3schools.com/html/html_tables.asp
// https://www.w3schools.com/php/php_mysql_select.asp
// https://www.php.net/manual/en/mysqli.prepare.php
// https://developer.mozilla.org/en-US/docs/Learn_web_development/Core/Structuring_content/HTML_table_basics


$servername = "mariadb";
$username_db = "root";
$password_db = "rootpwd";
$dbname = "hospital";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$search = $_POST['search_term'];
$like = "%$search%";

// extract patints details in all relevant tables
$sql_search = "SELECT
    p.NHSno,
    p.firstname,
    p.lastname,
    p.phone,
    p.address,
    p.age,
    p.gender,
    p.emergencyphone,

    w.wardname,
    wpa.date AS admission_date,
    wpa.time AS admission_time,
    wpa.status AS admission_status,

    t.testname,
    pt.date AS test_date,
    pt.report AS test_report,

    pe.date AS exam_date,
    pe.time AS exam_time

FROM patient p

LEFT JOIN wardpatientaddmission wpa ON p.NHSno = wpa.pid

LEFT JOIN ward w ON wpa.wardid = w.wardid

LEFT JOIN patient_test pt ON p.NHSno = pt.pid

LEFT JOIN test t ON pt.testid = t.testid

LEFT JOIN patientexamination pe ON p.NHSno = pe.patientid

WHERE p.NHSno = ? OR p.firstname LIKE ? OR p.lastname LIKE ?";



$stmt_search = $conn->prepare($sql_search);
$stmt_search->bind_param("sss", $search, $like, $like);
$stmt_search->execute();
$result_search = $stmt_search->get_result();

if ($result_search->num_rows > 0) {
    // table with patient info
    echo "<h2>Patient Details</h2>";
    echo "<table class='patients-details'>";
    echo "<tr>
            <th>NHSno</th>
            <th>Firstname</th>
            <th>Lastname</th>
            <th>Phone</th>
            <th>Address</th>
            <th>Age</th>
            <th>Gender</th>
            <th>Emergency Phone</th>
            <th>Ward</th>
            <th>Admission Date</th>
            <th>Admission Time</th>
            <th>Admission Status</th>
            <th>Test Name</th>
            <th>Test Date</th>
            <th>Test Report</th>
            <th>Exam Date</th>
            <th>Exam Time</th>
          </tr>";


    while ($row = $result_search->fetch_assoc()) {
        echo "<tr>
                <td>{$row['NHSno']}</td>
                <td>{$row['firstname']}</td>
                <td>{$row['lastname']}</td>
                <td>{$row['phone']}</td>
                <td>{$row['address']}</td>
                <td>{$row['age']}</td>
                <td>{$row['gender']}</td>
                <td>{$row['emergencyphone']}</td>
                <td>" . ($row['wardname'] ?? 'No ward') . "</td>
                <td>" . ($row['admission_date'] ?? '-') . "</td>
                <td>" . ($row['admission_time'] ?? '-') . "</td>
                <td>" . ($row['admission_status'] ?? '-') . "</td>
                <td>" . ($row['testname'] ?? 'No test') . "</td>
                <td>" . ($row['test_date'] ?? '-') . "</td>
                <td>" . ($row['test_report'] ?? '-') . "</td>
                <td>" . ($row['exam_date'] ?? '-') . "</td>
                <td>" . ($row['exam_time'] ?? '-') . "</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p>Patient not found in the system.</p>";
}

$conn->close();
?>
