<?php
    include '../db-connect.php';

    // Only a Group Admin should be able to access...

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $groupId = $_POST['group_id'];
        $eventTitle = $_POST['event_title'];
        $eventDescription = $_POST['event_description'];
        $eventDate = $_POST['event_date'];

        $eventCreatorId = $memberID;  // Event creator is the logged-in user

        // Insert event into database
        $stmt = $pdo->prepare("INSERT INTO Events (GroupID, EventTitle, EventDescription, EventCreatorID, EventDate) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$groupId, $eventTitle, $eventDescription, $eventCreatorId, $eventDate]);

        // Redirect to event voting page
        header("Location: event_voting.php?event_id=" . $pdo->lastInsertId());
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
    <h1>Create New Event</h1>
    <form action="create-events.php" method="POST">
        <label for="group_id">Group:</label>
        <select name="group_id" id="group_id" required>
            <!-- Fetch and display groups -->
            <?php
            $stmt = $pdo->query("SELECT GroupID, GroupName FROM Groups WHERE OwnerID = ?");
            while ($row = $stmt->fetch()) {
                echo "<option value='{$row['GroupID']}'>{$row['GroupName']}</option>";
            }
            ?>
        </select><br>

        <label for="event_title">Event Title:</label>
        <input type="text" name="event_title" required><br>

        <label for="event_description">Event Description:</label>
        <textarea name="event_description" required></textarea><br>

        <label for="event_date">Event Date:</label>
        <input type="datetime-local" name="event_date" required><br>

        <button type="submit">Create Event</button>
    </form>
</body>
</html>
