<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //
    
    include '../db-connect.php';  

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            // Check if the member is already a senior
            if ($privilege === 'Senior') {
                die("You are already a Senior member.");
            }

            // Check if a promotion request already exists for this member
            $sql = "SELECT Status FROM PromotionRequests WHERE MemberID = :memberID";
            $statement = $pdo->prepare($sql);
            $statement->bindParam(':memberID', $memberID, PDO::PARAM_INT);
            $statement->execute();

            $existingRequest = $statement->fetch(PDO::FETCH_ASSOC);

            // If a denied request exists, change it to pending
            if ($existingRequest && $existingRequest['Status'] === 'denied') {
                $sql = "UPDATE PromotionRequests SET Status = 'pending' WHERE MemberID = :memberID AND Status = 'denied'";
                $statement = $pdo->prepare($sql);
                $statement->bindParam(':memberID', $memberID, PDO::PARAM_INT);
                $statement->execute();

            } elseif (!$existingRequest) {
                // No existing request, create a new pending request
                $sql = "INSERT INTO PromotionRequests (MemberID, Status) VALUES (:memberID, 'pending')";
                $statement = $pdo->prepare($sql);
                $statement->bindParam(':memberID', $memberID, PDO::PARAM_INT);
                $statement->execute();

            }

            // Redirect after processing
            header("Location: ./display-members.php");
            exit();

        } catch (PDOException $e) {
            die("Query failed: " . $e->getMessage());
        }
    }
?>
