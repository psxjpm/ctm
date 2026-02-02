<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}
?>
 
<html>
<head>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

<h2>Create Doctor Account</h2>

<form action="create_doctor.php" method="post">

  <div class="container">

    <h3>Doctor Information</h3>

    <label for="staffno"><b>Staff Number</b></label>
    <input type="text" placeholder="Enter Staff Number" name="staffno" required>

    <label for="firstname"><b>Firstname</b></label>
    <input type="text" placeholder="Enter Firstname" name="firstname" required>

    <label for="lastname"><b>Lastname</b></label>
    <input type="text" placeholder="Enter Lastname" name="lastname" required>

    <label for="qualification"><b>Qualification</b></label>
    <input type="text" placeholder="Enter qualification" name="qualification" required>


    <label for="pay"><b>Salary</b></label>
    <input type="text" placeholder="Enter pay" name="pay" required>

    <label for="address"><b>Address</b></label>
    <input type="text" placeholder="Enter address" name="address" required>

    <!--admin can create temporary password that doctor can change later from their own profile-->
    <label for="password"><b>Temporary Password</b></label>
    <input type="password" placeholder="Enter Password" name="password_doctor" required>

    <label for="gender"><b>Select gender</b></label>
    <select name="gender" required>
        <option value="">--Choose a gender--</option>
        <option value="1">1 - Female</option>
        <option value="0">2 - Male</option> 
    </select>


    <label for="wardid"><b>Select Ward</b></label>
    <select name="wardid" required>
        <option value="">--Choose a specialisation--</option>
        <option value="1">1 - Dermatology</option>
        <option value="2">2 - Urology</option>
        <option value="3">3 - Orthopaedics</option>
        <option value="4">4 - Accident and emergency</option>
        <option value="5">5 - Cardiology</option>
       <!-- https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/select -->
    </select>

    <button type="submit">Create Doctor</button>

  </div>
<?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    <div style="color: green; font-weight: bold; margin-bottom: 10px;">
        Action completed successfully!
    </div>
<?php endif; ?>
  <div class="container" style="background-color:#f1f1f1">
    <a href="dashboard_admin.php" class="cancelbtn">Return</a>
  </div>

</form>


</body>
</html>
