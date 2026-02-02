<html>
    <head>
 <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<h2>Login Form</h2>
<form action="action.php" method="post">
  <div class="imgcontainer">
   
  </div>

  <div class="container">
    <label for="username"><b>Username / Firstname</b></label>
    <input type="text" placeholder="Enter Username or Firstname" name="username" required>

    <label for="password"><b>Password</b></label>
    <input type="password" placeholder="Enter Password" name="password" required>
        
    <button type="submit">Login</button>
    <label>
      <input type="checkbox" checked="checked" name="remember"> Remember me
    </label>
  </div>

  <div class="container" style="background-color:#f1f1f1">
    <span class="psw">Forgot <a href="reset_password.php">password?</a></span>
  </div>
</form>
</body>
</html>

<!-- inspired by: https://www.geeksforgeeks.org/php/creating-a-registration-and-login-system-with-php-and-mysql/ -->