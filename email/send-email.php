<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //
    
    include '../db-connect.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $receiverName = $_POST['receiverName'];

        // Fetch receiverID based on receiverName
        $stmt = $pdo->prepare("SELECT MemberID FROM Members WHERE Username = :username");
        $stmt->bindParam(':username', $receiverName, PDO::PARAM_STR);
        $stmt->execute();
        $receiverDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        $receiverID = $receiverDetails['MemberID'];
        $subject = htmlspecialchars($_POST['subject']);
        $body = htmlspecialchars($_POST['body']);
        $dateSent = date('Y-m-d H:i:s');

        $stmt = $pdo->prepare("INSERT INTO Email (SenderID, ReceiverID, Subject, Body, DateSent)
                            VALUES (:senderID, :receiverID, :subject, :body, :dateSent)");
        $stmt->bindParam(':senderID', $memberID, PDO::PARAM_INT);
        $stmt->bindParam(':receiverID', $receiverID, PDO::PARAM_INT);
        $stmt->bindParam(':subject', $subject, PDO::PARAM_STR);
        $stmt->bindParam(':body', $body, PDO::PARAM_STR);
        $stmt->bindParam(':dateSent', $dateSent, PDO::PARAM_STR);
        $stmt->execute();

        header('Location: ./email-system.php');
        exit;
    }
?>