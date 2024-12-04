<?php
    include '../db-connect.php';

    // Only a Group Admin should be able to access...

    $userId = $memberID;
    $eventId = $_GET['event_id'];  // Get the event ID from the URL

    // Fetch event details
    $stmt = $pdo->prepare("SELECT * FROM Events WHERE EventID = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();

    // Check if the user is the event creator
    if ($event['EventCreatorID'] != $userId) {
        echo "You are not authorized to finalize this event.";
        exit;
    }

    // Get the most voted option
    $stmt = $pdo->prepare("SELECT eo.OptionValue FROM EventVotingOptions eo LEFT JOIN EventVotes ev ON eo.OptionID = ev.OptionID WHERE eo.EventID = ? GROUP BY eo.OptionID ORDER BY COUNT(ev.VoteID) DESC LIMIT 1");
    $stmt->execute([$eventId]);
    $mostVotedOption = $stmt->fetch();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Finalize the event based on the most voted option
        $finalOption = $_POST['final_option'];

        // Update the event with the selected final option
        $stmt = $pdo->prepare("UPDATE Events SET EventDate = ?, EventStatus = 'Scheduled' WHERE EventID = ?");
        $stmt->execute([$finalOption, $eventId]);

        echo "Event has been finalized!";
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
    <h1>Finalize Event</h1>

    <h2>Most Voted Option</h2>
    <p><?php echo htmlspecialchars($mostVotedOption['OptionValue']); ?></p>

    <form action="finalize_event.php?event_id=<?php echo $eventId; ?>" method="POST">
        <label for="final_option">Select Final Option:</label>
        <input type="text" name="final_option" value="<?php echo htmlspecialchars($mostVotedOption['OptionValue']); ?>" readonly>
        <button type="submit">Finalize Event</button>
    </form>
</body>
</html>
