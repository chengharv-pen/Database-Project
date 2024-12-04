<?php
    include '../db-connect.php';

    try {
        // Fetch posts from the database (TODO: ADD A TOGGLE TO SORT BY DESC)
        $stmt = $pdo->prepare("
            SELECT p.PostID, p.AuthorID, p.Content, p.PostDate, p.Likes, p.Dislikes, 
                p.CommentsCount, p.VisibilitySettings, m.MediaType, m.MediaURL
            FROM Posts p
            LEFT JOIN PostMedia m ON p.PostID = m.PostID
            ORDER BY p.PostDate ASC
        ");
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch comments for each post
        $comments = [];
        foreach ($posts as $post) {
            $postID = $post['PostID'];
            $commentStmt = $pdo->prepare("
                SELECT c.CommentID, c.AuthorID, c.Content, c.CreationDate 
                FROM Comments c WHERE c.PostID = :post_id ORDER BY c.CreationDate ASC
            ");
            $commentStmt->execute([':post_id' => $postID]);
            $comments[$postID] = $commentStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        die("Error fetching posts or comments: " . $e->getMessage());
    }

    // Handle comment submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'], $_POST['comment_content'])) {
        $postID = $_POST['post_id'];
        $commentContent = $_POST['comment_content'];

        try {
            $stmt = $pdo->prepare("
                INSERT INTO Comments (PostID, AuthorID, Content, CreationDate) 
                VALUES (:post_id, :author_id, :content, NOW())
            ");
            $stmt->execute([
                ':post_id' => $postID,
                ':author_id' => $memberID,
                ':content' => $commentContent,
            ]);

            // Update comment count for the post
            $updateStmt = $pdo->prepare("
                UPDATE Posts SET CommentsCount = CommentsCount + 1 WHERE PostID = :post_id
            ");
            $updateStmt->execute([':post_id' => $postID]);

            header("Location: " . $_SERVER['PHP_SELF']); // Refresh the page to see the new comment
            exit;
        } catch (PDOException $e) {
            die("Error submitting comment: " . $e->getMessage());
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../styles.css?<?php echo time(); ?>" rel="stylesheet">
</head>
<body>
    <div class="posts">
        <h1>Posts</h1>

        <form action="" method="GET">
            From:
            <select> 
                <option name="postFrom" value="Anyone">Anyone</option>
                <option name="postFrom" value="You">You</option>
            </select>
        </form>

        <form action="" method="GET">
            Time:
            <select> 
                <option name="postTime" value="Oldest">Oldest</option>
                <option name="postTime" value="Newest">Newest</option>
            </select>
        </form>

        <form action="" method="GET">
            Post Type:
            <select> 
                <option name="postType" value="Public">Public</option>
                <option name="postType" value="Group">Group</option>
                <option name="postType" value="Private">Private</option>
            </select>
        </form>

        <?php foreach ($posts as $post): ?>
            <div class="post">
                <p>
                    <strong>
                    <?php
                        // Fetch Member's username based on the AuthorID of the Post
                        $stmt = $pdo->prepare("SELECT * FROM Members WHERE MemberID = :memberID");
                        $stmt->execute([
                            ':memberID' => $post['AuthorID'],
                        ]);
                        $memberPoster = $stmt->fetch(PDO::FETCH_ASSOC);
                    ?>
                    <?= htmlspecialchars($memberPoster['Username']) ?>
                    </strong>

                    (<?= htmlspecialchars($post['PostDate']) ?>)   
                </p>

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
                <p><button>Like</button> | <button>Dislike</button></p>

                <p><strong>Comments:</strong> <?= $post['CommentsCount'] ?></p>

                <!-- Comment Form -->
                <form action="" method="POST">
                    <input type="hidden" name="post_id" value="<?= $post['PostID'] ?>">
                    <label for="comment_content">Add a comment:</label><br>
                    <textarea name="comment_content" id="comment_content" rows="3" required></textarea><br>
                    <button type="submit" class="comment-button">Post Comment</button>
                </form>

                <!-- Display Comments -->
                <div class="comments">
                    <h3>Comments:</h3>
                    <?php if (isset($comments[$post['PostID']])): ?>
                        <?php foreach ($comments[$post['PostID']] as $comment): ?>
                            <div class="comment">

                                <p>
                                    <strong>
                                        <?= htmlspecialchars($comment['AuthorID']) ?>
                                    </strong> 
                                    (<?= htmlspecialchars($comment['CreationDate']) ?>):
                                </p>

                                <p><?= htmlspecialchars($comment['Content']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No comments yet.</p>
                    <?php endif; ?>
                </div>
                
                <?php if ($post['AuthorID'] === $memberID): ?>
                    <form action="./delete-posts.php" method="POST">
                        <button type="submit" class="delete-post">Delete Post</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>