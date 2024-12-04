<?php
    include '../db-connect.php';

    // Check for messages in the query string
    $message = "";
    if (isset($_GET['message'])) {
        $message = htmlspecialchars($_GET['message']); // Sanitize input
    }

    // Determine the view mode (default to 'all')
    $view = isset($_GET['view']) ? $_GET['view'] : 'all';

    // Fetch all groups from the database
    $allGroups = [];
    $joinedGroups = [];


    try {
        if ($view === 'all') {

            // Fetch all groups that are NOT joined by the logged-in member
            $stmt = $pdo->prepare("
                SELECT g.* 
                FROM `Groups` g
                LEFT JOIN GroupMembers gm ON g.GroupID = gm.GroupID AND gm.MemberID = :memberID
                WHERE gm.GroupMemberID IS NULL
            ");
            
            $stmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);
            
            $stmt->execute();
            
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
    <link href="../styles.css?<?php echo time(); ?>" rel="stylesheet"/>
    <title>Display Groups</title>
</head>
<body>
    <?php if ($message !== ""): ?>
        <p style='color: green; font-size: 30px; font-weight: bold;'><?php echo htmlspecialchars($message); ?></p>     
    <?php endif ?>

    <h1>Display Groups</h1>

    <!-- Dropdown to toggle view -->
    <form method="GET" action="">
        <label for="view">Select View:</label>
        <select name="view" id="view" onchange="this.form.submit()">
            <option value="all" <?= $view === 'all' ? 'selected' : '' ?>>All Groups</option>
            <option value="joined" <?= $view === 'joined' ? 'selected' : '' ?>>Joined Groups</option>
        </select>
    </form>

    <?php if ($privilege !== 'Junior'): ?>
        <br>
        <a href="./create-groups.php">Want to Create Groups?</a>
    <?php endif; ?>
    
    <br>
    <a href="./filtered-groups.php">Want a more Filtered Search?</a>

    <!-- Display groups based on selection -->
    <div class="groups">
        <?php if ($view === 'all'): ?>
            <h2>All Unjoined Groups</h2>
        <?php else: ?>
            <h2>Your Joined Groups</h2>
        <?php endif; ?>
        
        <?php foreach ($groups as $group): ?>
        <div class="group">
            <div class="group-names">
                <strong><?= htmlspecialchars($group['GroupName']) ?></strong>
                (<?= htmlspecialchars($group['GroupType']) ?>, <?= htmlspecialchars($group['Region']) ?>)<br><br>
                Interest Category: <?= htmlspecialchars($group['InterestCategory']) ?>
            </div>

            <?php if ($view === 'joined'): ?>
                <span>Role: <?= htmlspecialchars($group['Role']) ?></span>
            <?php endif; ?>

            <div class="group-buttons">
                <!-- Filter 1: ALL Groups -->
                <?php if ($view === 'all'): ?>
                    
                    <!-- Join button for all groups -->
                    <div class="group-button">
                        <form action="./join-groups.php" method="POST">
                            <input type="hidden" name="GroupID" value="<?= $group['GroupID'] ?>">
                            <button type="submit">Join</button>
                        </form>
                    </div>

                <!-- Filter 2: JOINED Groups -->
                <?php elseif ($view === 'joined'): ?>

                    <!-- Withdraw button for all JOINED groups -->
                    <div class="group-button">
                        <form action="./withdraw-groups.php" method="POST">
                            <input type="hidden" name="GroupID" value="<?= $group['GroupID'] ?>">
                            <button type="submit">Withdraw</button>
                        </form>
                    </div>

                    <!-- View a Group's Members -->
                    <div class="group-button">
                        <form action="./view-members-groups.php" method="POST">
                            <input type="hidden" name="GroupID" value="<?= $group['GroupID'] ?>">
                            <button type="submit">View Group Members</button>
                        </form>
                    </div>
                    
                    <!-- This should only be accessible to Senior/Admin Member AND Group Admin -->
                    <?php if ($group['Role'] === 'Admin' && $privilege !== 'Junior'): ?>
                        <!-- Edit button -->
                        <div class="group-button">
                            <form action="./edit-groups.php" method="GET">
                                <input type="hidden" name="GroupID" value="<?= $group['GroupID'] ?>">
                                <button type="submit">Edit</button>
                            </form>
                        </div>

                        <!-- Delete button -->
                        <div class="group-button">
                            <form action="./delete-groups.php" method="GET">
                                <input type="hidden" name="GroupID" value="<?= $group['GroupID'] ?>">
                                <button type="submit">Delete</button>
                            </form>
                        </div>
                    
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
