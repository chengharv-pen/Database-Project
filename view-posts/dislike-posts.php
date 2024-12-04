<?php
    // The memberID is in db-connect.php
    include '../db-connect.php';

    // Get the post ID
    $postID = $_POST['post_id'];        // The post ID from the form submission

    try {
        // Check if the user already disliked the post
        $stmt = $pdo->prepare("SELECT * FROM PostDislikes WHERE PostID = :postID AND UserID = :memberID");
        $stmt->execute([':postID' => $postID, ':memberID' => $memberID]);
        $existingDislike = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingDislike) {
            // If already disliked, remove the dislike
            $deleteStmt = $pdo->prepare("DELETE FROM PostDislikes WHERE PostID = :postID AND UserID = :memberID");
            $deleteStmt->execute([':postID' => $postID, ':memberID' => $memberID]);
        } else {
            // If not already disliked, add the dislike
            $insertStmt = $pdo->prepare("INSERT INTO PostDislikes (PostID, UserID) VALUES (:postID, :memberID)");
            $insertStmt->execute([':postID' => $postID, ':memberID' => $memberID]);
        }

        // Redirect back to the view posts page
        $previousPage = $_SERVER['HTTP_REFERER'];
        header("Location: $previousPage");
        exit;
    } catch (PDOException $e) {
        die("Error updating dislike status: " . $e->getMessage());
    }
?>
