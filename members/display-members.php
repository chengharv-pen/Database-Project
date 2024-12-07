<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //
    
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

        // Calculate age
        $dob = new DateTime($member['DOB']);
        $today = new DateTime();
        $age = $today->diff($dob)->y; // Get the difference in years

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

        <p>Age: <?php echo $age; ?> years</p>
        <p>Status: <?php echo htmlspecialchars($member['Status']); ?></p>
        <p>Profession: <?php echo htmlspecialchars($member['Profession']); ?></p>
        <p>Region: <?php echo htmlspecialchars($member['Region']); ?></p>
        <p>Interests: <?php echo htmlspecialchars($member['Interests']); ?></p>

        <p>Your Bio (Public):</p>
        <p><?php echo htmlspecialchars($member['PublicInformation']); ?></p>

        <p>Your Bio (Private):</p>
        <p><?php echo htmlspecialchars($member['PrivateInformation']); ?></p>

        
        <p>Contact me: <?php echo htmlspecialchars($member['Email']); ?></p>

        <!-- 
            Form that lets a Junior Member request for a promotion to Senior Member 
            Only Administrator Members will be able to approve it
        -->
        <form action="./senior-promotion.php" method="POST">
            <?php
                // Check if the member has already sent a request
                $sql = "SELECT Status from PromotionRequests WHERE MemberID = :memberID";
                $statement = $pdo->prepare($sql);
                $statement->bindParam(':memberID', $memberID, PDO::PARAM_INT);
                $statement->execute();

                $status = $statement->fetch(PDO::FETCH_ASSOC);
                
            if ($privilege === 'Junior' && empty($status)): ?>
                <button type="submit" name="senior-promotion" class="senior-promotion">Request Senior Promotion</button>
            <?php elseif($privilege === 'Junior' && $status['Status'] === 'denied'): ?>
                <button type="submit" name="senior-promotion" class="senior-promotion">Request Senior Promotion</button>
            <?php elseif($privilege !== 'Junior'): ?>
                <!-- DISPLAY NOTHING -->
            <?php else: ?>
                <strong> Pending Senior Promotion Request... </strong>
            <?php endif; ?>
        </form>


        <br>
        <br>
        <a href="./edit-members.php">Edit Display Information?</a>
        <br>
        <br>
        <a href="./delete-members.php">Want to delete your account?</a>
        <br>
        <br>
        <a href="./filtered-search-members.php">Filtered Search?</a>
    </div>    
</body>
</html>