<?php
    include '../db-connect.php';

    if (isset($_GET['emailID'])) {
        $emailID = $_GET['emailID'];

        // Fetch the email details
        $stmt = $pdo->prepare("SELECT * FROM Email WHERE EmailID = :emailID");
        $stmt->bindParam(':emailID', $emailID, PDO::PARAM_INT);
        $stmt->execute();
        $email = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($email) {
            // Update ReadStatus to 1 (mark as read)
            $updateStmt = $pdo->prepare("UPDATE Email SET ReadStatus = 1 WHERE EmailID = :emailID");
            $updateStmt->bindParam(':emailID', $emailID, PDO::PARAM_INT);
            $updateStmt->execute();

            // Fetch the SenderID's username
            $usernameStmt = $pdo->prepare("SELECT Username FROM Members WHERE MemberID = :senderID");
            $usernameStmt->bindParam(':senderID', $email['SenderID'], PDO::PARAM_INT);
            $usernameStmt->execute();
            $username = $usernameStmt->fetch(PDO::FETCH_ASSOC);
        } else {
            echo "Email not found.";
            exit;
        }
    } else {
        echo "No email ID provided.";
        exit;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Email</title>
    <link href="../styles.css?<?php echo time(); ?>" rel="stylesheet"/>
</head>
<body>
    <div class="email-details-page">
        <h1>Inbox Email Details</h1>
        <?php if ($email): ?>
            <div class="email-details">
                <p><strong>From:</strong> <?= htmlspecialchars($username['Username']); ?></p>
                <p><strong>Subject:</strong> <?= htmlspecialchars($email['Subject']); ?></p>
                <p><strong>Body:</strong><br><?= nl2br(htmlspecialchars($email['Body'])); ?></p>
                <p><small>Sent at: <?= $email['DateSent']; ?></small></p>
                <a href="./email-system.php">Back to Inbox</a>
            </div>
        <?php else: ?>
            <a href="./email-system.php">Back to Inbox</a>
        <?php endif; ?>
    </div>
</body>
</html>
