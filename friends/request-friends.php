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

    // Ensure the POST request contains the MemberID of the friend
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_friend'])) {
        if (!isset($_POST['friend_member_id']) || empty($_POST['friend_member_id'])) {
            die("Invalid request. Friend MemberID is required.");
        }

        // Sanitize input
        $friendMemberID = intval($_POST['friend_member_id']);
        $currentMemberID = $_SESSION['MemberID'];

        if ($friendMemberID === $currentMemberID) {
            die("You cannot send a friend request to yourself.");
        }

        try {
            // Check if a relationship already exists
            $stmt = $pdo->prepare("SELECT * FROM Relationships 
                                WHERE SenderMemberID = :currentMember AND ReceiverMemberID = :friendMember;");
            $stmt->execute([
                ':currentMember' => $currentMemberID,
                ':friendMember' => $friendMemberID
            ]);

            if ($stmt->rowCount() > 0) {
                die("A relationship already exists or is pending.");
            }

            // Insert a new relationship with 'Pending' status
            $stmt = $pdo->prepare("INSERT INTO Relationships (SenderMemberID, ReceiverMemberID, RelationshipType, CreationDate, Status) 
                                   VALUES (:currentMember, :friendMember, 'Friend', CURDATE(), 'Pending')");
            $stmt->execute([
                ':currentMember' => $currentMemberID,
                ':friendMember' => $friendMemberID
            ]);

            header('Location: ../members/search-members.php?requestID=' . $friendMemberID . 'message=Friend request sent!');
        } catch (PDOException $e) {
            die("An error occurred: " . $e->getMessage());
        }
    } elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_friend'])) {
        if (!isset($_POST['friend_member_id']) || empty($_POST['friend_member_id'])) {
            die("Invalid request. Friend MemberID is required.");
        }

        // Sanitize input
        $friendMemberID = intval($_POST['friend_member_id']);
        $currentMemberID = $_SESSION['MemberID'];

        if ($friendMemberID === $currentMemberID) {
            die("You cannot send a friend request to yourself.");
        }

        try {
            // Check if a relationship already exists
            $stmt = $pdo->prepare("SELECT * FROM Relationships 
                                WHERE SenderMemberID = :currentMember AND ReceiverMemberID = :friendMember;");

            $stmt->execute([
                ':currentMember' => $currentMemberID,
                ':friendMember' => $friendMemberID
            ]);

            if ($stmt->rowCount() === 0) {
                die("A relationship does not exist.");
            }

            // Delete the existing relationship
            $stmt = $pdo->prepare("DELETE FROM Relationships
                                    WHERE SenderMemberID = :currentMember AND ReceiverMemberID = :friendMember;");

            $stmt->execute([
                ':currentMember' => $currentMemberID,
                ':friendMember' => $friendMemberID
            ]);

            header('Location: ../members/search-members.php?requestID=' . $friendMemberID . 'message=Removed friend!');
        } catch (PDOException $e) {
            die("An error occurred: " . $e->getMessage());
        }
    } else {
        die("Invalid request method.");
    }
?>
