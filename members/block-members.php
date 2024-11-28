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

    // Placeholder for logged-in user's ID
    $loggedInUserID = $_SESSION['MemberID']; // Assuming session holds logged-in user's MemberID

    // Handle block/unblock actions
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
        $blockedID = $_POST['member_id'];

        try {
            if ($_POST['action'] === 'block') {

                // Insert into BlockedMembers
                $blockSql = "INSERT INTO BlockedMembers (BlockerID, BlockedID, Reason) 
                            VALUES (:blocker, :blocked, :reason)";
                
                $blockStmt = $pdo->prepare($blockSql);
                
                $blockStmt->execute([
                    ':blocker' => $loggedInUserID,
                    ':blocked' => $blockedID,
                    ':reason' => $_POST['reason'] ?? null
                ]);

            } elseif ($_POST['action'] === 'unblock') {
                // Remove from BlockedMembers
                $unblockSql = "DELETE FROM BlockedMembers 
                            WHERE BlockerID = :blocker AND BlockedID = :blocked";

                $unblockStmt = $pdo->prepare($unblockSql);
                
                $unblockStmt->execute([
                    ':blocker' => $loggedInUserID,
                    ':blocked' => $blockedID
                ]);

            }
            
            header("Location: ../display-members.php");
            exit;
        } catch (PDOException $e) {
            die("Action failed: " . $e->getMessage());
        }
    }    
?>