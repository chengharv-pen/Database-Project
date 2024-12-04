<?php
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
    <h1>Email System</h1>
    <div class="compose-email-container">
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
        <br>
        <a href="./email-system.php">Back to Inbox</a>
    </div>
</body>
</html>