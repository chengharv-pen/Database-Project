<?php
    // Start session
    session_start();

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

    // Get MemberID and Privilege from session
    $memberID = $_SESSION['MemberID'];
    $privilege = $_SESSION['Privilege']; // Admin/Senior/Junior

    // Determine the view mode (default to 'all')
    $view = isset($_GET['view']) ? $_GET['view'] : 'all';

    // Fetch all groups from the database
    $allGroups = [];
    $joinedGroups = [];


    try {
        if ($view === 'all') {

            // Fetch all groups
            $stmt = $pdo->query("SELECT * FROM `Groups`");
            $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } elseif ($view === 'joined') {

            // Fetch joined groups
            $stmt = $pdo->prepare("
                SELECT g.*, gm.Role 
                FROM `Groups` g
                JOIN GroupMembers gm ON g.GroupID = gm.GroupID
                WHERE gm.MemberID = :memberID
            ");

            $stmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);

            $stmt->execute();

            $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        die("Error fetching groups: " . $e->getMessage());
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../styles.css" rel="stylesheet"/>
    <title>Display Groups</title>
</head>
<body>
    <h1>Display Groups</h1>

    <!-- Dropdown to toggle view -->
    <form method="GET" action="">
        <label for="view">Select View:</label>
        <select name="view" id="view" onchange="this.form.submit()">
            <option value="all" <?= $view === 'all' ? 'selected' : '' ?>>All Groups</option>
            <option value="joined" <?= $view === 'joined' ? 'selected' : '' ?>>Joined Groups</option>
        </select>
    </form>

    <!-- Display groups based on selection -->
    <ul>
        <?php if ($view === 'all'): ?>
            <h2>All Groups</h2>
        <?php else: ?>
            <h2>Your Joined Groups</h2>
        <?php endif; ?>

        <?php foreach ($groups as $group): ?>
            <li>
                <strong><?= htmlspecialchars($group['GroupName']) ?></strong>
                (<?= htmlspecialchars($group['GroupType']) ?>, <?= htmlspecialchars($group['Region']) ?>)

                <!-- Filter 1: ALL Groups -->
                <?php if ($view === 'all'): ?>

                    <!-- Join button for all groups -->
                    <form action="./join-group.php" method="POST">
                        <input type="hidden" name="GroupID" value="<?= $group['GroupID'] ?>">
                        <button type="submit">Join</button>
                    </form>

                <!-- Filter 2: JOINED Groups -->
                <?php elseif ($view === 'joined'): ?>
                    <span>Role: <?= htmlspecialchars($group['Role']) ?></span>

                    <!-- Join button for all groups -->
                    <form action="./join-group.php" method="POST" style="display:inline;">
                        <input type="hidden" name="GroupID" value="<?= $group['GroupID'] ?>">
                        <button type="submit">Join</button>
                    </form>

                    <?php if ($group['Role'] === 'Admin' || $privilege !== 'Junior'): ?>
                        <!-- Edit button for Admins/Senior -->
                        <form action="./edit-groups.php" method="GET">
                            <input type="hidden" name="GroupID" value="<?= $group['GroupID'] ?>">
                            <button type="submit">Edit</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
