<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //
    
    include '../db-connect.php';

    // Handling privilege change (if form is submitted)
    $privilegesArray = ['Administrator', 'Senior', 'Junior'];

    // Fetch the member details
    if (isset($_POST['member_id'])) {
        $memberId = $_POST['member_id'];

        // Get the current member data for further checks
        $sql = "SELECT Privilege FROM Members WHERE MemberID = :memberID";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':memberID', $memberId, PDO::PARAM_INT);
        $stmt->execute();
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if member exists
        if (!$member) {
            die("Member not found.");
        }
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_privilege'])) {
        
        // If the user is the last Administrator, do not allow their privilege to be downgraded
        if ($adminCount <= 1 && $member['Privilege'] === 'Administrator') {
            die("There must always be at least one Administrator.");
        }
    
        $newPrivilege = $_POST['new_privilege'];
        
        // Make sure the new privilege is valid
        if (in_array($newPrivilege, $privilegesArray)) {

            // Step 1: If the new privilege is 'Junior' (or another lower privilege), cancel or deny both pending and approved promotion requests
            if ($newPrivilege === 'Junior') {
                // Deny any pending promotion requests for this member
                $sql = "UPDATE PromotionRequests SET Status = 'denied' WHERE MemberID = :memberID AND Status = 'pending'";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':memberID', $memberId, PDO::PARAM_INT);
                $stmt->execute();

                // Deny any approved promotion requests for this member
                $sql = "UPDATE PromotionRequests SET Status = 'denied' WHERE MemberID = :memberID AND Status = 'approved'";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':memberID', $memberId, PDO::PARAM_INT);
                $stmt->execute();
            }

            // Step 2: Update the member's privilege in the database
            $updatePrivilegeSql = "UPDATE Members 
                                        SET Privilege = :privilege 
                                        WHERE MemberID = :memberID";
            $updateStmt = $pdo->prepare($updatePrivilegeSql);
            $updateStmt->bindParam(':privilege', $newPrivilege, PDO::PARAM_STR);
            $updateStmt->bindParam(':memberID', $_POST['member_id'], PDO::PARAM_INT);
            $updateStmt->execute();
        
            // Redirect with success message
            header("Location: ./display-members.php?message=Privilege updated successfully");
            exit();
        } else {
            die("Invalid privilege.");
        }
    }
?>