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

        </br>
        </br>
        <a href="./edit-members.php">Edit Display Information?</a>
        </br>
        <a href="./delete-members.php">Want to delete your account?</a>
    </div>

    <div class="display-search-bar">
        <h3> Filtered Search </h3>
        <!-- Search for a Profile by Filters -->
        <form action="./filtered-search-members.php" method="POST">
            <!-- Interest Filter -->
            <label for="interest">Interest:</label>
            <input type="text" id="interest" name="interest" placeholder="Enter interest">
                
            <!-- Age Range Filter -->
            <label for="min-age">Min Age (years):</label>
            <input type="number" id="min-age" name="min_age" min="0" placeholder="Min Age"><br><br>
                
            <label for="max-age">Max Age (years):</label>
            <input type="number" id="max-age" name="max_age" min="0" placeholder="Max Age"><br><br>
                
            <!-- Profession Filter -->
            <label for="profession">Profession:</label>
            <input type="text" id="profession" name="profession" placeholder="Enter profession">
                
            <!-- Region Filter -->
            <label for="region">Region:</label>
            <input type="text" id="region" name="region" placeholder="Enter region">
                
            <!-- Submit Button -->
            <button type="submit" name="search-button" class="search-button">Search by Filters</button>
        </form>
    </div>
    
</body>
</html>