<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //
    
    include '../db-connect.php';

    $stmt = $pdo->prepare("SELECT COUNT(*) AS NewEmails FROM Email WHERE ReceiverID = :memberID AND ReadStatus = 0");
    $stmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['newEmails' => $result['NewEmails'] > 0]);
?>
