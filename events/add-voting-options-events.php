<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //
    
    include '../db-connect.php';

    $eventId = $_GET['event_id'];  // Get the event ID from the URL
    $groupId = $_GET['group_id'];  // Get the group ID from the URL

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $optionDate = $_POST['option_date'];
        $optionTime = $_POST['option_time'];
        $optionPlace = $_POST['option_place'];
        $isSuggestedByMember = isset($_POST['suggested_by_member']) ? 1 : 0;

        // Validate the date format (YYYY-MM-DD)
        if (strtotime($optionDate) === false) {
            echo "Invalid date format.";
            exit;
        }

        // Insert the voting option with date, time, and place
        $stmt = $pdo->prepare("
            INSERT INTO EventVotingOptions (EventID, OptionDate, OptionTime, OptionPlace, IsSuggestedByMember) 
            VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$eventId, $optionDate, $optionTime, $optionPlace, $isSuggestedByMember]);

        // Redirect to the voting page
        header("Location: vote-events.php?event_id=" . $eventId . "&group_id=" . $groupId);  // Fixed URL issue
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
        <h1>Add Voting Option</h1>
        <div class="event-groups">
            <form action="add-voting-options-events.php?event_id=<?php echo $eventId; ?>&group_id=<?php echo $groupId; ?>" method="POST">
                <label for="option_date">Date:</label>
                <input type="date" name="option_date" required><br><br>

                <label for="option_time">Time:</label>
                <input type="time" name="option_time" required><br><br>

                <label for="option_place">Place:</label>
                <input type="text" name="option_place" required><br><br>

                <label for="suggested_by_member">Is this suggested by a member?</label>
                <input type="checkbox" name="suggested_by_member"><br><br>

                <button type="submit">Add Voting Option</button>
            </form>
        </div>
    </div>
</body>
</html>
