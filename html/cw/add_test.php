<?php
// loads config file, connects to database and starts the session
require("config.php");
require("audit_log.php");

// checks if user is logged in before proceeding
// prevents somone accesssing the page by just typing in the URL
if (!isset($_SESSION["username"])) {
    header("Location: index.php");
    exit();
}

// creates a variable to store messages shown on the page (e.g. error or success messages)
$message = "";

// checks if the page was submitted using the form, means the code inside the block will only run after clicking "Add Test"
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // retrieves testname typed in by user and uses 'trim' to remove any unwanted spaces
    $testname = trim($_POST["testname"]);

    // checks the test name field was not left empty
    if (!empty($testname)) {
        // creates a query to insert a new row into the test table and adds it to the database
        $sql = "INSERT INTO test (testname) VALUES ('$testname')";
        $result = mysqli_query($conn, $sql);

        // query worked - success message
        if ($result) {
            audit_log($conn, $_SESSION["username"], "ADD_TEST", "Added test: $testname");
            $message = "<p class='success-msg'>Test successfully added</p>";
        // query failed - error message
        } else {
            $message = "<p class='error-msg'>Error adding test</p>";
        } 
    // if testname field was empty
    } else {
       $message = "<p class='error-msg'>Test name is required</p>"; 
    }
}
?>
<!--HTML section = the page that the user sees-->
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!--page title-->
<h2>Add New Medical Test</h2>

<!--shows messages generated earlier-->
<?php echo $message; ?>

<!--the form itself where user enters testname-->
<!--page reloads uwing POST when "Add Test" is clicked, this triggers the PHP code at the top to insert the test into the database-->
<form method="POST" action="add_test.php">
        <label>Test Name:</label>
        <input type="text" name="testname" required>

        <button type="submit" class="blue-button" style="width:300px;">Add Test</button>
</form>

<br>
<!--used to return to dashboard, either admin or doctor depending on role-->
<a href="<?php echo ($_SESSION['role'] == 'administrator') ? 'admin_dashboard.php' : 'doctor_dashboard.php'; ?>" class="green-button" style="width:200px;">Back to Dashboard</a>

</body>
</html>