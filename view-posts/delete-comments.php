<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //
    
    include '../db-connect.php';

    // Check if the user is logged in and the comment ID is provided
    if (!isset($_POST['comment_id'])) {
        die("Unauthorized access.");
    }

    $commentID = $_POST['comment_id'];

    try {
        // Fetch the comment to check if the current user is the author
        $stmt = $pdo->prepare("SELECT * FROM Comments WHERE CommentID = :commentID");
        $stmt->execute([':commentID' => $commentID]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$comment) {
            die("Comment not found.");
        }

        // Check if the logged-in user is the author of the comment or the post owner
        if ($comment['AuthorID'] != $memberID) {
            // If the user is not the author of the comment, check if they are the author of the post
            $postStmt = $pdo->prepare("SELECT * FROM Posts WHERE PostID = :postID");
            $postStmt->execute([':postID' => $comment['PostID']]);
            $post = $postStmt->fetch(PDO::FETCH_ASSOC);

            if ($post['AuthorID'] != $memberID) {
                die("You are not authorized to delete this comment.");
            }
        }

        // Begin a transaction (Ensures that both comment deletion and comments count decrement happen atomically.)
        $pdo->beginTransaction();

        // Delete the comment from the database
        $deleteStmt = $pdo->prepare("DELETE FROM Comments WHERE CommentID = :commentID");
        $deleteStmt->execute([':commentID' => $commentID]);

        // Decrement the CommentsCount in the Posts table
        $updateStmt = $pdo->prepare("
            UPDATE Posts 
            SET CommentsCount = CommentsCount - 1 
            WHERE PostID = :postID AND CommentsCount > 0
        ");
        $updateStmt->execute([':postID' => $comment['PostID']]);

        // Commit the transaction
        $pdo->commit();

        // Redirect back to the post page with a success message
        header("Location: inspect-single-post.php?post_id=" . $comment['PostID'] . "&message=Comment deleted successfully.");
        exit;

    } catch (PDOException $e) {
        die("Error deleting comment: " . $e->getMessage());
    }
?>

