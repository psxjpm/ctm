<?php
session_start();  
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

?>

<!-- https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/select-->
<!-- https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/input/date-->

<html>
    <head>
 <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<h2>Add Patient Details</h2>
<form action="add_test.php" method="post">
  <div class="imgcontainer">
   
  </div>

  <div class="container">
    <h3>Select Patient Type</h3>
    <label for="patients-select">Choose a patient type:</label>

    <select name="patient_type" id="patients-select">
        <option value="">--Please choose a patient type--</option>
        <option value="existing">Existing patient</option>
        <option value="new">New patient</option>
    </select>

    <h3>Patient Information</h3>

    <p>If adding a new patient, please fill all patient details below.</p>
    <p>If adding an existing patient, you can only fill the NHS Number but choose a date and a test.</p>

    <label for="pid"><b>NHS No</b></label>
    <input type="text" placeholder="Enter NHS No" name="pid" required>

    <label for="firstname"><b>Firstname</b></label>
    <input type="text" placeholder="Enter Firstname" name="firstname">

    <label for="lastname"><b>Lastname</b></label>
    <input type="text" placeholder="Enter Lastname" name="lastname">
    

    <h3>Test Information</h3>

    <label for="date"><b>Date</b></label>
    <input type="date" placeholder="Enter Date" name="date" required>

    <label for="testid"><b>Select Test</b></label>
    <select name="testid" required>
        <option value="">--Choose a test--</option>
        <option value="1">1 - Blood count</option>
        <option value="2">2 - Urinalysis</option>
        <option value="3">3 - CT scan</option>
        <option value="4">4 - Ultrasonography</option>
        <option value="5">5 - Colonoscopy</option>
        <option value="6">6 - Genetic testing</option>
        <option value="7">7 - Hematocrit</option>
        <option value="8">8 - Pap smear</option>
        <option value="9">9 - X-ray</option>
        <option value="10">10 - Biopsy</option>
        <option value="11">11 - Mammography</option>
        <option value="12">12 - Lumbar puncture</option>
        <option value="13">13 - thyroid function test</option>
        <option value="14">14 - prenatal testing</option>
        <option value="15">15 - electrocardiography</option>
        <option value="16">16 - skin test</option>
    </select>

    <button type="submit">Add</button>
    
  </div>
<?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    <div style="color: green; font-weight: bold; margin-bottom: 10px;">
        Action completed successfully!
    </div>
<?php endif; ?>

  <div class="container" style="background-color:#f1f1f1"> 
    <a href="<?php
        if ($_SESSION['role'] === 'doctor') {
            echo 'dashboard_dr.php';
        } elseif ($_SESSION['role'] === 'admin') {
            echo 'dashboard_admin.php';
        }
    ?>" class="cancelbtn">Return</a>
</div>
</form>
</body>
</html>
