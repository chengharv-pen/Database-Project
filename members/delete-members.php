<?php
    // Start session
    session_start();

    // Database connection
    $host = "localhost"; // Change if using a different host
    $dbname = "db-schema";
    $username = "root";
    $password = "";

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }

    // Check if delete request is made
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {

        // Ensure MemberID is set in session
        if (isset($_SESSION['MemberID'])) {
            $memberID = $_SESSION['MemberID'];

            try {
                // Delete the member from the database
                $stmt = $pdo->prepare("DELETE FROM Members WHERE MemberID = :memberID");
                $stmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    // If deletion is successful, destroy the session
                    session_destroy();
                    header("Location: goodbye.php"); // Redirect to a goodbye or confirmation page
                    exit;
                } else {
                    $error = "Failed to delete the account.";
                }
                
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        } else {
            $error = "No valid session found.";
        }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../styles.css" rel="stylesheet"/>
</head>
<body>

    <h1>Delete Member Account</h1>
    <h2 style="color: red;">WARNING: THIS ACTION WILL NOT BE REVERSIBLE. PLEASE PROCEED WITH CAUTION</h2>
        
    <!-- Display error if any -->
    <?php if (isset($error)): ?>
        <p class="feedback"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
        
    <!-- Form to trigger account deletion -->
    <form method="POST">
        <button type="submit" name="delete_account" class="delete-button">Delete My Account</button>
    </form>
        
    <br>
    <a href="./display-members.php">Display Your Profile?</a>
</body>
</html>