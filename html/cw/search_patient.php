<?php
session_start();
// Include the database connection
require_once 'config.php';
// Include the audit log function (required so we can call audit_log())
require_once 'audit_log.php';

// Security: Check if user is logged in as a doctor
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'doctor') {
    header("location: index.php");
    exit();
}

$search_results = [];
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $search = trim($_POST['search']);

    if (!empty($search)) {
        // Prepare statement to prevent SQL Injection
        // Searching by NHSno or First Name (matching your 'patient' table columns)
        $stmt = $conn->prepare("SELECT * FROM patient WHERE NHSno LIKE ? OR firstname LIKE ?");
        $search_term = "%" . $search . "%";
        $stmt->bind_param("ss", $search_term, $search_term);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $search_results[] = $row;
            }
        } else {
            $error = "No patients found matching '$search'.";
        }

        // AUDIT LOGGING
        // This matches the function signature seen in your error: 
        // audit_log($conn, $username, $action, $details)
        if (function_exists('audit_log')) {
            audit_log($conn, $_SESSION['username'], 'SEARCH_PATIENT', "Search keyword: $search");
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Search Patient</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f4f4f4; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Search Patient</h2>
        <a href="doctor_dashboard.php">Back to Dashboard</a>
        <br><br>

        <form method="post" action="">
            <input type="text" name="search" placeholder="Enter NHS Number or Name" required>
            <button type="submit">Search</button>
        </form>

        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if (!empty($search_results)): ?>
            <h3>Results:</h3>
            <table>
                <thead>
                    <tr>
                        <th>NHS Number</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Phone</th>
                        <th>Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($search_results as $patient): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($patient['NHSno']); ?></td>
                            <td><?php echo htmlspecialchars($patient['firstname']); ?></td>
                            <td><?php echo htmlspecialchars($patient['lastname']); ?></td>
                            <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                            <td><?php echo htmlspecialchars($patient['address']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>