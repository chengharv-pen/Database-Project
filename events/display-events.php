<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //
    
    include '../db-connect.php';

    // Fetch all groups that the user is a member of
    $stmt = $pdo->prepare("
        SELECT g.GroupID, g.GroupName 
        FROM GroupMembers gm 
        JOIN `Groups` g ON gm.GroupID = g.GroupID 
        WHERE gm.MemberID = ?
    ");
    $stmt->execute([$memberID]);
    $groups = $stmt->fetchAll();

    // Fetch ongoing events with most voted options (Event Date is now in EventVotingOptions)
    $ongoingStmt = $pdo->prepare("
        WITH RankedVotes AS (
            SELECT 
                e.EventID, 
                e.EventTitle, 
                g.GroupName, 
                ev.OptionDate, 
                ev.OptionTime, 
                ev.OptionPlace, 
                COUNT(evote.VoteID) AS VoteCount,
                ROW_NUMBER() OVER (PARTITION BY e.EventID ORDER BY COUNT(evote.VoteID) DESC, ev.OptionDate ASC) AS rn
            FROM Events e
            JOIN `Groups` g ON e.GroupID = g.GroupID
            LEFT JOIN EventVotingOptions ev ON e.EventID = ev.EventID
            LEFT JOIN EventVotes evote ON ev.OptionID = evote.OptionID
            JOIN GroupMembers gm ON gm.GroupID = e.GroupID
            WHERE e.EventStatus = 'Scheduled'
            AND ev.OptionDate > NOW()
            AND gm.MemberID = 1
            GROUP BY e.EventID, ev.OptionID
        )
        SELECT 
            EventID, 
            EventTitle, 
            GroupName, 
            OptionDate, 
            OptionTime, 
            OptionPlace, 
            VoteCount
        FROM RankedVotes
        WHERE rn = 1;
    ");

    $ongoingStmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);
    $ongoingStmt->execute();
    $ongoingEvents = $ongoingStmt->fetchAll();

    // Fetch previous events with most voted options (Event Date is now in EventVotingOptions)
    $previousStmt = $pdo->prepare("
        WITH RankedVotes AS (
            SELECT 
                e.EventID, 
                e.EventTitle, 
                g.GroupName, 
                ev.OptionDate, 
                ev.OptionTime, 
                ev.OptionPlace, 
                COUNT(evote.VoteID) AS VoteCount,
                ROW_NUMBER() OVER (PARTITION BY e.EventID ORDER BY COUNT(evote.VoteID) DESC, ev.OptionDate ASC) AS rn
            FROM Events e
            JOIN `Groups` g ON e.GroupID = g.GroupID
            LEFT JOIN EventVotingOptions ev ON e.EventID = ev.EventID
            LEFT JOIN EventVotes evote ON ev.OptionID = evote.OptionID
            JOIN GroupMembers gm ON gm.GroupID = e.GroupID
            WHERE e.EventStatus = 'Scheduled'
            AND ev.OptionDate <= NOW()
            AND gm.MemberID = 1
            GROUP BY e.EventID, ev.OptionID
        )
        SELECT 
            EventID, 
            EventTitle, 
            GroupName, 
            OptionDate, 
            OptionTime, 
            OptionPlace, 
            VoteCount
        FROM RankedVotes
        WHERE rn = 1;
    ");
    $previousStmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);
    $previousStmt->execute();
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
                <h2>Upcoming Events</h2>
                <?php if (!empty($ongoingEvents)): ?>
                    <ul>
                        <?php foreach ($ongoingEvents as $event): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($event['EventTitle']); ?></strong>
                                <br>
                                <span>Group: <?php echo htmlspecialchars($event['GroupName']); ?></span>
                                <br>
                                <br>
                                <?php if ($event['OptionDate'] && $event['OptionTime'] && $event['OptionPlace']): ?>
                                    <span>Most Voted Option:</span>
                                    <br>
                                    <span>Date: <?php echo htmlspecialchars($event['OptionDate']); ?></span>
                                    <br>
                                    <span>Time: <?php echo htmlspecialchars($event['OptionTime']); ?></span>
                                    <br>
                                    <span>Place: <?php echo htmlspecialchars($event['OptionPlace']); ?></span>
                                <?php else: ?>
                                    <span>No voting options available yet.</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No upcoming events at the moment.</p>
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
                                <br>
                                <?php if ($event['OptionDate'] && $event['OptionTime'] && $event['OptionPlace']): ?>
                                    <span>Most Voted Option:</span>
                                    <br>
                                    <span>Date: <?php echo htmlspecialchars($event['OptionDate']); ?></span>
                                    <br>
                                    <span>Time: <?php echo htmlspecialchars($event['OptionTime']); ?></span>
                                    <br>
                                    <span>Place: <?php echo htmlspecialchars($event['OptionPlace']); ?></span>
                                <?php else: ?>
                                    <span>No voting options available.</span>
                                <?php endif; ?>
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


