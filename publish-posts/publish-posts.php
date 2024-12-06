<?php
    include '../db-connect.php';

    // Set the directory for uploaded files
    $uploadDir = '../uploads/';

    // Handle post submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $content = $_POST['content'] ?? '';
        $visibility = $_POST['visibility'] ?? 'Group';
        $groups = $_POST['groups'] ?? [];
        $mediaType = null;
        $mediaURL = null;
    
        // Handle file upload if a file is provided
        if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['media']['tmp_name'];
            $fileName = basename($_FILES['media']['name']);
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mov', 'mkv'];
    
            if (in_array($fileExtension, $allowedExtensions)) {
                // Determine the media type based on the file extension
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
    
            // Insert post
            $stmt = $pdo->prepare("
                INSERT INTO Posts (AuthorID, Content, PostDate, VisibilitySettings) 
                VALUES (:author_id, :content, NOW(), :visibility)
            ");
            $stmt->execute([
                ':author_id' => $memberID,
                ':content' => $content,
                ':visibility' => $visibility,
            ]);
            $postID = $pdo->lastInsertId();
    
            // Insert media if a file was uploaded
            if ($mediaType && $mediaURL) {
                $stmt = $pdo->prepare("
                    INSERT INTO PostMedia (PostID, MediaType, MediaURL, UploadedAt) 
                    VALUES (:post_id, :media_type, :media_url, NOW())
                ");
                $stmt->execute([
                    ':post_id' => $postID,
                    ':media_type' => $mediaType,
                    ':media_url' => $mediaURL,
                ]);
            }
    
            // Insert into PostGroups if the post is for specific groups
            if ($visibility === 'Group' && !empty($groups)) {
                $stmt = $pdo->prepare("
                    INSERT INTO PostGroups (PostID, GroupID) 
                    VALUES (:post_id, :group_id)
                ");
                foreach ($groups as $groupID) {
                    $stmt->execute([
                        ':post_id' => $postID,
                        ':group_id' => $groupID
                    ]);
                }
            }
    
            $pdo->commit();
            echo "Post published successfully!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            die("Error publishing post: " . $e->getMessage());
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
    <title>Publish Post</title>
</head>
<body>
    <h1>Publish a New Post</h1>
    <form action="" method="POST" enctype="multipart/form-data">
        <label for="content">Post Content:</label><br>
        <textarea name="content" id="content" rows="5" required></textarea><br><br><br>

        <label for="visibility">Visibility:</label>
        <select name="visibility" id="visibility" onchange="toggleGroupSelection()">
            <option value="Public">Public</option>
            <option value="Group">Group</option>
            <option value="Private">Private</option>
        </select><br><br>

        <div id="groupSelection" style="display: none;">
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

        <label for="media">Upload Media (Image/Video):</label><br>
        <input type="file" name="media" id="media" accept=".jpg,.jpeg,.png,.mp4,.avi,.mov,.mkv"><br><br>

        <button class="publish-button" type="submit">Publish</button>
    </form>

    <script>
        function validateFileSize() {
            const fileInput = document.getElementById('media');
            const file = fileInput.files[0];

            if (file && file.size > 10000 * 1024 * 1024) { // 10000MB in bytes
                alert('File size exceeds the 10000MB limit.');
                fileInput.value = ''; // Clear the input
            }
        }

        // Function to toggle the visibility of the group selection dropdown
        function toggleGroupSelection() {
            var visibility = document.getElementById('visibility').value;
            var groupSelection = document.getElementById('groupSelection');

            // Show the group selection only when "Group" is selected
            if (visibility === 'Group') {
                groupSelection.style.display = 'block';
            } else {
                groupSelection.style.display = 'none';
            }
        }

        // Call the function on page load to set the correct visibility for the group selection
        window.onload = toggleGroupSelection;
    </script>
</body>
</html>