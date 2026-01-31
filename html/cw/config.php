<?php
// where the database is hosted
$servername="mariadb";
$username= "root";
$password= "rootpwd";
$dbname= "Hospital";

// creates the database connection
// $conn beocmes the link between PHP and the database 
$conn = mysqli_connect($servername, $username, $password, $dbname);

// checks if database connection failed
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// ensures a session is active
// login data is stored in $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();    
}