<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //
    
    include '../db-connect.php';

    $chatWithID = 0;

    // Retrieve chat history between the logged-in user and another member
    if (isset($_GET['chat_with'])) {
        $chatWithID = intval($_GET['chat_with']);
        $_SESSION['chat_with'] = $chatWithID;  // Save chatWithID to session

        $stmt = $pdo->prepare("SELECT * FROM Messages WHERE 
                            (SenderID = :memberID AND ReceiverID = :chatWithID OR 
                            SenderID = :chatWithID AND ReceiverID = :memberID) 
                            ORDER BY DataSent ASC");
        $stmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);
        $stmt->bindParam(':chatWithID', $chatWithID, PDO::PARAM_INT);
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // If there are no messages, set a placeholder message
        if (!$messages) {
            $messages = []; // array must be empty to avoid illegal access
        }
    }

    // Handle sending a message
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
        $messageContent = htmlspecialchars($_POST['message']);
        $receiverID = isset($_SESSION['chat_with']) ? $_SESSION['chat_with'] : 0;  // Use session value for chatWithID
        $currentTime = date('Y-m-d H:i:s');

        // Check if the receiver is in an active relationship with the sender
        $stmt = $pdo->prepare("SELECT * FROM Relationships WHERE 
                            (SenderMemberID = :memberID AND ReceiverMemberID = :receiverID OR 
                            SenderMemberID = :receiverID AND ReceiverMemberID = :memberID) 
                            AND Status = 'Active'");
        $stmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);
        $stmt->bindParam(':receiverID', $receiverID, PDO::PARAM_INT);
        $stmt->execute();
        $relationship = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($relationship) {
            // Insert the message into the Messages table
            $insertStmt = $pdo->prepare("INSERT INTO Messages (SenderID, ReceiverID, Content, DataSent) 
                                        VALUES (:senderID, :receiverID, :content, :dataSent)");
            $insertStmt->bindParam(':senderID', $memberID, PDO::PARAM_INT);
            $insertStmt->bindParam(':receiverID', $receiverID, PDO::PARAM_INT);
            $insertStmt->bindParam(':content', $messageContent, PDO::PARAM_STR);
            $insertStmt->bindParam(':dataSent', $currentTime, PDO::PARAM_STR);
            $insertStmt->execute();

            $message = "Message sent successfully.";
        } else {
            $message = "You are not in an active relationship with this member.";
        }

        // Redirect to avoid form resubmission
        header("Location: ./chat.php?chat_with=" . $receiverID . "&message=" . urlencode($message));
        exit;
    }
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat System</title>
    <link href="../styles.css?<?php echo time(); ?>" rel="stylesheet"/>
    <link href="./chat.css?<?php echo time(); ?>" rel="stylesheet"/>
