<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //
    
    include '../db-connect.php';

    // Set the directory for uploaded files
    $uploadDir = '../uploads/';

    // Get post ID from query parameter
    $postID = $_GET['EditPostID'] ?? null;
    if (!$postID) {
        die("Invalid request. Post ID is missing.");
    }

    // Fetch the post details
    try {
        $stmt = $pdo->prepare("
            SELECT p.PostID, p.AuthorID, p.Content, p.VisibilitySettings, m.MediaType, m.MediaURL
            FROM Posts p
            LEFT JOIN PostMedia m ON p.PostID = m.PostID
            WHERE p.PostID = :post_id
        ");
        $stmt->execute([':post_id' => $postID]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            die("Post not found.");
        }

        // Ensure the current user is the author of the post
        if ($post['AuthorID'] !== $memberID) {
            die("Unauthorized access.");
        }
    } catch (PDOException $e) {
        die("Error fetching post: " . $e->getMessage());
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $content = $_POST['content'] ?? '';
        $visibility = $_POST['visibility'] ?? 'Group';
        $groups = $_POST['groups'] ?? [];
        $mediaType = $post['MediaType'];
        $mediaURL = $post['MediaURL'];

        // Handle file upload if a new file is provided
        if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['media']['tmp_name'];
            $fileName = basename($_FILES['media']['name']);
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mov', 'mkv'];

            if (in_array($fileExtension, $allowedExtensions)) {
                $mediaType = in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif']) ? 'Image' : 'Video';

                // Generate a unique file name
                $newFileName = uniqid('media_', true) . '.' . $fileExtension;
                $mediaURL = $uploadDir . $newFileName;

                // Move the uploaded file to the uploads directory
                if (!move_uploaded_file($fileTmpPath, $mediaURL)) {
                    die("Error moving uploaded file.");
                }
            } else {
                die("Invalid file type. Allowed types: " . implode(', ', $allowedExtensions));
            }
        }

        try {
            $pdo->beginTransaction();

            // Update the post
            $stmt = $pdo->prepare("
                UPDATE Posts 
                SET Content = :content, VisibilitySettings = :visibility 
                WHERE PostID = :post_id
            ");
            $stmt->execute([
                ':content' => $content,
                ':visibility' => $visibility,
                ':post_id' => $postID,
            ]);

            // Update media if a new file was uploaded
            if ($mediaType && $mediaURL !== $post['MediaURL']) {
                // Delete old media file if it exists
                if ($post['MediaURL'] && file_exists($post['MediaURL'])) {
                    unlink($post['MediaURL']);
                }

                $stmt = $pdo->prepare("
                    INSERT INTO PostMedia (PostID, MediaType, MediaURL, UploadedAt) 
                    VALUES (:post_id, :media_type, :media_url, NOW())
                    ON DUPLICATE KEY UPDATE MediaType = :media_type, MediaURL = :media_url
                ");
                $stmt->execute([
                    ':post_id' => $postID,
                    ':media_type' => $mediaType,
                    ':media_url' => $mediaURL,
                ]);
            }

            // Update PostGroups if visibility is 'Group'
            if ($visibility === 'Group') {
                $stmt = $pdo->prepare("DELETE FROM PostGroups WHERE PostID = :post_id");
                $stmt->execute([':post_id' => $postID]);

                $stmt = $pdo->prepare("
                    INSERT INTO PostGroups (PostID, GroupID) 
                    VALUES (:post_id, :group_id)
                ");
                foreach ($groups as $groupID) {
                    $stmt->execute([
                        ':post_id' => $postID,
                        ':group_id' => $groupID,
                    ]);
                }
            }

            $pdo->commit();
            echo "Post updated successfully!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            die("Error updating post: " . $e->getMessage());
        }
    }

    // Fetch groups where the user is an admin
    $stmt = $pdo->prepare("
        SELECT g.GroupID, g.GroupName 
        FROM `Groups` g
        INNER JOIN `GroupMembers` gm ON g.GroupID = gm.GroupID
        WHERE gm.MemberID = :memberID AND gm.Role = 'Admin'
    ");
    $stmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);
    $stmt->execute();

    // Fetch all admin groups
    $adminGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../styles.css?<?php echo time(); ?>" rel="stylesheet">
    <title>Edit Post</title>
</head>
<body>
    <h1>Edit Post</h1>
    <form action="" method="POST" enctype="multipart/form-data">
        <label for="content">Post Content:</label><br>
        <textarea name="content" id="content" rows="5" required><?= htmlspecialchars($post['Content']) ?></textarea><br><br>

        <label for="visibility">Visibility:</label>
        <select name="visibility" id="visibility" onchange="toggleGroupSelection()">
            <option value="Public" <?= $post['VisibilitySettings'] === 'Public' ? 'selected' : '' ?>>Public</option>
            <option value="Group" <?= $post['VisibilitySettings'] === 'Group' ? 'selected' : '' ?>>Group</option>
            <option value="Private" <?= $post['VisibilitySettings'] === 'Private' ? 'selected' : '' ?>>Private</option>
        </select><br><br>

        <div id="groupSelection" style="display: <?= $post['VisibilitySettings'] === 'Group' ? 'block' : 'none' ?>;">
            <label for="groups">Select Groups:</label>
            <select name="groups[]" id="groups" multiple>
                <!-- Populate dynamically with groups where the user is an admin -->
                <?php foreach ($adminGroups as $group): ?>
                    <option value="<?php echo htmlspecialchars($group['GroupID']); ?>">
                        <?php echo htmlspecialchars($group['GroupName']); ?>
                    </option>
                <?php endforeach; ?>
            </select><br><br>
        </div>

        <label for="media">Upload New Media (Optional):</label><br>
        <input type="file" name="media" id="media" accept=".jpg,.jpeg,.png,.mp4,.avi,.mov,.mkv"><br><br>

        <?php if ($post['MediaType'] && $post['MediaURL']): ?>
            <p>Current Media: <?= htmlspecialchars($post['MediaURL']) ?></p>
        <?php endif; ?>

        <button class="edit-button" type="submit">Save Changes</button>
    </form>

    <script>
        function toggleGroupSelection() {
            var visibility = document.getElementById('visibility').value;
            var groupSelection = document.getElementById('groupSelection');

            if (visibility === 'Group') {
                groupSelection.style.display = 'block';
            } else {
                groupSelection.style.display = 'none';
            }
        }

        window.onload = toggleGroupSelection;
    </script>
</body>
</html>
