<?php
require("config.php");
require("audit_log.php");

// checks user is logged in before accessing page 
if(!isset($_SESSION["username"])) {
    header("Location: index.php");
    exit();
}

// ensures only administrators can access this page
if ($_SESSION["role"] !== "administrator") {
    header("Location: doctor_dashboard.php");
    exit();
}

// prepares message variable
$message = "";

// detect form submission, only runs when "Create Doctor" is pressed
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // retrieves values entered in the form and removes any spaces 
    $staffno = trim($_POST["staffno"]);
    $firstname = trim($_POST["firstname"]);
    $lastname = trim($_POST["lastname"]);
    $specialisation = trim($_POST["specialisation"]);
    $qualification = trim($_POST["qualification"]);
    $pay = trim($_POST["pay"]);
    $gender = trim($_POST["gender"]);
    $consultantstatus = trim($_POST["consultantstatus"]);
    $address = trim($_POST["address"]);

        // checks all fields are full, if not then displays an error
        if (empty($staffno) || empty($firstname) || empty($lastname) || empty($specialisation) || 
        empty($qualification) || empty($pay) || empty($gender) || empty($consultantstatus) || empty($address)) {
        $message = "<p class='error-msg'>Both fields are required</p>";
    } else {
        // checks staff number doesnt already exists, as they are unique
        $check = mysqli_query($conn, "SELECT * FROM doctor WHERE staffno = '$staffno'");
        if (mysqli_num_rows($check) > 0) {
            // shows error if the staff number already exists 
            $message = "<p class='error-msg'>A doctor already exists with that staff number</p>";
        } else {
            // if all fields are full and valid it inserts new doctor into database
            $sql = "INSERT INTO doctor 
            (staffno, firstname, lastname, specialisation, qualification, pay, gender, consultantstatus, address) 
            VALUES 
            ('$staffno', '$firstname', '$lastname', '$specialisation', '$qualification', '$pay', '$gender', '$consultantstatus', '$address')";
            // insertion is successful
            if (mysqli_query($conn, $sql)) {
                audit_log($conn, $_SESSION["username"], "CREATE_DOCTOR", "Created doctor $firstname $lastname (StaffNo $staffno)");
                $message = "<p class='success-msg'>Doctor added successfully</p>";
            // insertion fails
            } else {
                $message = "<p class='error-msg'>Error adding doctor</p>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h2>Create Doctor Records</h2>

<!--displays messages stored earlier-->
<?php echo $message; ?>

<form method="POST" action="create_doctor.php" style="width:45%; margin:auto;">
        <!--each <label>, <input> pair collects one field of data-->
        <label>Staff Number:</label>
        <input type="text" name="staffno" required>

        <label>First Name:</label>
        <input type="text" name="firstname" required>

        <label>Last Name:</label>
        <input type="text" name="lastname" required>

        <label>Specialisation:</label>
        <input type="text" name="specialisation" required>

        <label>Qualification:</label>
        <input type="text" name="qualification" required>

        <label>Pay:</label>
        <input type="text" name="pay" required>

        <!--creates dropdown, user sees male or female but the database receives 0 or 1 as required-->
        <label>Gender:</label>
        <select name="gender" required>
            <option value=""> </option>
            <option value="0">Male</option>
            <option value="1">Female</option>
        </select>

        <label>Consultant Status:</label>
        <input type="text" name="consultantstatus" required>

        <label>Address:</label>
        <input type="text" name="address" required>

        <button type="submit" class="blue-button">Create Doctor</button>
</form>

<br>
<a href="admin_dashboard.php" class="green-button" style="width:200px">Back to Dashboard</a>

</body>
</html>