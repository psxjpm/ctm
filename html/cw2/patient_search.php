<?php
session_start();  
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

?>

<html>
    <head>
 <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<!--Form with 2 fields: NHSno and firstname-->
<!--Form action should be  a lookup patients function-->
<!--Lookup patients function will select patient from database-->
<!-- Search where equal for nhsno and name-->
<!--If found, display patient details-->
<!-- If not found, display not found message-->


<h2> Patient Search Form</h2>
<form action="patient_details.php" method="post">
  <div class="imgcontainer">
   
  </div>

  <div class="container">
    <label for="search_term"><b>NHSno or, Firstname, or lastname</b></label>
    <input type="text" placeholder="Enter NHSno or Firstname or Lastname" name="search_term" required>

    <button type="submit">Search</button>
  </div>

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






