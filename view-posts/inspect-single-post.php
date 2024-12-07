<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //
    
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
            WHERE p.PostID = :post_id
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
                <?php
                    $userLiked = false; // Initialize the variable
                    $userDisliked = false; // Initialize the variable for dislike

                    // Check if the user has liked the post
                    $stmt = $pdo->prepare("SELECT * FROM PostLikes WHERE PostID = :postID AND UserID = :memberID");
                    $stmt->execute([':postID' => $post['PostID'], ':memberID' => $memberID]);

                    $existingLike = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($existingLike !== false) {
                        $userLiked = true;
                    }

                    // Check if the user has disliked the post
                    $stmt = $pdo->prepare("SELECT * FROM PostDislikes WHERE PostID = :postID AND UserID = :memberID");
                    $stmt->execute([':postID' => $post['PostID'], ':memberID' => $memberID]);

                    $existingDislike = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($existingDislike !== false) {
                        $userDisliked = true;
                    }

                    // Display the appropriate buttons based on the like/dislike status
                    if ($userLiked && !$userDisliked): ?>
                        <!-- User has liked the post, so show "Remove Like" -->
                        <div class="like-button">
                            <form action="./like-posts.php" method="POST">
                                <input type="hidden" name="post_id" value="<?= $post['PostID'] ?>">
                                <button type="submit">Remove Like</button>
                            </form>
                        </div>
                    <?php elseif (!$userLiked && !$userDisliked): ?>
                        <!-- User hasn't liked or disliked the post, so show both "Like" and "Dislike" -->
                        <div class="like-button">
                            <form action="./like-posts.php" method="POST">
                                <input type="hidden" name="post_id" value="<?= $post['PostID'] ?>">
                                <button type="submit">Like</button>
                            </form>
                        </div>
                        <div class="dislike-button">
                            <form action="dislike-posts.php" method="POST">
                                <input type="hidden" name="post_id" value="<?= $post['PostID'] ?>">
                                <button type="submit">Dislike</button>
                            </form>
                        </div>
                    <?php elseif (!$userLiked && $userDisliked): ?>
                        <!-- User has disliked the post, so show "Remove Dislike" -->
                        <div class="dislike-button">
                            <form action="dislike-posts.php" method="POST">
                                <input type="hidden" name="post_id" value="<?= $post['PostID'] ?>">
                                <button type="submit">Remove Dislike</button>
                            </form>
                        </div>
                    <?php endif; ?>
            </div>
        
            <!-- Comment Form -->
            <h3>Add a Comment</h3>
            <form action="" method="POST">
                <textarea name="comment_content" rows="3" required></textarea><br><br>
                <button type="submit">Post Comment</button>
            </form>
        </div>

        <div class="single-comments">
            <!-- Display Comments -->
            <h3>Comments:</h3>
            <?php if (empty($comments)): ?>
                <p>No comments yet.</p>
            <?php else: ?>
                <!-- Inside the comment display loop -->
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-wrapper">
                        <div class="comment">
                            <?php 
                                // Fetch the username of the commenter
                                $commenterStmt = $pdo->prepare("SELECT Username FROM Members WHERE MemberID = :authorID");
                                $commenterStmt->execute([':authorID' => $comment['AuthorID']]);
                                $commenter = $commenterStmt->fetch(PDO::FETCH_ASSOC);
                            ?>
                            <p><strong><?= htmlspecialchars($commenter['Username']) ?></strong> (<?= htmlspecialchars($comment['CreationDate']) ?>):</p>
                            <p><?= htmlspecialchars($comment['Content']) ?></p>
                        </div>
                        <!-- Edit and Delete options if the comment belongs to the logged-in user -->
                        <?php if ($comment['AuthorID'] == $memberID): ?>
                                <!-- Edit Comment Form -->
                                <div class="edit-comment">
                                    <form action="./edit-comments.php" method="POST">
                                        <input type="hidden" name="comment_id" value="<?= $comment['CommentID'] ?>">
                                        <textarea name="edited_content" rows="3"><?= htmlspecialchars($comment['Content']) ?></textarea><br>
                                        <button type="submit">Save Changes</button>
                                    </form>
                                </div>

                                <!-- Delete Comment Form -->
                                <div class="delete-comment">
                                    <form action="./delete-comments.php" method="POST">
                                        <input type="hidden" name="comment_id" value="<?= $comment['CommentID'] ?>">
                                        <button type="submit">Delete Comment</button>
                                    </form>
                                </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>