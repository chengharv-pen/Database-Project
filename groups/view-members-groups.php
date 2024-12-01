<?php
    // Start session
    session_start();

    $memberID = $_SESSION['MemberID'];
    $privilege = $_SESSION['Privilege'];

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
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
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

        // Get all the MemberIDs in GroupMembers
        $stmt = $pdo->prepare("SELECT MemberID FROM GroupMembers WHERE GroupID = :groupID");
        $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
        $stmt->execute();
        $memberIDArray = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get the details of every Member in the group
        $stmt = $pdo->prepare("
            SELECT m.MemberID, m.Username, m.FirstName, m.LastName, gm.Role 
            FROM Members m
            JOIN GroupMembers gm ON m.MemberID = gm.MemberID
            WHERE gm.GroupID = :groupID
        ");
        $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
        $stmt->execute();
        $groupMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        die("Invalid request.");
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../styles.css" rel="stylesheet"/>
    <title>View Group Members</title>
</head>
<body>

    <h1>View Group Members</h1>

    <?php if ($privilege === 'Junior' || $privilege === 'Senior' || $privilege === 'Administrator'): ?>
        <!-- Display the Group's Name -->
        <h2>Group Name: <?php echo htmlspecialchars($group['GroupName']); ?></h2>

        <!-- Display Member Details -->
        <div class="member-details">
            <div class="member-details-head">
                <div class="head-item">Member ID</div>
                <div class="head-item">Username</div>
                <div class="head-item">First Name</div>
                <div class="head-item">Last Name</div>
                <div class="head-item">Role</div>
            </div>
            <?php foreach ($groupMembers as $member): ?>
                <div class="member-details-body">
                    <div class="body-item"><?php echo htmlspecialchars($member['MemberID']); ?></div>
                    <div class="body-item"><?php echo htmlspecialchars($member['Username']); ?></div>
                    <div class="body-item"><?php echo htmlspecialchars($member['FirstName']); ?></div>
                    <div class="body-item"><?php echo htmlspecialchars($member['LastName']); ?></div>
                    <div class="body-item"><?php echo htmlspecialchars($member['Role']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>You do not have permission to view this page.</p>
    <?php endif; ?>

</body>
</html>
