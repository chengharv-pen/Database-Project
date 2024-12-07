<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //
    
    include '../db-connect.php';

    $groupID = $_GET['group_id'] ?? null;
    $giftExchangeID = $_GET['gift_exchange_id'] ?? null;

    // Fetch all groups for the dropdown
    $groupsStmt = $pdo->query("SELECT GroupID, GroupName FROM `Groups`");
    $groups = $groupsStmt->fetchAll(PDO::FETCH_ASSOC);

    // If a group is selected, fetch the gift exchanges associated with that group
    if ($groupID) {
        $stmt = $pdo->prepare("SELECT GiftExchangeID, EventName, MaxBudget FROM GiftExchange WHERE GroupID = :group_id");
        $stmt->execute([':group_id' => $groupID]);
        $giftExchanges = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch participants and current status for the selected gift exchange
        if ($giftExchangeID) {
            try {
                // Fetch participants, assigned Secret Santa, and current status from GiftExchangeParticipants
                $stmt = $pdo->prepare("
                    SELECT gep.MemberID, m.Username AS MemberName, gep.AssignedToMemberID, m2.Username AS AssignedToName, gep.ExchangeStatus, gep.PaymentAmount 
                    FROM GiftExchangeParticipants gep
                    LEFT JOIN Members m ON gep.MemberID = m.MemberID
                    LEFT JOIN Members m2 ON gep.AssignedToMemberID = m2.MemberID
                    WHERE gep.GiftExchangeID = :gift_exchange_id
                ");
                $stmt->execute([':gift_exchange_id' => $giftExchangeID]);
                $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Display the current assignments and status
                echo "<div class='event-groups'><h2>Gift Exchange: " . htmlspecialchars($giftExchanges[0]['EventName']) . "</h2>";

                echo "<h3>Current Assignments:</h3>";
                echo "<table border='1'>
                        <tr>
                            <th>Participant</th>
                            <th>Assigned To</th>
                            <th>Current Status</th>
                            <th>Payment Amount</th>
                        </tr>";

                foreach ($participants as $participant) {
                    echo "<tr>
                            <td>" . htmlspecialchars($participant['MemberName']) . "</td>
                            <td>" . htmlspecialchars($participant['AssignedToName']) . "</td>
                            <td>" . htmlspecialchars($participant['ExchangeStatus']) . "</td>
                            <td>" . htmlspecialchars($participant['PaymentAmount']) . "</td>
                        </tr>";
                }
                echo "</table>";
                echo "<br><a href='./pay-gift-exchange.php?group_id=" . $groupID . "&gift_exchange_id=" . $giftExchangeID ."'>Pay an assigned Gift Exchange?</a></div>";

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