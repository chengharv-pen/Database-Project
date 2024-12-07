<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //
    
    include '../db-connect.php';

    // Check if the user is logged in and the comment ID and content are provided
    if (!isset($_POST['comment_id']) || !isset($_POST['edited_content'])) {
        die("Unauthorized access.");
    }

    $commentID = $_POST['comment_id'];
    $editedContent = $_POST['edited_content'];

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
                die("You are not authorized to edit this comment.");
            }
        }

        // Update the comment with the new content
        $updateStmt = $pdo->prepare("UPDATE Comments SET Content = :content WHERE CommentID = :commentID");
        $updateStmt->execute([':content' => $editedContent, ':commentID' => $commentID]);

        // Redirect back to the post page with a success message
        header("Location: inspect-single-post.php?post_id=" . $comment['PostID'] . "&message=Comment edited successfully.");
        exit;

    } catch (PDOException $e) {
        die("Error editing comment: " . $e->getMessage());
    }
?>
