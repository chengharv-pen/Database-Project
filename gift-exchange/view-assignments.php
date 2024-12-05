<?php
include '../db-connect.php';

$groupID = $_GET['group_id'] ?? null;
$giftExchangeID = $_GET['gift_exchange_id'] ?? null;

// Fetch all groups for the dropdown
$groupsStmt = $pdo->query("SELECT GroupID, GroupName FROM Groups");
$groups = $groupsStmt->fetchAll(PDO::FETCH_ASSOC);

// If a group is selected, fetch the gift exchanges associated with that group
if ($groupID) {
    $stmt = $pdo->prepare("SELECT GiftExchangeID, EventName FROM GiftExchange WHERE GroupID = :group_id");
    $stmt->execute([':group_id' => $groupID]);
    $giftExchanges = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch participants for the selected gift exchange
    if ($giftExchangeID) {
        try {
            $stmt = $pdo->prepare("SELECT ParticipantID, MemberID FROM GiftExchangeParticipants WHERE GiftExchangeID = :gift_exchange_id");
            $stmt->execute([':gift_exchange_id' => $giftExchangeID]);
            $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($participants) > 1) {
                // Randomizes Assignments of Secret Santa
                $members = array_column($participants, 'MemberID');
                $shuffled = $members;
                shuffle($shuffled);

                $assignments = array_combine($members, $shuffled);

                foreach ($assignments as $memberID => $assignedTo) {
                    if ($memberID === $assignedTo) { // Prevent self-assignment
                        $assignments = false;
                        break;
                    }
                }

                if ($assignments) {
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
                    
                    // Before printing out, we should fetch the time of the Gift Exchange
                    $stmt = $pdo->prepare("SELECT EventDate FROM GiftExchange WHERE GiftExchangeID = :gift_exchange_id");
                    $stmt->execute([':gift_exchange_id' => $giftExchangeID]);
                    $exchangeTimeFetch = $stmt->fetch(PDO::FETCH_ASSOC);
                    $exchangeTime = $exchangeTimeFetch['EventDate'];

                    echo "<h3>Secret Santa Assignments (Event Time: $exchangeTime)</h3>";
                    echo "<table border='1'>
                            <tr>
                                <th>Participant</th>
                                <th>Assigned To</th>
                            </tr>";

                    foreach ($assignments as $memberID => $assignedTo) {
                        // Fetch names of participants
                        $stmt = $pdo->prepare("SELECT Username FROM Members WHERE MemberID = :member_id");
                        $stmt->execute([':member_id' => $memberID]);
                        $memberName = $stmt->fetchColumn();

                        $stmt = $pdo->prepare("SELECT Username FROM Members WHERE MemberID = :member_id");
                        $stmt->execute([':member_id' => $assignedTo]);
                        $assignedToName = $stmt->fetchColumn();

                        echo "<tr>
                                <td>{$memberName}</td>
                                <td>{$assignedToName}</td>
                            </tr>";
                    }
                    echo "</table>";
                } else {
                    echo "Error: Could not assign participants. Please try again.";
                }
            } else {
                echo "Not enough participants to assign Secret Santa.";
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
</head>
<body>
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

    <?php if ($groupID): ?>
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
    <?php endif; ?>

</body>
</html>