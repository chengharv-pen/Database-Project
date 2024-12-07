<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //
    
    include '../db-connect.php'; 

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

                // Remove any existing relationships (this could be automated using a trigger, but this works)
                $deleteRelationshipsSql = "DELETE FROM Relationships 
                                            WHERE (SenderMemberID = :blocker AND ReceiverMemberID = :blocked)
                                               OR (SenderMemberID = :blocked AND ReceiverMemberID = :blocker)";
                
                $deleteRelationshipsStmt = $pdo->prepare($deleteRelationshipsSql);
                $deleteRelationshipsStmt->execute([
                    ':blocker' => $loggedInUserID,
                    ':blocked' => $blockedID
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
            
            header("Location: ./display-members.php");
            exit;
        } catch (PDOException $e) {
            die("Action failed: " . $e->getMessage());
        }
    }    
?>