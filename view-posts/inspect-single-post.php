<?php
    // inspect-single-post.php
    include '../db-connect.php';

    $postID = $_GET['post_id'] ?? null; // Get the post ID from the URL parameter
    if (!$postID) {
        die("Post ID is required.");
    }

    try {
        // Fetch the post details
        $stmt = $pdo->prepare("
                SELECT p.PostID, p.AuthorID, p.Content, p.PostDate, 
                    (SELECT COUNT(*) FROM PostLikes pl WHERE pl.PostID = p.PostID) AS Likes,
                    (SELECT COUNT(*) FROM PostDislikes pd WHERE pd.PostID = p.PostID) AS Dislikes, 
                    p.CommentsCount, p.VisibilitySettings, m.MediaType, m.MediaURL
                FROM Posts p
                LEFT JOIN PostMedia m ON p.PostID = m.PostID
                ORDER BY p.PostDate ASC
            ");
        $stmt->execute([':post_id' => $postID]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            die("Post not found.");
        }

        include './show-comments.php';

    } catch (PDOException $e) {
        die("Error fetching post or comments: " . $e->getMessage());
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../styles.css?<?php echo time(); ?>" rel="stylesheet">
    <title>Inspect Post</title>
</head>
<body>
    <div class="single-posts-container">
        <h1>Post Details</h1>
        <div class="single-posts">
            <div class="single-post">

                <p><strong>Author:</strong> 
                    <?php
                        $authorStmt = $pdo->prepare("SELECT Username FROM Members WHERE MemberID = :authorID");
                        $authorStmt->execute([':authorID' => $post['AuthorID']]);
                        $author = $authorStmt->fetch(PDO::FETCH_ASSOC);
                        echo htmlspecialchars($author['Username']);
                    ?>
                </p>

                <p><strong>Posted on:</strong> <?= htmlspecialchars($post['PostDate']) ?></p>

                <p><?= htmlspecialchars($post['Content']) ?></p>

                <?php if ($post['MediaType'] === 'Image'): ?>
                    <img src="<?= htmlspecialchars($post['MediaURL']) ?>" alt="Post Image" class="post-image">
                <?php elseif ($post['MediaType'] === 'Video'): ?>
                    <video controls class="post-video">
                        <source src="<?= htmlspecialchars($post['MediaURL']) ?>" type="video/mp4">
                        <source src="<?= htmlspecialchars($post['MediaURL']) ?>" type="video/avi">
                        <source src="<?= htmlspecialchars($post['MediaURL']) ?>" type="video/mov">
                        <source src="<?= htmlspecialchars($post['MediaURL']) ?>" type="video/x-matroska"> <!-- For MKV files -->
                        Your browser does not support the video tag.
                    </video>
                <?php endif; ?>

                <p><strong>Likes:</strong> <?= $post['Likes'] ?> | <strong>Dislikes:</strong> <?= $post['Dislikes'] ?></p>
                
                <div class="post-feedback-buttons">
                    <!-- Show appropriate buttons -->
                    <div class="like-button">
                    <?php
                        $userLiked = false; // Initialize the variable

                        $stmt = $pdo->prepare("SELECT * FROM PostLikes WHERE PostID = :postID AND UserID = :memberID");
                        $stmt->execute([':postID' => $post['PostID'], ':memberID' => $memberID]);

                        $existingLike = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($existingLike !== false) {
                            $userLiked = true;
                        }

                        if ($userLiked): ?>
                            <!-- User has liked the post, so show "Remove Like" -->
                            <form action="./like-posts.php" method="POST">
                                <input type="hidden" name="post_id" value="<?= $post['PostID'] ?>">
                                <button type="submit">Remove Like</button>
                            </form>
                        <?php else: ?>
                            <!-- User hasn't liked the post, so show "Like" -->
                            <form action="./like-posts.php" method="POST">
                                <input type="hidden" name="post_id" value="<?= $post['PostID'] ?>">
                                <button type="submit">Like</button>
                            </form>
                        <?php endif; ?>
                    </div>
                        
                    <div class="dislike-button">
                    <?php
                        $userDisliked = false; // Initialize the variable

                        $stmt = $pdo->prepare("SELECT * FROM PostDislikes WHERE PostID = :postID AND UserID = :memberID");
                        $stmt->execute([':postID' => $post['PostID'], ':memberID' => $memberID]);

                        $existingLike = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($existingLike !== false) {
                            $userDisliked = true;
                        }

                        if ($userDisliked): ?>
                            <!-- User has disliked the post, so show "Remove Dislike" -->
                            <form action="dislike-posts.php" method="POST">
                                <input type="hidden" name="post_id" value="<?= $post['PostID'] ?>">
                                <button type="submit">Remove Dislike</button>
                            </form>
                        <?php else: ?>
                            <!-- User hasn't disliked the post, so show "Dislike" -->
                            <form action="dislike-posts.php" method="POST">
                                <input type="hidden" name="post_id" value="<?= $post['PostID'] ?>">
                                <button type="submit">Dislike</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Comment Form -->
                <h3>Add a Comment</h3>
                <form action="" method="POST">
                    <textarea name="comment_content" rows="3" required></textarea><br>
                    <button type="submit">Post Comment</button>
                </form>
            </div>
            <div class="single-comments">
                <!-- Display Comments -->
                <h3>Comments:</h3>
                <?php if (empty($comments)): ?>
                    <p>No comments yet.</p>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment">
                            <?php 
                                $commenterStmt = $pdo->prepare("SELECT Username FROM Members WHERE MemberID = :authorID");
                                $commenterStmt->execute([':authorID' => $comment['AuthorID']]);
                                $commenter = $commenterStmt->fetch(PDO::FETCH_ASSOC);
                            ?>
                            <p><strong><?= htmlspecialchars($commenter['Username']) ?></strong> (<?= htmlspecialchars($comment['CreationDate']) ?>):</p>
                            <p><?= htmlspecialchars($comment['Content']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>