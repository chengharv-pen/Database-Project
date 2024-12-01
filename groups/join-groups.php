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
        $status = 'Pending'; // Default status for join request

        // Insert the join request into the GroupJoinRequests table
        $stmt = $pdo->prepare("INSERT INTO GroupJoinRequests (GroupID, MemberID, Status) VALUES (:groupID, :memberID, :status)");
        $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
        $stmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->execute();

        // Redirect to display-groups.php with a success message
        header("Location: ./display-groups.php?message=A join request has been sent!");
        exit;
    } else {
        die("Invalid request.");
    }
?>