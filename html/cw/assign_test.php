<?php
// loads database connection and starts session
require("config.php");
// loads audit logging fucntion
require("audit_log.php");

// ensures user is logged in before accessing page 
if (!isset($_SESSION["username"])) {
    header("Location: index.php");
    exit();
}

// stores messages to display
$message = "";

// retrieves list of patients and tests from the database
$patientQuery = mysqli_query($conn, "SELECT nhsno, firstname, lastname FROM patient ORDER BY lastname");
$testQuery = mysqli_query($conn, "SELECT testid, testname FROM test ORDER BY testname");
$doctorQuery = mysqli_query($conn, "SELECT staffno, firstname, lastname FROM doctor ORDER BY lastname");

// only runs after user clicks "Assign Test"
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // reads selected patient and selected test from dropdowns
    $testid = $_POST["testid"];
    $doctorid = $_POST["doctorid"];
    // check if user wants to create new patient to assign test to if theyre not already in database
    $create_patient = isset($_POST["create_patient"]) && $_POST["create_patient"] == "1";
    // stores NHS number of patient 
    $nhsno = "";

    // if new patient is being created
    if ($create_patient) {
        // collects new patient details 
        $new_nhsno = trim($_POST["new_nhsno"]     ?? "");
        $new_firstname = trim($_POST["new_firstname"] ?? "");
        $new_lastname = trim($_POST["new_lastname"]  ?? "");
        $new_phone = trim($_POST["new_phone"]           ?? "");
        $new_address = trim($_POST["new_address"]         ?? "");
        $new_age = trim($_POST["new_age"]             ?? "");
        $new_gender = isset($_POST["new_gender"]) && $_POST["new_gender"] !== "" 
                        ? (int)$_POST["new_gender"] 
                        : null;
        $new_emergencyphone = trim($_POST["new_emergencyphone"]  ?? "");
        // ensures all fields are filled in
        if (
            $new_nhsno === "" ||
            $new_firstname === "" ||
            $new_lastname === "" ||
            $new_phone === "" ||
            $new_address === "" ||
            $new_age === "" ||
            $new_gender === null ||
            $new_emergencyphone === ""
        ) {
            $message = "<p class='error-msg'>Please fill in all new patient fields.</p>";
        } else {
            // check if exists
            $check = mysqli_prepare($conn, "SELECT nhsno FROM patient WHERE nhsno = ?");
            mysqli_stmt_bind_param($check, "s", $new_nhsno);
            mysqli_stmt_execute($check);
            mysqli_stmt_store_result($check);
            
            // inserts patient if they do not already exist
            if (mysqli_stmt_num_rows($check) == 0) {
                // insert new patient
                $insert = mysqli_prepare($conn,
                    "INSERT INTO patient (nhsno, firstname, lastname, phone, address, age, gender, emergencyphone)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                mysqli_stmt_bind_param(
                  $insert,
                "sssssiis", 
                $new_nhsno,
                $new_firstname,
                $new_lastname,
                $new_phone,
                $new_address,
                $new_age,
                $new_gender,
                $new_emergencyphone  
                );

                // logs action if insert successful
                if (mysqli_stmt_execute($insert)) {
                    audit_log($conn, $_SESSION["username"], "CREATE_PATIENT",
                        "Created patient $new_nhsno ($new_firstname $new_lastname)");

                    $nhsno = $new_nhsno; // use new patient
                } else {
                    $message = "<p class='error-msg'>Error creating new patient.</p>";
                }
                mysqli_stmt_close($insert);
            } else {
                $nhsno = $new_nhsno; // exists already
            }

            mysqli_stmt_close($check);
        }
    } else {
        // if user selected is an existing patient
        $nhsno = trim($_POST["nhsno"] ?? "");
    }

    $date = date("Y-m-d");
    $report = NULL;

    // ensures both fields are selected
     if (!empty($nhsno) && !empty($testid) && !empty($doctorid)) {
        // creates a link between the selected patient and selected test
        $sql = "INSERT INTO patient_test (pid, testid, date, report, doctorid) VALUES ('$nhsno', '$testid', '$date', '$report', '$doctorid')";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            audit_log($conn, $_SESSION["username"], "ASSIGN_TEST", "Assigned test $testid to patient $nhsno by doctor $doctorid");
            $message = "<p class='success-msg'>Test successfully assigned to patient</p>";
        } else {
            $message = "<p class='error-msg'>Error assigning test</p>";
        }
    } else {
        $message = "<p class='error-msg'>All fields are required</p>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h2>Assign Medical Test to Patient</h2>

<!--displays success or error message-->
<?php echo $message; ?>

<!--when user clicks "Assign Test" the page reloads and the PHP code at the top runs-->
<form method="POST" action="assign_test.php">

        <!--patient dropdown menu-->
        <label>Select Patient:</label>
        <select name="nhsno" id="nhsno">
            <option value="">-- Choose Patient --</option>
            <?php while ($row = mysqli_fetch_assoc($patientQuery)) { ?>
                <!--creates one <option> for each unique patient from the database-->
                <option value="<?php echo $row['nhsno']; ?>">
                    <!--displays the first name, last name, and NHS number-->
                    <!--stores the NHS number as the value-->
                    <?php echo $row['firstname'] . " " . $row['lastname'] . " (NHS No.: " . $row['nhsno'] . ")"; ?>
                </option>
            <?php } ?>
        </select>
        <hr>

        <!--create new patient fields-->
        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
            <input type="checkbox" id="create_new" name="create_patient" value="1" style="width:auto; margin:0;">
            <span>Create new patient</span>
        </label>

        <div id="new_patient_fields">
            <label>NHS Number:</label>
            <input type="text" name="new_nhsno">

            <label>First Name:</label>
            <input type="text" name="new_firstname">

            <label>Last Name:</label>
            <input type="text" name="new_lastname">

            <label>Phone:</label>
            <input type="text" name="new_phone">

            <label>Address:</label>
            <input type="text" name="new_address">

            <label>Age:</label>
            <input type="number" name="new_age" min="0">

            <label>Gender:</label>
            <select name="new_gender">
                <option value="">-- Select Gender --</option>
                <option value="0">Male</option>
                <option value="1">Female</option>
            </select>

            <label>Emergency Phone:</label>
            <input type="text" name="new_emergencyphone">
        </div>

        <script>
            const box = document.getElementById("create_new");
            const fields = document.getElementById("new_patient_fields");
            const nhsSel = document.getElementById("nhsno");

            function toggle() {
                if (box.checked) {
                    fields.style.display = "block"; // shows new patient fields 
                    nhsSel.disabled = true; // disable existing patient dropdown
                    nhsSel.value = ""; // clear any previous selection
                } else {
                    fields.style.display = "none"; // hides new patient fields 
                    nhsSel.disabled = false; // enable existing patient dropdown
                }
            }
            box.addEventListener("change", toggle);
            toggle(); // run when page loads 
        </script>
        <hr>

        <!--select patient test-->
        <label>Select Test:</label>
        <select name="testid" required>
            <option value="">-- Choose Test --</option>
            <?php while ($row = mysqli_fetch_assoc($testQuery)) { ?>
                <!--creates one <option> for each unique test-->
                <option value="<?php echo $row['testid']; ?>">
                    <!--displayes the test name-->
                    <!--stores the test ID as the value-->
                    <?php echo $row['testname']; ?>
                </option>
            <?php } ?>
        </select>

        <!--assign doctor to test-->
        <label>Select Doctor for Test:</label>
        <select name="doctorid" required>
            <option value="">-- Choose Doctor --</option>
            <?php while ($row = mysqli_fetch_assoc($doctorQuery)) { ?>
                <!--creates one <option> for each unique doctor from the database-->
                <option value="<?php echo $row['staffno']; ?>">
                    <!--displays the first name, last name, and staff number-->
                    <!--stores the staff number as the value-->
                    <?php echo $row['firstname'] . " " . $row['lastname'] . " (Staff No.: " . $row['staffno'] . ")"; ?>
                </option>
            <?php } ?>
        </select>

        <button type="submit" class="blue-button">Assign Test</button>
</form>

<br>
<a href="<?php echo ($_SESSION['role'] == 'administrator') ? 'admin_dashboard.php' : 'doctor_dashboard.php'; ?>" class="green-button" style="width:200px;">Back to Dashboard</a>

</body>
</html>