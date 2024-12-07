<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //
    
    include '../db-connect.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $groupID = $_POST['group_id'] ?? null;
        $eventName = $_POST['event_name'] ?? '';
        $eventDate = $_POST['event_date'] ?? '';
        $maxBudget = $_POST['max_budget'] ?? 0;

        if ($groupID && $eventName && $eventDate) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO GiftExchange (GroupID, EventName, EventDate, MaxBudget, Status)
                    VALUES (:group_id, :event_name, :event_date, :max_budget, 'Pending')
                ");
                $stmt->execute([
                    ':group_id' => $groupID,
                    ':event_name' => $eventName,
                    ':event_date' => $eventDate,
                    ':max_budget' => $maxBudget,
                ]);

                echo "Gift exchange created successfully!";
            } catch (PDOException $e) {
                die("Error: " . $e->getMessage());
            }
        } else {
            echo "Please fill all required fields.";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../styles.css?<?php echo time(); ?>" rel="stylesheet">
    <link href="../events/events.css?<?php echo time(); ?>" rel="stylesheet">
</head>
<body>
    <div class="vertical-event-wrapper">
        <div class="event-groups">
            <form method="POST" action="">
                <label for="group_id">Group:</label>
                <select name="group_id" id="group_id" required>
                    <?php
                        $groups = $pdo->query("SELECT GroupID, GroupName FROM `Groups`")->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($groups as $group) {
                            echo "<option value='{$group['GroupID']}'>{$group['GroupName']}</option>";
                        }
                    ?>
                </select><br><br>

                <label for="event_name">Event Name:</label>
                <input type="text" name="event_name" id="event_name" required><br><br>

                <label for="event_date">Event Date:</label>
                <input type="datetime-local" name="event_date" id="event_date" required><br><br>

                <label for="max_budget">Max Budget:</label>
                <input type="number" name="max_budget" id="max_budget" step="0.01" required><br><br>

                <button type="submit">Create Gift Exchange</button>
            </form>
    
            <br><a href="./add-participants.php">Want to Add Participants?</a>
            <br><a href="./generate-assignments.php">Randomly generate the assigned Secret Santa?</a>
            <br><a href="./view-assignments.php">View an assigned Gift Exchange?</a>
        </div>
    </div>
</body>
</html>