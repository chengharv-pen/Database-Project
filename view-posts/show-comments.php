<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //
    
    // Fetch the comments for this post
    $commentStmt = $pdo->prepare("
        SELECT c.CommentID, c.AuthorID, c.Content, c.CreationDate 
        FROM Comments c 
        WHERE c.PostID = :post_id 
        ORDER BY c.CreationDate DESC
    ");
    $commentStmt->execute([':post_id' => $postID]);
    $comments = $commentStmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle comment submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_content'])) {
        $commentContent = $_POST['comment_content'];

        try {
            $stmt = $pdo->prepare("INSERT INTO Comments (PostID, AuthorID, Content, CreationDate) 
                VALUES (:post_id, :author_id, :content, NOW())");
            $stmt->execute([
                ':post_id' => $postID,
                ':author_id' => $memberID, // Assume $memberID is the ID of the logged-in user
                ':content' => $commentContent,
            ]);

            // Update comment count for the post
            $updateStmt = $pdo->prepare("UPDATE Posts SET CommentsCount = CommentsCount + 1 WHERE PostID = :post_id");
            $updateStmt->execute([':post_id' => $postID]);

            // Redirect to avoid resubmitting the form
            header("Location: inspect-single-post.php?post_id=" . $postID);
            exit;
        } catch (PDOException $e) {
            die("Error submitting comment: " . $e->getMessage());
        }
    }
?>