<?php
    include '../db-connect.php';  

    // Get MemberID from session
    $memberID = $_SESSION['MemberID'];

    // Query to fetch member details
    $sql = "SELECT *
            FROM Members 
            WHERE MemberID = :memberID";

    try {
        $statement = $pdo->prepare($sql);
        $statement->bindParam(':memberID', $memberID, PDO::PARAM_INT);
        $statement->execute();

        // Fetch member details
        $member = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$member) {
            die("Member not found.");
        }

    } catch (PDOException $e) {
        die("Query failed: " . $e->getMessage());
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../styles.css?<?php echo time(); ?>" rel="stylesheet"/>
</head>
<body>

    <div class=container-2-horizontal>
        <div class="display-title">
            <h1>Your Profile</h1>
        </div>
        
        <div class="display-search-bar">
            <!-- Search for a Profile by Username Input -->
            <form action="./search-members.php" method="POST">
                <input type="text" id="username" name="username" placeholder="Search by Username..." class="search-username" required>
                <button type="submit" name="search-button" class="search-button">Search</button>
            </form>
        </div>
    </div>

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

        <button> See Your Posts </button>

        </br>
        </br>
        <a href="./edit-members.php">Edit Display Information?</a>
        </br>
        <a href="./delete-members.php">Want to delete your account?</a>
    </div>
    
</body>
</html>