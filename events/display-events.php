<?php
    include '../db-connect.php';

    // Fetch all groups that the user is a member of
    $stmt = $pdo->prepare("
        SELECT g.GroupID, g.GroupName 
        FROM GroupMembers gm 
        JOIN Groups g ON gm.GroupID = g.GroupID 
        WHERE gm.MemberID = ?
    ");
    $stmt->execute([$memberID]);
    $groups = $stmt->fetchAll();

    // Fetch ongoing events for user's groups
    $ongoingStmt = $pdo->prepare("
        SELECT e.EventID, e.EventTitle, e.EventDate, g.GroupName 
        FROM Events e 
        JOIN Groups g ON e.GroupID = g.GroupID 
        WHERE e.EventStatus = 'Scheduled' AND e.EventDate > NOW() AND g.GroupID IN (
            SELECT GroupID FROM GroupMembers WHERE MemberID = ?
        )
        ORDER BY e.EventDate ASC
    ");
    $ongoingStmt->execute([$memberID]);
    $ongoingEvents = $ongoingStmt->fetchAll();

    // Fetch previous events for user's groups
    $previousStmt = $pdo->prepare("
        SELECT e.EventID, e.EventTitle, e.EventDate, g.GroupName 
        FROM Events e 
        JOIN Groups g ON e.GroupID = g.GroupID 
        WHERE e.EventStatus = 'Scheduled' AND e.EventDate <= NOW() AND g.GroupID IN (
            SELECT GroupID FROM GroupMembers WHERE MemberID = ?
        )
        ORDER BY e.EventDate DESC
    ");
    $previousStmt->execute([$memberID]);
    $previousEvents = $previousStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../styles.css?<?php echo time(); ?>" rel="stylesheet"/>
    <link href="./events.css?<?php echo time(); ?>" rel="stylesheet"/>
    <title>Events</title>
</head>
<body>
    <h1>Events</h1>
    
    <div class="event-wrapper">
        <div class="event-groups">
            <h2>Your Groups</h2>
            <ul>
                <?php foreach ($groups as $group): ?>
                    <li>
                        <a href="display-by-group-events.php?group_id=<?php echo $group['GroupID']; ?>">
                            <?php echo htmlspecialchars($group['GroupName']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="event-container-2">
            <div class="ongoing-events">
                <h2>Ongoing Events</h2>
                <?php if (!empty($ongoingEvents)): ?>
                    <ul>
                        <?php foreach ($ongoingEvents as $event): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($event['EventTitle']); ?></strong>
                                <br>
                                <span>Group: <?php echo htmlspecialchars($event['GroupName']); ?></span>
                                <br>
                                <span>Date: <?php echo htmlspecialchars($event['EventDate']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No ongoing events at the moment.</p>
                <?php endif; ?>
            </div>

            <div class="previous-events">
                <h2>Previous Events</h2>
                <?php if (!empty($previousEvents)): ?>
                    <ul>
                        <?php foreach ($previousEvents as $event): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($event['EventTitle']); ?></strong>
                                <br>
                                <span>Group: <?php echo htmlspecialchars($event['GroupName']); ?></span>
                                <br>
                                <span>Date: <?php echo htmlspecialchars($event['EventDate']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No previous events found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

