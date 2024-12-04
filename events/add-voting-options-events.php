<?php
    include '../db-connect.php';

    // Only a Group Admin should be able to access...

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $eventId = $_POST['event_id'];
        $optionType = $_POST['option_type'];
        $optionValue = $_POST['option_value'];
        $isSuggestedByMember = isset($_POST['suggested_by_member']) ? 1 : 0;

        // Insert the voting option
        $stmt = $pdo->prepare("INSERT INTO EventVotingOptions (EventID, OptionType, OptionValue, IsSuggestedByMember) VALUES (?, ?, ?, ?)");
        $stmt->execute([$eventId, $optionType, $optionValue, $isSuggestedByMember]);

        // Redirect to the voting page
        header("Location: event_voting.php?event_id=" . $eventId);
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
    <h1>Add Voting Option</h1>
    <form action="./add-voting-options-events.php" method="POST">
        <label for="event_id">Event ID:</label>
        <input type="text" name="event_id" required><br>

        <label for="option_type">Option Type:</label>
        <select name="option_type" required>
            <option value="Date">Date</option>
            <option value="Time">Time</option>
            <option value="Place">Place</option>
        </select><br>

        <label for="option_value">Option Value:</label>
        <input type="text" name="option_value" required><br>

        <label for="suggested_by_member">Is this suggested by a member?</label>
        <input type="checkbox" name="suggested_by_member"><br>

        <button type="submit">Add Voting Option</button>
    </form>
</body>
</html>
