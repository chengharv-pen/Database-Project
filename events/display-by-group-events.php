<?php
    include '../db-connect.php';

    $userId = $memberID;
    $groupId = $_GET['group_id'];  // Get the group ID from the URL

    // Fetch all events for the selected group
    $stmt = $pdo->prepare("SELECT * FROM Events WHERE GroupID = ?");
    $stmt->execute([$groupId]);
    $events = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../styles.css?<?php echo time(); ?>" rel="stylesheet"/>
</head>
<body>
    <h1>Events for Group</h1>

    <h2>Events</h2>
    <ul>
        <?php foreach ($events as $event): ?>
            <li>
                <a href="vote-events.php?event_id=<?php echo $event['EventID']; ?>">
                    <?php echo htmlspecialchars($event['EventTitle']); ?> - Status: <?php echo htmlspecialchars($event['EventStatus']); ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    
    <!-- IF MEMBER IS ADMIN OF THIS GROUP, 
         THEN SHOW HIM A LINK TO create-events.php AND finalize-events.php
    -->
    <?php if ($memberID): ?>

    <?php endif; ?>

    <a href="./display-events.php">Choose another Group?</a>
</body>
</html>
