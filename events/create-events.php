<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //
    
    include '../db-connect.php';

    $userId = $memberID;
    $groupId = $_GET['group_id'];  // Get the group ID from the URL

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $groupId = $_POST['group_id'];
        $eventTitle = $_POST['event_title'];
        $eventDescription = $_POST['event_description'];

        $eventCreatorId = $memberID;  // Event creator is the logged-in user

        // Insert event into database
        $stmt = $pdo->prepare("
            INSERT INTO Events (GroupID, EventTitle, EventDescription, EventCreatorID) 
            VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$groupId, $eventTitle, $eventDescription, $eventCreatorId]);

        // Redirect to event voting page
        header("Location: ./display-events.php");
        exit;
    }

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
    <div class="vertical-event-wrapper">
        <h1>Create New Event</h1>

        <div class="event-groups">
        <?php if ($isAdmin): ?>
            <form action="create-events.php" method="POST">
                <label for="group_id">Group:</label>
                <select name="group_id" id="group_id" required>
                    <!-- Fetch and display groups -->
                    <?php
                        try {
                            $stmt = $pdo->prepare("
                                SELECT GroupID, GroupName 
                                FROM `Groups` 
                                WHERE OwnerID = ? AND GroupID = ?
                            ");
                            $stmt->execute([$userId, $groupId]);
                            
                            // Check if groups are found
                            if ($stmt->rowCount() > 0) {
                                $row = $stmt->fetch();
                                echo "<option value='" . htmlspecialchars($row['GroupID']) . "'>" . htmlspecialchars($row['GroupName']) . "</option>";
                            } else {
                                echo "<option disabled>No groups found</option>";
                            }
                        } catch (PDOException $e) {
                            echo "<option disabled>Error fetching groups: " . htmlspecialchars($e->getMessage()) . "</option>";
                        }
                    ?>
                </select><br><br>

                <label for="event_title">Event Title:</label>
                <input type="text" name="event_title" required><br>

                <label for="event_description">Event Description:</label><br>
                <textarea name="event_description" required></textarea><br><br>

                <button type="submit">Create Event</button>
            </form>
        <?php endif; ?>
        </div>
    </div>
</body>
</html>
