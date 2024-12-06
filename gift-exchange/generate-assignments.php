<?php
    include '../db-connect.php';

    $groupID = $_GET['group_id'] ?? null;
    $giftExchangeID = $_GET['gift_exchange_id'] ?? null;

    // Fetch all groups for the dropdown
    $groupsStmt = $pdo->query("SELECT GroupID, GroupName FROM `Groups`");
    $groups = $groupsStmt->fetchAll(PDO::FETCH_ASSOC);

    // If a group is selected, fetch the gift exchanges associated with that group
    if ($groupID) {
        $stmt = $pdo->prepare("SELECT GiftExchangeID, EventName FROM GiftExchange WHERE GroupID = :group_id");
        $stmt->execute([':group_id' => $groupID]);
        $giftExchanges = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch participants for the selected gift exchange
        if ($giftExchangeID) {
            try {
                // Check if assignments already exist for this gift exchange
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM GiftExchangeParticipants WHERE GiftExchangeID = :gift_exchange_id AND AssignedToMemberID IS NOT NULL");
                $stmt->execute([':gift_exchange_id' => $giftExchangeID]);
                $assignmentsExist = $stmt->fetchColumn() > 0;

                if ($assignmentsExist) {
                    echo "<div class='event-groups'>Assignments have already been generated for this Gift Exchange.</div>";
                } else {
                    // Before proceeding, set the status of the Gift Exchange to 'Ongoing'
                        $updateGiftExchangeStatusStmt = $pdo->prepare("
                        UPDATE GiftExchange
                        SET Status = 'Ongoing' 
                        WHERE GiftExchangeID = :gift_exchange_id
                    ");
                    $updateGiftExchangeStatusStmt->execute([':gift_exchange_id' => $giftExchangeID]);


                    $stmt = $pdo->prepare("SELECT ParticipantID, MemberID FROM GiftExchangeParticipants WHERE GiftExchangeID = :gift_exchange_id");
                    $stmt->execute([':gift_exchange_id' => $giftExchangeID]);
                    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (count($participants) > 1) {
                        // Randomizes Assignments of Secret Santa
                        $members = array_column($participants, 'MemberID');
                        
                        $validAssignments = false;
                        while (!$validAssignments) {
                            $shuffled = $members;
                            shuffle($shuffled);
                            $assignments = array_combine($members, $shuffled);

                            // Check if there's any self-assignment
                            $validAssignments = true;
                            foreach ($assignments as $memberID => $assignedTo) {
                                if ($memberID === $assignedTo) { // Prevent self-assignment
                                    $validAssignments = false;
                                    break;
                                }
                            }
                        }

                        // Update the assignments in the database
                        $updateStmt = $pdo->prepare("
                            UPDATE GiftExchangeParticipants
                            SET AssignedToMemberID = :assigned_to
                            WHERE GiftExchangeID = :gift_exchange_id AND MemberID = :member_id
                        ");
                        foreach ($assignments as $memberID => $assignedTo) {
                            $updateStmt->execute([
                                ':assigned_to' => $assignedTo,
                                ':gift_exchange_id' => $giftExchangeID,
                                ':member_id' => $memberID,
                            ]);
                        }
                        
                        // Fetch event time of the Gift Exchange
                        $stmt = $pdo->prepare("SELECT EventDate FROM GiftExchange WHERE GiftExchangeID = :gift_exchange_id");
                        $stmt->execute([':gift_exchange_id' => $giftExchangeID]);
                        $exchangeTimeFetch = $stmt->fetch(PDO::FETCH_ASSOC);
                        $exchangeTime = $exchangeTimeFetch['EventDate'];

                        echo "<div class='event-groups'><h3>Secret Santa Assignments (Event Time: $exchangeTime)</h3>";
                        echo "<table border='1'>
                                <tr>
                                    <th>Participant</th>
                                    <th>Assigned To</th>
                                    <th>Gift Preference</th>
                                </tr>";

                        foreach ($assignments as $memberID => $assignedTo) {
                            // Fetch names of participants
                            $stmt = $pdo->prepare("SELECT Username FROM Members WHERE MemberID = :member_id");
                            $stmt->execute([':member_id' => $memberID]);
                            $memberName = $stmt->fetchColumn();
                            
                            // Fetch assigned participant's name (Secret Santa)
                            $stmt = $pdo->prepare("SELECT Username FROM Members WHERE MemberID = :member_id");
                            $stmt->execute([':member_id' => $assignedTo]);
                            $assignedToName = $stmt->fetchColumn();

                            // Fetch gift preference
                            $stmt = $pdo->prepare("SELECT GiftPreference FROM GiftExchangeParticipants WHERE GiftExchangeID = :gift_exchange_id AND MemberID = :member_id");
                            $stmt->execute([':gift_exchange_id' => $giftExchangeID, ':member_id' => $memberID]);
                            $giftPreference = $stmt->fetchColumn();

                            echo "<tr>
                                    <td>{$memberName}</td>
                                    <td>{$assignedToName}</td>
                                    <td>{$giftPreference}</td>
                                </tr>";
                        }
                        echo "</table></div>";
                    } else {
                        echo "Not enough participants to assign Secret Santa.";
                    }
                }
            } catch (PDOException $e) {
                die("Error: " . $e->getMessage());
            }
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
        <br>
        <form method="GET" action="">
            <label for="group_id">Select Group:</label>
            <select name="group_id" id="group_id" onchange="this.form.submit()">
                <option value="">--Select Group--</option>
                <?php foreach ($groups as $group): ?>
                    <option value="<?= $group['GroupID'] ?>" <?= ($group['GroupID'] == $groupID) ? 'selected' : '' ?>>
                        <?= $group['GroupName'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form><br>
        </div>

        <?php if ($groupID): ?>
        <div class="event-groups">
            <form method="GET" action="">
                <input type="hidden" name="group_id" value="<?= $groupID ?>">
                <label for="gift_exchange_id">Select Gift Exchange:</label>
                <select name="gift_exchange_id" id="gift_exchange_id" onchange="this.form.submit()">
                    <option value="">--Select Gift Exchange--</option>
                    <?php foreach ($giftExchanges as $giftExchange): ?>
                        <option value="<?= $giftExchange['GiftExchangeID'] ?>" <?= ($giftExchange['GiftExchangeID'] == $giftExchangeID) ? 'selected' : '' ?>>
                            <?= $giftExchange['EventName'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form><br>
        </div>
        <?php endif; ?>
    </div>

</body>
</html>