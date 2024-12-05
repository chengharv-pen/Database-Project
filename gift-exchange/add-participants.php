<?php
    include '../db-connect.php';

    $giftExchangeID = $_GET['gift_exchange_id'] ?? null;
    $groupID = $_POST['group_id'] ?? null;
    $giftExchangeIDSelected = $_POST['gift_exchange_id'] ?? null;

    // Fetch available groups
    $groups = $pdo->query("SELECT GroupID, GroupName FROM Groups")->fetchAll(PDO::FETCH_ASSOC);

    // Fetch gift exchanges for the selected group
    if ($groupID) {
        $giftExchanges = $pdo->prepare("
            SELECT GiftExchangeID, EventName FROM GiftExchange WHERE GroupID = :group_id
        ");
        $giftExchanges->execute([':group_id' => $groupID]);
        $giftExchanges = $giftExchanges->fetchAll(PDO::FETCH_ASSOC);
    }

    // Handle form submission to add participants
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $giftExchangeIDSelected) {
        $participants = $_POST['participants'] ?? [];

        try {
            // Insert participants into GiftExchangeParticipants table
            $stmt = $pdo->prepare("
                INSERT INTO GiftExchangeParticipants (GiftExchangeID, MemberID, GiftPreference)
                VALUES (:gift_exchange_id, :member_id, :gift_preference)
            ");
            foreach ($participants as $memberID => $giftPreference) {
                $stmt->execute([
                    ':gift_exchange_id' => $giftExchangeIDSelected,
                    ':member_id' => $memberID,
                    ':gift_preference' => $giftPreference,
                ]);
            }
            echo "Participants added successfully!";
        } catch (PDOException $e) {
            die("Error: " . $e->getMessage());
        }
    }

    // Fetch all members for the selected gift exchange (if any)
    if ($giftExchangeIDSelected) {
        $stmt = $pdo->prepare("SELECT MemberID, Username FROM Members");
        $stmt->execute();
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <form method="POST" action="">
        <!-- Group Selection -->
        <label for="group_id">Select Group:</label>
        <select name="group_id" id="group_id" onchange="this.form.submit()">
            <option value="">--Select a Group--</option>
            <?php
            foreach ($groups as $group) {
                echo "<option value='{$group['GroupID']}'" . ($group['GroupID'] == $groupID ? ' selected' : '') . ">{$group['GroupName']}</option>";
            }
            ?>
        </select><br><br>

        <!-- Gift Exchange Selection -->
        <?php if ($groupID): ?>
        <label for="gift_exchange_id">Select Gift Exchange:</label>
        <select name="gift_exchange_id" id="gift_exchange_id" onchange="this.form.submit()">
            <option value="">--Select an Event--</option>
            <?php
                foreach ($giftExchanges as $giftExchange) {
                    echo "<option value='{$giftExchange['GiftExchangeID']}'" . ($giftExchange['GiftExchangeID'] == $giftExchangeIDSelected ? ' selected' : '') . ">{$giftExchange['EventName']}</option>";
                }
            ?>
        </select><br><br>
        <?php endif; ?>

        <!-- Participants -->
        <?php if ($giftExchangeIDSelected): ?>
        <label for="participants">Add Participants:</label><br>
        <?php
            if (isset($members)) {
                foreach ($members as $member) {
                    echo "
                    <input type='checkbox' name='participants[{$member['MemberID']}]'>
                    {$member['Username']}
                    <input type='text' name='gift_preferences[{$member['MemberID']}]' placeholder='Gift Preference'><br>
                    ";
                }
            }
        ?>
        <button type="submit">Add Participants</button>
        <?php endif; ?>
    </form>
</body>
</html>