</head>
<body>
    
    <div class="chat-container">
        <!-- Show message success or error -->
        <?php if (isset($message)): ?>
            <p class="message"><?= htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <!-- Get the friends to chat with -->
        <div class="chat-get-form">
            <form id="get-chat-logs" action="chat.php" method="GET">
                <?php 
                    // Fetch active relationships of the user
                    $stmt = $pdo->prepare("SELECT * FROM Relationships WHERE 
                                                (SenderMemberID = :memberID OR ReceiverMemberID = :memberID) 
                                                AND Status = 'Active'");
                    $stmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);
                    $stmt->execute();
                    $relationships = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (!empty($relationships)): // Check if relationships exist
                        foreach ($relationships as $relationship): 

                            if ($relationship['SenderMemberID'] == $memberID) {
                                $chatWithID = $relationship['ReceiverMemberID'];
                            } else {
                                $chatWithID = $relationship['SenderMemberID'];
                            }

                            // Fetch username of the receiver
                            $userStmt = $pdo->prepare("SELECT Username FROM Members WHERE MemberID = :chatWithID");
                            $userStmt->bindParam(':chatWithID', $chatWithID, PDO::PARAM_INT);
                            $userStmt->execute();
                            $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                            if ($user && isset($user['Username'])): // Ensure username exists
                ?>
                            <div class="chat-option" data-chat-with="<?= $chatWithID ?>" onclick="submitForm(this)">
                                <?= htmlspecialchars($user['Username']); ?>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No friends to chat with.</p>
                    <?php endif; ?>
                </select>
            </form>
        </div>

        <div class="chat-display-messages">
            <!-- Chat with a specific member -->
            <?php if (!empty($messages)): ?>
                <div class="chat-history" id="chat-history">
                    <?php foreach ($messages as $message): ?>
                        <div class="chat-message">
                            <?php 
                                if (!is_array($message)) continue; // Ensure message is an array
                                
                                $senderID = $message['SenderID'] ?? null;
                                $receiverID = $message['ReceiverID'] ?? null;
                                $content = $message['Content'] ?? '[No content]';
                                $dataSent = $message['DataSent'] ?? '[Unknown time]';

                                // Fetch sender's username
                                $stmt = $pdo->prepare("SELECT Username FROM Members WHERE MemberID = :senderID");
                                $stmt->bindParam(':senderID', $senderID, PDO::PARAM_INT);
                                $stmt->execute();
                                $sender = $stmt->fetch(PDO::FETCH_ASSOC);

                                $isCurrentUser = ($senderID == $memberID); // Check if the message is sent by the current user
                            ?>
                            <div class="chat-message-box <?= $isCurrentUser ? 'right' : 'left'; ?>">
                                <strong>
                                    <?= $isCurrentUser ? "You" : htmlspecialchars($sender['Username'] ?? 'Unknown Sender'); ?>:
                                </strong>
                                <p><?= htmlspecialchars($content); ?></p>
                                <small>Sent at: <?= htmlspecialchars($dataSent); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No chat history available.</p>
            <?php endif; ?>

            
                <!-- Send a new message -->
                <form action="chat.php" method="POST">
                    <div class="chat-send-form">  
                        <div class="text-message">
                            <textarea name="message" id="message" rows="3" cols="120" required></textarea>
                        </div>
                        <div class="text-message-send">
                            <button type="submit" class="chat-send-button">Send</button>
                        </div>
                    </div>
                </form>
        </div>
    </div>
    
    <script>
        // Function to handle the form submission
        function submitForm(element) {
            // Set the selected chat_with value to the clicked div's data attribute
            const chatWithID = element.getAttribute('data-chat-with');

            // Create a hidden input field to store the selected value
            const inputElement = document.createElement('input');
            inputElement.type = 'hidden';
            inputElement.name = 'chat_with';
            inputElement.value = chatWithID;

            // Append the hidden input to the form
            const form = document.getElementById('get-chat-logs');
            form.appendChild(inputElement);

            // Submit the form
            form.submit();
        }

        // Function to refresh chat history every 5 seconds
        function refreshChatHistory() {
            // Fetch the current chatWithID from the session (or any method you are using to track the current chat)
            const chatWithID = <?php echo isset($chatWithID) ? $chatWithID : 0; ?>;

            // Create an AJAX request to get new messages
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `chat.php?chat_with=${chatWithID}`, true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    const chatHistoryElement = document.getElementById('chat-history');
                    
                    // Clear the current chat history
                    chatHistoryElement.innerHTML = '';

                    // Append new messages to the chat history
                    response.forEach(message => {
                        const messageElement = document.createElement('div');
                        messageElement.classList.add('message');
                        messageElement.id = `message-${message.MessageID}`;
                        messageElement.innerHTML = `
                            <strong>${message.SenderID === <?php echo $memberID; ?> ? 'You' : message.SenderName}:</strong>
                            <p>${message.Content}</p>
                            <small>Sent at: ${message.DataSent}</small>
                        `;
                        chatHistoryElement.appendChild(messageElement);
                    });
                }
            };
            xhr.send();
        }

        // Set an interval to refresh chat history every 5 seconds
        setInterval(refreshChatHistory, 5000);

        // Some JavaScript that automatically scrolls the scroll wheel to the bottom for Chat History
        function scrollToBottom() {
            const chatHistory = document.getElementById('chat-history');
            chatHistory.scrollTop = chatHistory.scrollHeight; // Scroll to the bottom
        }

        // Call the function when the page loads
        window.onload = scrollToBottom();

        // Call the function whenever new messages are added
        function addMessage(message) {
            const chatHistory = document.getElementById('chat-history');
            const newMessage = document.createElement('p');
            newMessage.textContent = message;
            chatHistory.appendChild(newMessage);

            scrollToBottom(); // Ensure scrolling happens after the message is added
        }
    </script>

</body>
</html>