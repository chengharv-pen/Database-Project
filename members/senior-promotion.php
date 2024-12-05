<?php
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

                echo "Your previously denied request has been re-submitted and is now pending approval.";

            } elseif (!$existingRequest) {
                // No existing request, create a new pending request
                $sql = "INSERT INTO PromotionRequests (MemberID, Status) VALUES (:memberID, 'pending')";
                $statement = $pdo->prepare($sql);
                $statement->bindParam(':memberID', $memberID, PDO::PARAM_INT);
                $statement->execute();

                echo "Your promotion request has been submitted successfully. It is pending approval.";
            } else {
                echo "You already have a pending or approved promotion request.";
            }

            // Redirect after processing
            header("Location: ./display-members.php");
            exit();

        } catch (PDOException $e) {
            die("Query failed: " . $e->getMessage());
        }
    }
?>
