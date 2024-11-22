<?php
    // Start session
    session_start();
    print_r($_SESSION);
    print($_SESSION['MemberID']);

    // Check if MemberID is set in session
    if (!isset($_SESSION['MemberID'])) {
        die("Member not logged in.");
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

    // Get MemberID from session
    $memberID = $_SESSION['MemberID'];

    // Query to fetch member details
    $sql = "SELECT 
                MemberID, Username, FirstName, LastName, Email, Address, DOB, DateJoined, 
                Privilege, Status, Profession, Region, Interests, LastLogin 
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
    <link href="../styles.css" rel="stylesheet"/>
</head>
<body>

    <h1>Members (Display)</h1>

    <div class="profile">
        <div>Username: <?php echo htmlspecialchars($member['Username']); ?></div>
        <div>First Name: <?php echo htmlspecialchars($member['FirstName']); ?></div>
        <div>Last Name: <?php echo htmlspecialchars($member['LastName']); ?></div>
        <div>Email: <?php echo htmlspecialchars($member['Email']); ?></div>
        <div>Address: <?php echo htmlspecialchars($member['Address']); ?></div>
        <div>Date of Birth: <?php echo htmlspecialchars($member['DOB']); ?></div>
        <div>Date Joined: <?php echo htmlspecialchars($member['DateJoined']); ?></div>
        <div>Privilege: <?php echo htmlspecialchars($member['Privilege']); ?></div>
        <div>Status: <?php echo htmlspecialchars($member['Status']); ?></div>
        <div>Profession: <?php echo htmlspecialchars($member['Profession']); ?></div>
        <div>Region: <?php echo htmlspecialchars($member['Region']); ?></div>
        <div>Interests: <?php echo htmlspecialchars($member['Interests']); ?></div>
        <div>Last Login: <?php echo htmlspecialchars($member['LastLogin']); ?></div>
    </div>



    <a href="./edit-members.php">Edit Display Information?</a>
    <a href="./delete-members.php">Want to delete your account?</a>
    
</body>
</html>