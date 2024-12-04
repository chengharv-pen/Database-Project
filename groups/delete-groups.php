<?php
    include '../db-connect.php';

    // GET method from display-groups.php
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['GroupID'])) {
        // Display confirmation page
        $groupID = intval($_GET['GroupID']);

        // Fetch group details along with the role of the current user
        $stmt = $pdo->prepare("
            SELECT g.GroupName, g.OwnerID, gm.Role 
            FROM `Groups` g
            LEFT JOIN GroupMembers gm ON g.GroupID = gm.GroupID 
            WHERE g.GroupID = :groupID AND gm.MemberID = :memberID
        ");
        $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
        $stmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);
        $stmt->execute();
        $group = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$group) {
            die("Group not found or you are not a member of this group.");
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_group'])) {
        
        // Handle deletion
        if (!isset($_POST['GroupID']) || empty($_POST['GroupID'])) {
            die("Invalid request. GroupID is required.");
        }

        $groupID = intval($_POST['GroupID']);

        try {
            // Fetch group details again for double-check
            $stmt = $pdo->prepare("
                SELECT OwnerID 
                FROM `Groups` 
                WHERE GroupID = :groupID
            ");
            $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
            $stmt->execute();
            $group = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$group) {
                die("Group not found.");
            }

            // Delete the group
            $deleteStmt = $pdo->prepare("DELETE FROM `Groups` WHERE GroupID = :groupID");
            $deleteStmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
            $deleteStmt->execute();

            // Redirect to display-groups.php with a success message
            header("Location: ./display-groups.php?message=The Group has been deleted!");
            exit;

        } catch (PDOException $e) {
            die("Error deleting group: " . $e->getMessage());
        }
    } else {
        die("Invalid request.");
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../styles.css?<?php echo time(); ?>" rel="stylesheet"/>
</head>
<body>

    <!-- This should only be accessible to Senior/Admin Member AND Group Admin -->
    <?php if ($group['Role'] === 'Admin' && $privilege !== 'Junior'): ?>
        <h1> Delete Groups </h1>
        
        <h2 style="color: red;">WARNING: THIS ACTION WILL NOT BE REVERSIBLE. PLEASE PROCEED WITH CAUTION</h2>
            
        <!-- Form to trigger account deletion -->
        <form method="POST">
            <button type="submit" name="delete_group" class="delete-button">Delete the Group <?php echo htmlspecialchars($group['GroupName']); ?></button>
        </form>
    <?php else: ?>
        <p>You do not have the necessary privileges to delete this group.</p>
    <?php endif; ?>

    <script>
        // Add confirmation popup for the delete button
        document.querySelector('.delete-button').addEventListener('click', (event) => {
            const confirmed = confirm("Are you sure you want to delete this Group? This action cannot be undone.");
            if (!confirmed) {
                // Prevent form submission if the user cancels
                event.preventDefault();
            }
        });
    </script>
</body>
</html>