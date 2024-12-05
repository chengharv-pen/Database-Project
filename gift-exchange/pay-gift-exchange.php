<?php
    include '../db-connect.php';

    $giftExchangeID = $_GET['gift_exchange_id'] ?? null;
    $groupID = $_GET['group_id'] ?? null;

    // Fetch gift exchange details
    $stmt = $pdo->prepare("
        SELECT ge.EventName, ge.EventDate, ge.MaxBudget, ge.Status 
        FROM GiftExchange ge
        WHERE ge.GiftExchangeID = :gift_exchange_id
    ");
    $stmt->execute([':gift_exchange_id' => $giftExchangeID]);
    $giftExchange = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no gift exchange is found, stop
    if (!$giftExchange) {
        die("Gift exchange not found.");
    }

    // Fetch the participant's payment details and status (only for the logged-in user)
    $stmt = $pdo->prepare("
        SELECT gep.ParticipantID, m.Username, gep.ExchangeStatus, gep.PaymentAmount
        FROM GiftExchangeParticipants gep
        JOIN Members m ON gep.MemberID = m.MemberID
        WHERE gep.GiftExchangeID = :gift_exchange_id AND gep.MemberID = :member_id
    ");
    $stmt->execute([
        ':gift_exchange_id' => $giftExchangeID,
        ':member_id' => $memberID
    ]);
    $participant = $stmt->fetch(PDO::FETCH_ASSOC);

    // If the participant is not found, stop
    if (!$participant) {
        die("You are not a participant in this gift exchange.");
    }

    // Handle payment submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $paymentAmount = max(0, (float)$_POST['payment']); // Ensure non-negative payment value

        // Update the logged-in participant's payment amount and ExchangeStatus to 'Gift Purchased'
        $updatePaymentStmt = $pdo->prepare("
            UPDATE GiftExchangeParticipants
            SET PaymentAmount = :payment_amount, ExchangeStatus = 'Gift Purchased'
            WHERE ParticipantID = :participant_id
        ");
        $updatePaymentStmt->execute([
            ':payment_amount' => $paymentAmount,
            ':participant_id' => $participant['ParticipantID']
        ]);

        // Reduce MaxBudget based on the payment
        $reduceBudgetStmt = $pdo->prepare("
            UPDATE GiftExchange
            SET MaxBudget = MaxBudget - :payment_amount
            WHERE GiftExchangeID = :gift_exchange_id
        ");
        $reduceBudgetStmt->execute([
            ':payment_amount' => $paymentAmount,
            ':gift_exchange_id' => $giftExchangeID
        ]);

        // Check if all participants have completed their gift purchases
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS remaining
            FROM GiftExchangeParticipants
            WHERE GiftExchangeID = :gift_exchange_id AND ExchangeStatus != 'Gift Purchased'
        ");
        $stmt->execute([':gift_exchange_id' => $giftExchangeID]);
        $remaining = $stmt->fetchColumn();

        // If no participants have remaining payments, set all to Completed
        if ($remaining == 0) {
            // Set all participants' ExchangeStatus to 'Completed'
            $updateParticipantsStmt = $pdo->prepare("
                UPDATE GiftExchangeParticipants
                SET ExchangeStatus = 'Completed'
                WHERE GiftExchangeID = :gift_exchange_id
            ");
            $updateParticipantsStmt->execute([':gift_exchange_id' => $giftExchangeID]);

            // Set the GiftExchange status to 'Completed'
            $updateGiftExchangeStmt = $pdo->prepare("
                UPDATE GiftExchange
                SET Status = 'Completed'
                WHERE GiftExchangeID = :gift_exchange_id
            ");
            $updateGiftExchangeStmt->execute([':gift_exchange_id' => $giftExchangeID]);
        }

        // Refresh the page to reflect changes
        header("Location: pay-gift-exchange.php?group_id=$groupID&gift_exchange_id=$giftExchangeID");
        exit;
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
            <h2>Gift Exchange: <?= htmlspecialchars($giftExchange['EventName']) ?></h2>
            <p>Event Date: <?= htmlspecialchars($giftExchange['EventDate']) ?></p>
            <p>Remaining Budget: <?= htmlspecialchars($giftExchange['MaxBudget']) ?></p>
            <form method="POST">
                <h3>Your Payment</h3>
                <p>Participant: <?= htmlspecialchars($participant['Username']) ?></p>
                <p>Status: <?= htmlspecialchars($participant['ExchangeStatus']) ?></p>

                <label for="payment">Payment Amount:</label>
                <?php if ($participant['ExchangeStatus'] == 'Assigned'): ?>
                    <input type="number" name="payment" value="<?= $participant['PaymentAmount'] ?>" min="0" max="<?= $giftExchange['MaxBudget'] ?>" step="0.01" required>

                    <br><br>
                    <button type="submit">Submit Payment</button>
                <?php else: ?>
                    <input type="number" name="payment" value="<?= $participant['PaymentAmount'] ?>" disabled>
                <?php endif; ?>
            </form>
        </div>
    </div>
</body>
</html>

