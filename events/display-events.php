<?php
    include '../db-connect.php';

    // Fetch all groups that the user is a member of
    $stmt = $pdo->prepare("SELECT g.GroupID, g.GroupName FROM GroupMembers gm JOIN Groups g ON gm.GroupID = g.GroupID WHERE gm.MemberID = ?");
    $stmt->execute([$memberID]);
    $groups = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../styles.css?<?php echo time(); ?>" rel="stylesheet"/>
</head>
<body>
    <h1>Events</h1>
    
    <h2>Your Groups</h2>
    <ul>
        <?php foreach ($groups as $group): ?>
            <li><a href="display-by-group-events.php?group_id=<?php echo $group['GroupID']; ?>"><?php echo htmlspecialchars($group['GroupName']); ?></a></li>
        <?php endforeach; ?>
    </ul>

    <!-- there should be a box that just displays all events here, regardless of group specificity -->
</body>
</html>
