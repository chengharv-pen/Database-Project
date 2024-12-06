<?php
    session_start();

    // Check if the user is logged in
    if (isset($_SESSION['MemberID'])) {
        // Database connection
        $host = "npc353.encs.concordia.ca"; // Change if using a different host
        $dbname = "npc353_2";
        $username = "npc353_2";
        $password = "WrestFrugallyErrant43";

        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Update the user's status to 'Inactive' before logging them out
            $updateStatusSql = "UPDATE Members SET Status = 'Inactive' WHERE MemberID = :memberID";
            $updateStatusStmt = $pdo->prepare($updateStatusSql);
            $updateStatusStmt->bindParam(':memberID', $_SESSION['MemberID'], PDO::PARAM_INT);
            $updateStatusStmt->execute();

        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }

        // Destroy the session to log the user out
        session_unset(); 
        session_destroy();
    }

    // Redirect to the homepage or login page
    header('Location: ../index.php');
    exit;
?>
