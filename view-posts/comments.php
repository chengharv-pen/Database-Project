<?php
// comments.php
$comments = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'], $_POST['comment_content'])) {
    $postID = $_POST['post_id'];
    $commentContent = $_POST['comment_content'];

    try {
        $stmt = $pdo->prepare("INSERT INTO Comments (PostID, AuthorID, Content, CreationDate) 
            VALUES (:post_id, :author_id, :content, NOW())");
        $stmt->execute([
            ':post_id' => $postID,
            ':author_id' => $memberID,
            ':content' => $commentContent,
        ]);

        // Update comment count for the post
        $updateStmt = $pdo->prepare("UPDATE Posts SET CommentsCount = CommentsCount + 1 WHERE PostID = :post_id");
        $updateStmt->execute([':post_id' => $postID]);

        header("Location: " . $_SERVER['PHP_SELF']); // Refresh the page to see the new comment
        exit;
    } catch (PDOException $e) {
        die("Error submitting comment: " . $e->getMessage());
    }
}

$view = $_GET['view'] ?? 'none'; // Default to 'none' if not set

// Fetch comments if 'view' is 'all'
if ($view === 'all') {
    foreach ($posts as $post) {
        $postID = $post['PostID'];
        $commentStmt = $pdo->prepare("SELECT c.CommentID, c.AuthorID, c.Content, c.CreationDate 
            FROM Comments c WHERE c.PostID = :post_id ORDER BY c.CreationDate ASC");
        $commentStmt->execute([':post_id' => $postID]);
        $comments[$postID] = $commentStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>