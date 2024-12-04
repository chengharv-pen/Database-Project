<?php
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

        // Delete the comment from the database
        $deleteStmt = $pdo->prepare("DELETE FROM Comments WHERE CommentID = :commentID");
        $deleteStmt->execute([':commentID' => $commentID]);

        // Redirect back to the post page with a success message
        header("Location: inspect-single-post.php?post_id=" . $comment['PostID'] . "&message=Comment deleted successfully.");
        exit;

    } catch (PDOException $e) {
        die("Error deleting comment: " . $e->getMessage());
    }
?>
