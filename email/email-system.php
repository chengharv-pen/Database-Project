<?php
    session_start();

    // Check if user is authorized
    if (!isset($_SESSION['MemberID']) || !isset($_SESSION['Privilege'])) {
        die("Access denied. Please log in.");
    }

    $memberID = $_SESSION['MemberID'];

    // Database connection
    $host = "localhost"; // Change if using a different host
    $dbname = "db-schema";
    $username = "root";
    $password = "";

    try {
        $pdo = new PDO("mysql:host=$host; dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email System</title>
    <link href="../styles.css" rel="stylesheet"/>
</head>
<body>
    <div class="popup-notification" id="email-notification" style="display: none;">
        <p>You have a new email!</p>
        <button onclick="hideNotification()">Dismiss</button>
    </div>

    <h1>Email System</h1>
    <div class="email-container">
        <h2>Compose Email</h2>
        <form action="./send-email.php" method="POST">
            <label for="receiverName">To:</label>
            <input type="text" name="receiverName" id="receiverName" placeholder="Receiver Name" required><br><br>

            <label for="subject">Subject:</label>
            <input type="text" name="subject" id="subject" placeholder="Subject" required><br><br>

            <label for="body">Body:</label><br>
            <textarea name="body" id="body" rows="5" cols="60" required></textarea><br><br>

            <button type="submit">Send Email</button>
        </form>

        <h2>Inbox</h2>
        <div class="email-list">
            <?php
                // Fetch emails for the inbox
                $stmt = $pdo->prepare("SELECT * FROM Email WHERE ReceiverID = :memberID ORDER BY DateSent DESC");
                $stmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);
                $stmt->execute();
                $inboxEmails = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($inboxEmails):
                    foreach ($inboxEmails as $email):
            ?>
                <div class="email-item">
                    <strong>From:</strong> <?= htmlspecialchars($email['SenderID']); ?><br>
                    <strong>Subject:</strong> <?= htmlspecialchars($email['Subject']); ?><br>
                    <p><?= htmlspecialchars($email['Body']); ?></p>
                    <small>Sent at: <?= $email['DateSent']; ?></small>
                </div>
            <?php 
                    endforeach;
                else: 
            ?>
                <p>No emails in your inbox.</p>
            <?php endif; ?>
        </div>

        <h2>Sent Items</h2>
        <div class="email-list">
            <?php
                // Fetch sent emails
                $stmt = $pdo->prepare("SELECT * FROM Email WHERE SenderID = :memberID ORDER BY DateSent DESC");
                $stmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);
                $stmt->execute();
                $sentEmails = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($sentEmails):
                    foreach ($sentEmails as $email):
            ?>
                <div class="email-item">
                    <strong>To:</strong> <?= htmlspecialchars($email['ReceiverID']); ?><br>
                    <strong>Subject:</strong> <?= htmlspecialchars($email['Subject']); ?><br>
                    <p><?= htmlspecialchars($email['Body']); ?></p>
                    <small>Sent at: <?= $email['DateSent']; ?></small>
                </div>
            <?php 
                    endforeach;
                else: 
            ?>
                <p>No sent emails.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Function to show notification
        function showNotification() {
            document.getElementById('email-notification').style.display = 'block';
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
        }, 5000); // Check every 5 seconds
    </script>
</body>
</html>
