<?php
    include '../db-connect.php';

    // Fetch posts from the database
    try {
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

    // Initialize variables for filters
    $filterFrom = $_GET['postFrom'] ?? null;
    $filterTime = $_GET['postTime'] ?? 'Oldest';
    $filterType = $_GET['postType'] ?? null;
    $filterMetrics = $_GET['postMetrics'] ?? null;

    if (!empty($_GET)) {
        // Build the query only if filters are applied
        $query = "
            SELECT p.PostID, p.AuthorID, p.Content, p.PostDate, p.Likes, p.Dislikes, 
                p.CommentsCount, p.VisibilitySettings, m.MediaType, m.MediaURL
            FROM Posts p
            LEFT JOIN PostMedia m ON p.PostID = m.PostID
            LEFT JOIN BlockedMembers b ON p.AuthorID = b.BlockedID AND b.BlockerID = :currentUserID
            WHERE b.BlockedID IS NULL
        ";

        // Add filters to the query
        $params = [':currentUserID' => $memberID]; // Current user's ID

        // Filter by "From" (e.g., posts by others or the user)
        if ($filterFrom === 'You') {
            $query .= " AND p.AuthorID = :memberID";
            $params[':memberID'] = $memberID;
        } elseif ($filterFrom === 'Others') {
            $query .= " AND p.AuthorID <> :memberID";
            $params[':memberID'] = $memberID;
        }

        // Filter by visibility type
        if ($filterType) {
            if ($filterType === 'Private') {
                $query .= "
                    AND p.VisibilitySettings = 'Private'
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
                ";
            } elseif ($filterType === 'Group') {
                $query .= "
                    AND p.VisibilitySettings = 'Group'
                    AND EXISTS (
                        SELECT 1 
                        FROM PostGroups pg
                        JOIN GroupMembers gm ON pg.GroupID = gm.GroupID
                        WHERE pg.PostID = p.PostID AND gm.MemberID = :currentUserID
                    )
                ";
            } else {
                $query .= " AND p.VisibilitySettings = :visibility";
                $params[':visibility'] = $filterType;
            }
        }

        // Order by time
        if ($filterTime === 'Newest') {
            $query .= " ORDER BY p.PostDate DESC";
        } else {
            $query .= " ORDER BY p.PostDate ASC";
        }

        // Apply metrics filter
        if ($filterMetrics === 'Most Likes') {
            $query = str_replace("ORDER BY p.PostDate", "ORDER BY p.Likes DESC, p.PostDate", $query);
        } elseif ($filterMetrics === 'Most Comments') {
            $query = str_replace("ORDER BY p.PostDate", "ORDER BY p.CommentsCount DESC, p.PostDate", $query);
        }

        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Error fetching posts: " . $e->getMessage());
        }
    }

    include './comments.php';
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
                    

                    <!-- Dropdown GET form to let the user display or not display Comments -->
                    <form method="GET" action="">
                        <label for="view">Select Comment View:</label>
                        <select name="view" id="view" onchange="this.form.submit()">
                            <option value="none" <?= isset($_GET['view']) && $_GET['view'] === 'none' ? 'selected' : '' ?>>None</option>
                            <option value="all" <?= isset($_GET['view']) && $_GET['view'] === 'all' ? 'selected' : '' ?>>All</option>
                        </select>
                    </form>

                    <!-- Display Comments -->
                    <div class="comments" style="display: <?= $view === 'all' ? 'block' : 'none' ?>;">
                        <h3>Comments:</h3>
                        <?php if (isset($comments[$post['PostID']])): ?>
                            <?php foreach ($comments[$post['PostID']] as $comment): ?>

                                <div class="comment">

                                    <?php 
                                        // Fetch the Author's name based on the AuthorID of the comment
                                        $commentSQL = "SELECT Username FROM Members WHERE MemberID = :commentID";
                                        $stmt = $pdo->prepare($commentSQL);
                                        $stmt->bindParam(':commentID', $comment['AuthorID'], PDO::PARAM_INT);
                                        $stmt->execute();
                                        $commenter = $stmt->fetch(PDO::FETCH_ASSOC);
                                    ?>

                                    <p><strong><?= htmlspecialchars($commenter['Username']) ?></strong> (<?= htmlspecialchars($comment['CreationDate']) ?>):</p>

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
        <?php endif; ?>
    </div>
</body>
</html>