<?php
    include '../db-connect.php';

    $userId = $memberID;
    $eventId = $_GET['event_id'];  // Get the event ID from the URL

    // Fetch event details
    $stmt = $pdo->prepare("SELECT * FROM Events WHERE EventID = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();

    // Fetch voting options for the event
    $stmt = $pdo->prepare("SELECT * FROM EventVotingOptions WHERE EventID = ?");
    $stmt->execute([$eventId]);
    $options = $stmt->fetchAll();

    // Check if the user has already voted
    $alreadyVoted = false;
    foreach ($options as $option) {
        $stmt = $pdo->prepare("SELECT * FROM EventVotes WHERE EventID = ? AND MemberID = ? AND OptionID = ?");
        $stmt->execute([$eventId, $userId, $option['OptionID']]);
        if ($stmt->rowCount() > 0) {
            $alreadyVoted = true;
            break;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$alreadyVoted) {
        $optionId = $_POST['option_id'];

        // Insert vote into the database
        $stmt = $pdo->prepare("INSERT INTO EventVotes (EventID, MemberID, OptionID) VALUES (?, ?, ?)");
        $stmt->execute([$eventId, $userId, $optionId]);

        // Redirect back to the vote events page
        header("Location: ./vote-events.php?event_id=" . $eventId);
        exit;
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
    <h1><?php echo htmlspecialchars($event['EventTitle']); ?></h1>
    <p><?php echo htmlspecialchars($event['EventDescription']); ?></p>
    <p>Status: <?php echo htmlspecialchars($event['EventStatus']); ?></p>

    <h3>Vote on Date/Time/Place:</h3>
    <form action="event_details.php?event_id=<?php echo $eventId; ?>" method="POST">
        <?php foreach ($options as $option): ?>
            <div>
                <input type="radio" name="option_id" value="<?php echo $option['OptionID']; ?>" id="option-<?php echo $option['OptionID']; ?>" <?php echo $alreadyVoted ? 'disabled' : ''; ?>>
                <label for="option-<?php echo $option['OptionID']; ?>">
                    <?php echo htmlspecialchars($option['OptionValue']); ?>
                    <?php echo $option['IsSuggestedByMember'] ? "(Suggested by a member)" : "(Default option)"; ?>
                </label>
            </div>
        <?php endforeach; ?>

        <?php if ($alreadyVoted): ?>
            <p>You have already voted!</p>
        <?php else: ?>
            <button type="submit">Vote</button>
        <?php endif; ?>
    </form>

    <?php if ($event['EventStatus'] == 'Scheduled'): ?>
        <h3>Voting Results:</h3>
        <?php
        $stmt = $pdo->prepare("SELECT eo.OptionValue, COUNT(ev.VoteID) AS VoteCount FROM EventVotingOptions eo LEFT JOIN EventVotes ev ON eo.OptionID = ev.OptionID WHERE eo.EventID = ? GROUP BY eo.OptionID ORDER BY VoteCount DESC");
        $stmt->execute([$eventId]);
        $voteResults = $stmt->fetchAll();

        foreach ($voteResults as $result) {
            echo "<p>" . htmlspecialchars($result['OptionValue']) . " - Votes: " . $result['VoteCount'] . "</p>";
        }
        ?>
    <?php endif; ?>
</body>
</html>
