<?php
    // The memberID is in db-connect.php
    include '../db-connect.php';

    // Get the post ID
    $postID = $_POST['post_id'];        // The post ID from the form submission

    try {
        // Check if the user already liked the post
        $stmt = $pdo->prepare("SELECT * FROM PostLikes WHERE PostID = :postID AND UserID = :memberID");
        $stmt->execute([':postID' => $postID, ':memberID' => $memberID]);
        $existingLike = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingLike) {
            // If already liked, remove the like
            $deleteStmt = $pdo->prepare("DELETE FROM PostLikes WHERE PostID = :postID AND UserID = :memberID");
            $deleteStmt->execute([':postID' => $postID, ':memberID' => $memberID]);
        } else {
            // If not already liked, add the like
            $insertStmt = $pdo->prepare("INSERT INTO PostLikes (PostID, UserID) VALUES (:postID, :memberID)");
            $insertStmt->execute([':postID' => $postID, ':memberID' => $memberID]);
        }
        
        // Redirect back to the view posts
        $previousPage = $_SERVER['HTTP_REFERER'];
        header("Location: $previousPage");
        exit;
    } catch (PDOException $e) {
        die("Error updating like status: " . $e->getMessage());
    }
?>