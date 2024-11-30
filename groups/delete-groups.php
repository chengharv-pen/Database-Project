<?php
    // Start session
    session_start();

    // Check if user is authorized
    if (!isset($_SESSION['MemberID']) || !isset($_SESSION['Privilege'])) {
        die("Access denied. Please log in.");
    }

    // Database connection
    $host = "localhost"; // Change if using a different host
    $dbname = "db-schema";
    $username = "root";
    $password = "";

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }

    // GET method from display-groups.php
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['GroupID'])) {
        // Display confirmation page
        $groupID = intval($_GET['GroupID']);

        // Fetch group details
        $stmt = $pdo->prepare("
            SELECT GroupName, OwnerID 
            FROM `Groups` 
            WHERE GroupID = :groupID
        ");
        $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
        $stmt->execute();
        $group = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$group) {
            die("Group not found.");
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
    <link href="../styles.css" rel="stylesheet"/>
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