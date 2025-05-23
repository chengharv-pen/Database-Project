<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //
    
    include '../db-connect.php';

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
    <link href="../styles.css?<?php echo time(); ?>" rel="stylesheet"/>
</head>
<body>

    <h1>Delete Member Account</h1>

    <p> ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ </p>
    
    <h2 style="color: red;">WARNING: THIS ACTION WILL NOT BE REVERSIBLE. PLEASE PROCEED WITH CAUTION</h2>
        
    <!-- Display error if any -->
    <?php if (isset($error)): ?>
        <p class="feedback"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
        
    <!-- Form to trigger account deletion -->
    <form method="POST">
        <button type="submit" name="delete_account" class="delete-button">Delete My Account</button>
    </form>
        
    <p> ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ </p>

    <a href="./display-members.php">Display Your Profile?</a>

    <script>
        // Add confirmation popup for the delete button
        document.querySelector('.delete-button').addEventListener('click', (event) => {
            const confirmed = confirm("Are you sure you want to delete your account? This action cannot be undone.");
            if (!confirmed) {
                // Prevent form submission if the user cancels
                event.preventDefault();
            }
        });
    </script>
</body>
</html>