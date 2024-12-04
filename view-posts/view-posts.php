<?php
    include '../db-connect.php';

    if (isset($_GET['message'])) {
        echo "<p style='color: green; font-size: 20px;'>Message: " . htmlspecialchars($_GET['message']) . "</p>";
    }

    if (isset($_GET['error'])) {
        echo "<p style='color: red; font-size: 20px;'>Error: " . htmlspecialchars($_GET['error']) . "</p>";
    }

    // Fetch posts from the database
    try {
        $stmt = $pdo->prepare("
            SELECT p.PostID, p.AuthorID, p.Content, p.PostDate, 
                (SELECT COUNT(*) FROM PostLikes pl WHERE pl.PostID = p.PostID) AS Likes,
                (SELECT COUNT(*) FROM PostDislikes pd WHERE pd.PostID = p.PostID) AS Dislikes, 
                p.CommentsCount, p.VisibilitySettings, m.MediaType, m.MediaURL
            FROM Posts p
            LEFT JOIN PostMedia m ON p.PostID = m.PostID
            ORDER BY p.PostDate ASC
        ");
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching posts or comments: " . $e->getMessage());
    }

    include "filter-posts.php";
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
            <label for="postFrom">From:</label>
            <select name="postFrom" id="postFrom">
                <option value="Others" <?= $filterFrom === 'Others' ? 'selected' : '' ?>>Others</option>
                <option value="You" <?= $filterFrom === 'You' ? 'selected' : '' ?>>You</option>
            </select>

            <label for="postTime">Time:</label>
            <select name="postTime" id="postTime">
                <option value="Oldest" <?= $filterTime === 'Oldest' ? 'selected' : '' ?>>Oldest</option>
                <option value="Newest" <?= $filterTime === 'Newest' ? 'selected' : '' ?>>Newest</option>
            </select>

            <label for="postType">Post Type:</label>
            <select name="postType" id="postType">
                <option value="Public" <?= $filterType === 'Public' ? 'selected' : '' ?>>Public</option>
                <option value="Group" <?= $filterType === 'Group' ? 'selected' : '' ?>>Group</option>
                <option value="Private" <?= $filterType === 'Private' ? 'selected' : '' ?>>Private</option>
            </select>

            <label for="postMetrics">Post Metrics:</label>
            <select name="postMetrics" id="postMetrics">
                <option value="None" <?= $filterMetrics === 'None' ? 'selected' : '' ?>>No filter</option>
                <option value="Most Likes" <?= $filterMetrics === 'Most Likes' ? 'selected' : '' ?>>Most Likes</option>
                <option value="Most Comments" <?= $filterMetrics === 'Most Comments' ? 'selected' : '' ?>>Most Comments</option>
            </select>

            <button type="submit">Apply Filters</button>
        </form>

        <!-- Display Posts -->
        <?php if (empty($_GET)): ?>
            <p>Please apply filters to view posts.</p>
        <?php elseif (empty($posts)): ?>
            <p>No posts found for the selected filters.</p>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="post">
                    <p>
                        <strong>
                        <?php
                            // Fetch Member's username based on the AuthorID of the Post
                            $stmt = $pdo->prepare("SELECT * FROM Members WHERE MemberID = :memberID");
                            $stmt->execute([ ':memberID' => $post['AuthorID'] ]);
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

                    <p>
                        <strong>Likes:</strong> <?= $post['Likes'] ?> | <strong>Dislikes:</strong> <?= $post['Dislikes'] ?>
                    </p>
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

                    <p><strong>Comments:</strong> <?= $post['CommentsCount'] ?> | 
                        <a href="inspect-single-post.php?post_id=<?= htmlspecialchars($post['PostID']) ?>">View Comments</a>
                    </p>

                    <?php if ($post['AuthorID'] === $memberID): ?>
                        <form action="./delete-posts.php" method="GET">
                            <button type="submit" class="delete-post" name="DeletePostID" value="<?= $post['PostID'] ?>">Delete Post</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>