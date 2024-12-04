<?php
    include '../db-connect.php';

    // Handling privilege change (if form is submitted)
    $privilegesArray = ['Administrator', 'Senior', 'Junior'];

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_privilege'])) {
        
        // If the user is the last Administrator, do not allow their privilege to be downgraded
        if ($adminCount <= 1 && $member['Privilege'] === 'Administrator') {
            die("There must always be at least one Administrator.");
        }
    
        $newPrivilege = $_POST['new_privilege'];
        
        // Make sure the new privilege is valid
        if (in_array($newPrivilege, $privilegesArray)) {
            // Update the member's privilege in the database
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