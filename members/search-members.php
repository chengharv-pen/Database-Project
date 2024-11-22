<?php
    // Start session
    session_start();

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

    $member = null;

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
        <button> Add as Friend </button>
    </div>
</body>
</html>