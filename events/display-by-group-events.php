<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //
    
    include '../db-connect.php';

    $userId = $memberID;
    $groupId = $_GET['group_id'];  // Get the group ID from the URL

    // Fetch all events for the selected group
    $stmt = $pdo->prepare("
        SELECT * FROM Events 
        WHERE GroupID = ?"
    );
    $stmt->execute([$groupId]);
    $events = $stmt->fetchAll();

    // Check if the logged-in user is an admin of the group
    $stmt = $pdo->prepare("
        SELECT * FROM GroupMembers 
        WHERE GroupID = ? AND MemberID = ? AND Role = 'Admin'"
    );
    $stmt->execute([$groupId, $userId]);
    $isAdmin = $stmt->rowCount() > 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../styles.css?<?php echo time(); ?>" rel="stylesheet"/>
    <link href="./events.css?<?php echo time(); ?>" rel="stylesheet"/>
</head>
<body>
    <h1>Events for Group</h1>

    <div class="event-wrapper">
        <div class="event-groups">
            <h2>Events</h2>
            <ul>
                <?php foreach ($events as $event): ?>
                    <li>
                        <a href="vote-events.php?event_id=<?php echo $event['EventID']; ?>&group_id=<?php echo $groupId; ?>">
                            <?php echo htmlspecialchars($event['EventTitle']); ?> - Status: <?php echo htmlspecialchars($event['EventStatus']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="event-groups">
            <!-- If the member is an admin of the group, show additional links -->
            <?php if ($isAdmin): ?>
                <h3>Admin Options</h3>
                <ul>
                    <li><a href="create-events.php?group_id=<?php echo $groupId; ?>">Create New Event</a></li>
                    <li><a href="finalize-events.php?group_id=<?php echo $groupId; ?>">Finalize Event</a></li>

                    <!-- Link to choose another group -->
                    <li><a href="./display-events.php">Choose another Group?</a></li>
                </ul>
            <?php else: ?>
                <ul>
                    <!-- Link to choose another group -->
                    <li><a href="./display-events.php">Choose another Group?</a></li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
