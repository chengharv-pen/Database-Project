<?php
    // Start session
    session_start();

    // Check if user is authorized
    if (!isset($_SESSION['MemberID']) || !isset($_SESSION['Privilege'])) {
        die("Access denied. Please log in.");
    }

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

    $member = null;
    $blockingStatus = false; // Default to not blocked
    $friendStatus = false; // Default to not friend

    // Handle search form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Get input from the form
        $usernameInput = $_POST['username'];

        // Query to find user by username
        $sql = "SELECT * 
                FROM Members 
                WHERE Username = :username";

        try {
            $statement = $pdo->prepare($sql);
            $statement->bindParam(':username', $usernameInput, PDO::PARAM_STR);
            $statement->execute();

            // Fetch member details
            $member = $statement->fetch(PDO::FETCH_ASSOC);

            if (!$member) {
                die("Searched Member not found.");
            }

            // Check if the user is already blocked
            $checkBlockSql = "SELECT 1 
            FROM BlockedMembers 
            WHERE BlockerID = :blocker AND BlockedID = :blocked";

            $blockStmt = $pdo->prepare($checkBlockSql);
            
            $blockStmt->execute([
                ':blocker' => $loggedInUserID,
                ':blocked' => $member['MemberID']
            ]);

            $blockingStatus = $blockStmt->fetchColumn();

            // Check if the user is already a friend
            $checkFriendSql = "SELECT 1
            FROM Relationships 
            WHERE MemberID1 = :senderMemberID AND MemberID2 = :receiverMemberID";

            $friendStmt = $pdo->prepare($checkFriendSql);

            $friendStmt->bindParam(':senderMemberID', $loggedInUserID, PDO::PARAM_INT);
            $friendStmt->bindParam(':senderMemberID', $member['MemberID'], PDO::PARAM_INT);

            $friendStatus = $friendStmt->fetchColumn();

        } catch (PDOException $e) {
            die("Query failed: " . $e->getMessage());
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../styles.css" rel="stylesheet"/>
</head>
<body>

    <h1>Searched Profile</h1>

    <!-- Display YOUR Profile -->
    <div class="profile">
        <h2><?php echo htmlspecialchars($member['Username']); ?></h2>
        <p>Last Login: <?php echo htmlspecialchars($member['LastLogin']); ?></p>
        <p>Join Date: <?php echo htmlspecialchars($member['DateJoined']); ?></p>

        <p>Status: <?php echo htmlspecialchars($member['Status']); ?></p>
        <p>Profession: <?php echo htmlspecialchars($member['Profession']); ?></p>
        <p>Region: <?php echo htmlspecialchars($member['Region']); ?></p>
        <p>Interests: <?php echo htmlspecialchars($member['Interests']); ?></p>

        <p><?php echo htmlspecialchars($member['PublicInformation']); ?></p>

        
        <p>Contact me: <?php echo htmlspecialchars($member['Email']); ?></p>

        <button> See Posts </button>

        <form action="../friends/request-friends.php" method="POST">
            <input type="hidden" name="member_id" value="<?php echo htmlspecialchars($member['MemberID']); ?>">

            <?php if ($friendStatus): ?>
                <!-- If added as Friend, then show this -->
                <button type="submit" name="action" value="remove_friend" class="friend-button"> Remove Friend </button> 
            <?php else: ?>
                <!-- If not added as Friend yet, then show this -->
                <button type="submit" name="action" value="add_friend" class="friend-button"> Add to Friends </button>
            <?php endif; ?>
        </form>
        
        <form action="./block-members.php" method="POST">
            <input type="hidden" name="member_id" value="<?php echo htmlspecialchars($member['MemberID']); ?>">
            <?php if ($blockingStatus): ?>
                <!-- Unblock button -->
                <button type="submit" name="action" value="unblock" class="unblock-button">
                    Unblock
                </button>
            <?php else: ?>
                <!-- Block button with optional reason -->
                <button type="submit" name="action" value="block" class="block-button">
                    Block
                </button>
                <textarea name="reason" placeholder="Reason for blocking (optional)" rows="2" cols="40"></textarea>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>