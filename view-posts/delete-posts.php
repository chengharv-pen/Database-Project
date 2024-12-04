<?php
    // Include database connection file
    include('../db-connect.php');

    // Check if 'PostID' is set in the URL (GET request)
    if (isset($_GET['DeletePostID'])) {
        $postID = $_GET['DeletePostID'];

        // Create a prepared statement to delete the post from the database
        $stmt = $pdo->prepare("DELETE FROM Posts WHERE PostID = :postID");
        $stmt->execute([':postID' => $postID]);

        // Execute the query
        if ($stmt->execute()) {
            header("Location: ./view-posts.php?message=Post deleted successfully!");
            exit;

        } else {
            // Error occurred, show error message
            echo "Error deleting post: " . $stmt->error;
        }

    } else {
        header("Location: ./view-posts.php?error=No post ID provided");
        exit;
    }
?>