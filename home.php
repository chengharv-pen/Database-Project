<?php
    include './db-connect.php';

    if (isset($_GET['message'])) {
        echo "<p style='color: green; font-size: 20px;'>Message: " . htmlspecialchars($_GET['message']) . "</p>";
    }

    if (isset($_GET['error'])) {
        echo "<p style='color: red; font-size: 20px;'>Error: " . htmlspecialchars($_GET['error']) . "</p>";
    }

    if ($privilege === 'Administrator') {
        echo "<a href='./warnings/admin-dashboard.php'>Admin Dashboard</a>";
    }

    // Check the Member's Account type. If it is Business, then show a link to payments.php
    try {
        $stmt = $pdo->prepare("
            SELECT AccountType FROM Members WHERE MemberID = :memberID
        ");
        $stmt->execute([':memberID' => $memberID]);
        $accountType = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($accountType['AccountType'] === 'Business') {
            echo "<a href='./warnings/payments.php'>Payments</a>";
        }

    } catch (PDOException $e) {
        die("Error fetching posts or comments: " . $e->getMessage());
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

    // Initialize variables for filters
    $filterFrom = $_GET['postFrom'] ?? null;
    $filterTime = $_GET['postTime'] ?? 'Oldest';
    $filterType = $_GET['postType'] ?? null;
    $filterMetrics = $_GET['postMetrics'] ?? null;

    try {
        // Start with the base query
        $query = "
            SELECT p.PostID, p.AuthorID, p.Content, p.PostDate, 
                (SELECT COUNT(*) FROM PostLikes pl WHERE pl.PostID = p.PostID) AS Likes,
                (SELECT COUNT(*) FROM PostDislikes pd WHERE pd.PostID = p.PostID) AS Dislikes, 
                p.CommentsCount, p.VisibilitySettings, m.MediaType, m.MediaURL
            FROM Posts p
            LEFT JOIN PostMedia m ON p.PostID = m.PostID
            LEFT JOIN BlockedMembers b ON p.AuthorID = b.BlockedID AND b.BlockerID = :currentUserID
            WHERE b.BlockedID IS NULL 
                AND (p.AuthorID = :memberID OR p.AuthorID <> :memberID)
                OR p.VisibilitySettings = 'Private'
                AND (
                        p.authorID = :currentUserID 
                        OR EXISTS (
                            SELECT 1 
                            FROM Relationships r 
                            WHERE 
                                ((r.SenderMemberID = p.AuthorID AND r.ReceiverMemberID = :currentUserID)
                                OR (r.SenderMemberID = :currentUserID AND r.ReceiverMemberID = p.AuthorID))
                                AND r.Status = 'Active'
                        )
                    )
                OR p.VisibilitySettings = 'Group'
                    AND EXISTS (
                        SELECT 1 
                        FROM PostGroups pg
                        JOIN GroupMembers gm ON pg.GroupID = gm.GroupID
                        WHERE pg.PostID = p.PostID AND gm.MemberID = :currentUserID
                    )
                OR p.VisibilitySettings = :visibility
                ORDER BY Likes DESC, p.PostDate
        ";

        // Add filters to the query
        $params = [':currentUserID' => $memberID]; // Current user's ID
        $params[':memberID'] = $memberID;
        $params[':visibility'] = 'Public';

        // Execute the query
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
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
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="./styles.css?<?php echo time(); ?>" rel="stylesheet">
</head>
<body>
    <div class="posts">
        <h1>Home</h1>

        <!-- Display Posts -->
        <?php if (empty($posts)): ?>
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
                                    <form action="./view-posts/like-posts.php" method="POST">
                                        <input type="hidden" name="post_id" value="<?= $post['PostID'] ?>">
                                        <button type="submit">Remove Like</button>
                                    </form>
                                </div>
                            <?php elseif (!$userLiked && !$userDisliked): ?>
                                <!-- User hasn't liked or disliked the post, so show both "Like" and "Dislike" -->
                                <div class="like-button">
                                    <form action="./view-posts/like-posts.php" method="POST">
                                        <input type="hidden" name="post_id" value="<?= $post['PostID'] ?>">
                                        <button type="submit">Like</button>
                                    </form>
                                </div>
                                <div class="dislike-button">
                                    <form action="./view-posts/dislike-posts.php" method="POST">
                                        <input type="hidden" name="post_id" value="<?= $post['PostID'] ?>">
                                        <button type="submit">Dislike</button>
                                    </form>
                                </div>
                            <?php elseif (!$userLiked && $userDisliked): ?>
                                <!-- User has disliked the post, so show "Remove Dislike" -->
                                <div class="./view-posts/dislike-button">
                                    <form action="dislike-posts.php" method="POST">
                                        <input type="hidden" name="post_id" value="<?= $post['PostID'] ?>">
                                        <button type="submit">Remove Dislike</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                    </div>

                    <p><strong>Comments:</strong> <?= $post['CommentsCount'] ?> | 
                        <a href="./view-posts/inspect-single-post.php?post_id=<?= htmlspecialchars($post['PostID']) ?>">View Comments</a>
                    </p>

                    <?php if ($post['AuthorID'] === $memberID): ?>
                        <button type="submit" class="edit-post" name="EditPostID" value="<?= $post['PostID'] ?>"
                        onclick="window.location.href='../publish-posts/edit-posts.php?EditPostID=<?= $post['PostID'] ?>';">
                        Edit Post
                        </button>
                        <p>---------------------------------------------</p>
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