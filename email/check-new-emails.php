<?php
    include '../db-connect.php';

    $stmt = $pdo->prepare("SELECT COUNT(*) AS NewEmails FROM Email WHERE ReceiverID = :memberID AND DateSent > NOW() - INTERVAL 5 SECOND");
    $stmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['newEmails' => $result['NewEmails'] > 0]);
?>
