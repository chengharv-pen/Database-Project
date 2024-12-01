<?php
    // Start session
    session_start();

    // Check if user is authorized
    if (!isset($_SESSION['MemberID']) || !isset($_SESSION['Privilege'])) {
        die("Access denied. Please log in.");
    }

    // Database connection
    $host = "localhost";
    $dbname = "db-schema";
    $username = "root";
    $password = "";

    try {
        $pdo = new PDO("mysql:host=$host; dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }

    // Check if GroupID is provided
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['GroupID'])) {
        $groupID = intval($_POST['GroupID']);
        
        // Fetch group details to check if the group exists
        $stmt = $pdo->prepare("SELECT * FROM `Groups` WHERE GroupID = :groupID");
        $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
        $stmt->execute();
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if the group exists
        if (!$group) {
            die("Group not found.");
        }

        // Get the member ID from session
        $memberID = $_SESSION['MemberID'];

        // Check if the member is in the group before attempting to delete
        $stmt = $pdo->prepare("SELECT * FROM GroupMembers WHERE GroupID = :groupID AND MemberID = :memberID");
        $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
        $stmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);
        $stmt->execute();
        $membership = $stmt->fetch(PDO::FETCH_ASSOC);

        // If the member is not part of the group, show an error
        if (!$membership) {
            die("You are not a member of this group.");
        }

        // If Group Admin is being deleted
        if ($membership['Role'] === 'Admin') {
            // Check if there are other members in the group who can take over the admin role
            $stmt = $pdo->prepare("
                SELECT MemberID 
                FROM GroupMembers 
                WHERE GroupID = :groupID 
                AND Role IN ('Senior', 'Administrator') 
                LIMIT 1
            ");
            $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
            $stmt->execute();
            $potentialAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($potentialAdmin) {
                // Reassign admin role to the next eligible member
                $newAdminID = $potentialAdmin['MemberID'];
                $stmt = $pdo->prepare("
                    UPDATE GroupMembers 
                    SET Role = 'Admin' 
                    WHERE GroupID = :groupID 
                    AND MemberID = :newAdminID
                ");
                $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
                $stmt->bindParam(':newAdminID', $newAdminID, PDO::PARAM_INT);
                $stmt->execute();
            } else {
                // If no eligible members exist, delete the group
                $stmt = $pdo->prepare("DELETE FROM `Groups` WHERE GroupID = :groupID");
                $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
                $stmt->execute();
    
                // Also delete all related records in GroupMembers and GroupJoinRequests
                $stmt = $pdo->prepare("DELETE FROM GroupMembers WHERE GroupID = :groupID");
                $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
                $stmt->execute();
    
                $stmt = $pdo->prepare("DELETE FROM GroupJoinRequests WHERE GroupID = :groupID");
                $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
                $stmt->execute();
    
                // Redirect with a message
                header("Location: ./display-groups.php?message=Group deleted because no admin or eligible members remained.");
                exit;
            }
        }

        // Delete the member from the GroupMembers table
        $stmt = $pdo->prepare("DELETE FROM GroupMembers WHERE GroupID = :groupID AND MemberID = :memberID");
        $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
        $stmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);
        $stmt->execute();

        // Redirect to display-groups.php with a success message
        header("Location: ./display-groups.php?message=You have left the group!");
        exit;
    } else {
        die("Invalid request.");
    }
?>
