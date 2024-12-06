<?php
    include '../db-connect.php';

    $userId = $memberID;

    // Safely handle group_id from GET or POST
    $groupId = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $groupId = isset($_POST['group_id']) ? $_POST['group_id'] : null;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $groupId = isset($_GET['group_id']) ? $_GET['group_id'] : null;
    }

    if (!$groupId) {
        echo "Error: group_id is missing.";
        echo "Debug: POST data: " . json_encode($_POST);
        echo "Debug: GET data: " . json_encode($_GET);
        exit;
    }

    // Check if the logged-in user is an admin of the group
    $stmt = $pdo->prepare("
        SELECT * FROM GroupMembers 
        WHERE GroupID = ? AND MemberID = ? AND Role = 'Admin'");
    $stmt->execute([$groupId, $userId]);
    $isAdmin = $stmt->rowCount() > 0;

    // Fetch events only if the user is an admin
    if ($isAdmin) {
        // Fetch events for the selected group
        $stmt = $pdo->prepare("SELECT * FROM Events WHERE GroupID = ?");
        $stmt->execute([$groupId]);
        $events = $stmt->fetchAll();

        if ($_SERVER['REQUEST_METHOD'] == 'GET'&& isset($_GET['event_id'])) {
            // Fetch event details based on the selected event
            $eventId = $_GET['event_id'];
            $stmt = $pdo->prepare("SELECT * FROM Events WHERE EventID = ?");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch();

            // Check if the user is the event creator
            if ($event['EventCreatorID'] != $userId) {
                echo "You are not authorized to finalize this event.";
                exit;
            }

            $stmt = $pdo->prepare("
                SELECT eo.OptionDate, eo.OptionTime, eo.OptionPlace, COUNT(CASE WHEN ev.VoteID IS NOT NULL THEN 1 END) AS VoteCount
                FROM EventVotingOptions eo
                LEFT JOIN EventVotes ev ON eo.OptionID = ev.OptionID
                WHERE eo.EventID = ?
                GROUP BY eo.OptionID
                ORDER BY VoteCount DESC
                LIMIT 1
            ");
            $stmt->execute([$eventId]);
            $mostVotedOption = $stmt->fetch();
            print_r($mostVotedOption);
            
            // Check if no vote has been cast yet
            if (!$mostVotedOption || $mostVotedOption['VoteCount'] == 0) {
                echo "No votes have been cast for this event yet.";
                exit;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $eventId = $_POST['event_id'] ?? null;

            if (!$eventId) {
                echo "Error: event_id is missing.";
                exit;
            }

            $finalDate = $_POST['final_date'];
            $finalTime = $_POST['final_time'];
            $finalPlace = $_POST['final_place'];

            $stmt = $pdo->prepare("
                UPDATE Events 
                SET EventStatus = 'Scheduled' 
                WHERE EventID = ?");
            $stmt->execute([$eventId]);

            echo "Event has been finalized!";
            exit;
        }
    } else {
        echo "You must be an admin to finalize events.";
        exit;
    }

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
    <div class="vertical-event-wrapper">
        <h1>Finalize Event</h1>

        <!-- Only a Group Admin should be able to access -->
        <?php if ($isAdmin): ?>
            <div class="event-groups">
                <!-- Form to select an Event in the Group to finalize -->
                <form id="eventForm" action="./finalize-events.php" method="GET">
                    <input type="hidden" name="group_id" value="<?php echo $groupId; ?>"> <!-- Hidden group_id field -->
                    <label for="event_id">Select Event to Finalize:</label>
                    <select name="event_id" id="event_id" onchange="this.form.submit()">
                        <option value="">--Select an Event--</option>
                        <?php foreach ($events as $event): ?>
                            <option value="<?php echo $event['EventID']; ?>">
                                <?php echo htmlspecialchars($event['EventTitle']); ?> - Status: <?php echo htmlspecialchars($event['EventStatus']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <?php if (isset($mostVotedOption)): ?>
                    <h2>Most Voted Option</h2>
                    <form id="finalizeForm" action="./finalize-events.php" method="POST">
                        <input type="hidden" name="group_id" value="<?php echo $groupId; ?>"> <!-- Hidden group_id field -->
                        <input type="hidden" name="event_id" value="<?php echo $eventId; ?>"> <!-- Hidden event_id field -->
                        <fieldset>
                            <legend>Finalize Event</legend>
                            <label for="final_date">Final Date:</label>
                            <input type="text" id="final_date" name="final_date" value="<?php echo htmlspecialchars($mostVotedOption['OptionDate']); ?>" readonly>

                            <label for="final_time">Final Time:</label>
                            <input type="text" id="final_time" name="final_time" value="<?php echo htmlspecialchars($mostVotedOption['OptionTime']); ?>" readonly>

                            <label for="final_place">Final Place:</label>
                            <input type="text" id="final_place" name="final_place" value="<?php echo htmlspecialchars($mostVotedOption['OptionPlace']); ?>" readonly>

                            <button type="submit" onclick="return confirm('Are you sure you want to finalize this event?')">Finalize Event</button>
                        </fieldset>
                    </form>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>You are not authorized to view this page.</p>
        <?php endif; ?>
    </div>
</body>
</html>
