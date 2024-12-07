<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //
    
    include '../db-connect.php';

    // Check the Member's Account type. If it is Business, then fetch payments for the logged-in user
    try {
        $stmt = $pdo->prepare("
            SELECT AccountType FROM Members WHERE MemberID = :memberID
        ");
        $stmt->execute([':memberID' => $memberID]);
        $accountType = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($accountType['AccountType'] === 'Business') {
            // Fetch payments for the logged-in user
            $stmt = $pdo->prepare("SELECT PaymentID, Amount, PaymentDate, Description FROM Payments WHERE MemberID = :member_id");
            $stmt->execute([':member_id' => $memberID]);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

    } catch (PDOException $e) {
        die("Error fetching posts or comments: " . $e->getMessage());
    }

    // Handle the pay button
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_id'])) {
        $paymentID = $_POST['payment_id'];
        
        // Delete the payment record
        $deleteStmt = $pdo->prepare("DELETE FROM Payments WHERE PaymentID = :payment_id AND MemberID = :member_id");
        $deleteStmt->execute([':payment_id' => $paymentID, ':member_id' => $memberID]);

        header("Location: ./payments.php"); // Refresh the page after deletion
        exit();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments</title>
    <link href="../styles.css?<?php echo time(); ?>" rel="stylesheet"/>
</head>
<body>
    <h1>Your Payments</h1>

    <?php if (empty($payments)): ?>
        <p>You have no pending payments. 🎉</p>
    <?php else: ?>
        <table border="1">
            <thead>
                <tr>
                    <th>Amount</th>
                    <th>Payment Date</th>
                    <th>Description</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td>$<?php echo number_format($payment['Amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($payment['PaymentDate']); ?></td>
                        <td><?php echo htmlspecialchars($payment['Description']); ?></td>
                        <td>
                            <form method="POST" action="">
                                <input type="hidden" name="payment_id" value="<?php echo $payment['PaymentID']; ?>">
                                <button type="submit">Pay</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>