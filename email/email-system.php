<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //
    
    include '../db-connect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email System</title>
    <link href="../styles.css?<?php echo time(); ?>" rel="stylesheet"/>
</head>
<body>
    <div class="popup-notification" id="email-notification" style="display: none;">
        <p>You have a new email!</p>
        <button onclick="hideNotification()">Dismiss</button>
    </div>

    <h1>Email System</h1>
    <button class="compose-button" onclick="window.location.href='./compose-email.php';">Compose Email</button>

    <div class="email-container">
        <div class="email-list">
            <h2>Inbox</h2>
            <?php
                // Fetch emails for the inbox
                $stmt = $pdo->prepare("SELECT * FROM Email WHERE ReceiverID = :memberID ORDER BY DateSent DESC");
                $stmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);
                $stmt->execute();
                $inboxEmails = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($inboxEmails):
                    foreach ($inboxEmails as $email):
                        // Fetch username of Receiver
                        $stmt = $pdo->prepare("SELECT Username FROM Members WHERE MemberID = :receiverID");
                        $stmt->bindParam(':receiverID', $email['SenderID'], PDO::PARAM_INT);
                        $stmt->execute();
                        $username = $stmt->fetch(PDO::FETCH_ASSOC);
            ?>
                <div class="email-item <?= $email['ReadStatus'] ? 'read' : 'unread'; ?>">
                    <strong>From:</strong> <?= htmlspecialchars($username['Username']); ?><br>
                    <strong>Subject:</strong> 
                    <a href="./view-inbox-email.php?emailID=<?= $email['EmailID']; ?>">
                        <?= htmlspecialchars($email['Subject']); ?>
                    </a><br>
                    <small>Sent at: <?= $email['DateSent']; ?></small>
                </div>
            <?php endforeach; ?>
            <?php else: ?>
                <p>No emails in your inbox.</p>
            <?php endif; ?>
        </div>

        <div class="email-list">
            <h2>Sent Items</h2>
            <?php
                // Fetch sent emails
                $stmt = $pdo->prepare("SELECT * FROM Email WHERE SenderID = :memberID ORDER BY DateSent DESC");
                $stmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);
                $stmt->execute();
                $sentEmails = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($sentEmails):
                    foreach ($sentEmails as $email):
                        // Fetch username of Receiver
                        $stmt = $pdo->prepare("SELECT Username FROM Members WHERE MemberID = :receiverID");
                        $stmt->bindParam(':receiverID', $email['ReceiverID'], PDO::PARAM_INT);
                        $stmt->execute();
                        $username = $stmt->fetch(PDO::FETCH_ASSOC);
            ?>
                <div class="email-item read">
                    <strong>To:</strong> <?= htmlspecialchars($username['Username']); ?> <br>
                    <strong>Subject:</strong>
                    <a href="./view-sent-email.php?emailID=<?= $email['EmailID']; ?>">
                        <?= htmlspecialchars($email['Subject']); ?>
                    </a><br>
                    <small>Sent at: <?= $email['DateSent']; ?></small>
                </div>
            <?php endforeach; ?>
            <?php else: ?>
                <p>No sent emails.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Function to show notification
        function showNotification() {
            const notification = document.getElementById('email-notification');
            if (notification.style.display !== 'block') {
                notification.style.display = 'block';
            }
        }

        // Function to hide notification
        function hideNotification() {
            document.getElementById('email-notification').style.display = 'none';
        }

        // Periodically check for new emails
        setInterval(function() {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'check-new-emails.php', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.newEmails) {
                        showNotification();
                    }
                }
            };
            xhr.send();
        }, 30000); // Check every 30 seconds
    </script>
</body>
</html>
