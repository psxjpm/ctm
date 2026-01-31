<?php
// reusable function to add records into the audit log table
// called by other pages to track actions 
function audit_log($conn, $username, $action, $details = null) {
    // prepare SQL statement to insert records into the audit log
    // question marks act as placeholders  
    $stmt = $conn->prepare("INSERT INTO audit_log (username, action, details) VALUES (?, ?, ?)");
    // stop the script if the statement fails to prepare
    if (!$stmt) {
        die("Audit log prepare failed: " . $conn->error);
    }
    // attach the real values to the placeholders 
    // "sss" represents sending 3 strings of text  
    $stmt->bind_param("sss", $username, $action, $details);
    // runs the SQL and writes the record into the database 
    $stmt->execute();
    // closes the statement 
    $stmt->close();
}
?>