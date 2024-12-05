<?php
    include '../db-connect.php';

    // This is easily refactorable, but I do not have the time anymore.
    // Fetch data for the page after the POST submission
    if (isset($_GET['GroupID'])) {
        $groupID = intval($_GET['GroupID']);

        // Fetch group details to check if the group exists
        $stmt = $pdo->prepare("SELECT * FROM `Groups` WHERE GroupID = :groupID");
        $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
        $stmt->execute();
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if the group exists
        if (!$group) {
            die("Group not found.");
        }

        // Check if the member is in the group
        $stmt = $pdo->prepare("SELECT * FROM GroupMembers WHERE GroupID = :groupID AND MemberID = :memberID");
        $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
        $stmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);
        $stmt->execute();
        $membership = $stmt->fetch(PDO::FETCH_ASSOC);

        // If the member is not part of the group, show an error
        if (!$membership) {
            die("You are not a member of this group.");
        }

        // Get all the MemberIDs in GroupMembers
        $stmt = $pdo->prepare("SELECT MemberID FROM GroupMembers WHERE GroupID = :groupID");
        $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
        $stmt->execute();
        $memberIDArray = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get the details of every Member in the group
        $stmt = $pdo->prepare("
            SELECT m.MemberID, m.Username, m.FirstName, m.LastName, gm.Role 
            FROM Members m
            JOIN GroupMembers gm ON m.MemberID = gm.MemberID
            WHERE gm.GroupID = :groupID
        ");
        $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
        $stmt->execute();
        $groupMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Check if the current session is an admin in the group
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM GroupMembers 
            WHERE MemberID = :memberID AND Role = 'Admin'
        ");
        $stmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);
        $stmt->execute();
        $isAdmin = $stmt->fetchColumn();
    }

    // Check if GroupID is provided
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['GroupID'])) {
        $groupID = intval($_POST['GroupID']);
        
        // Fetch group details to check if the group exists
        $stmt = $pdo->prepare("SELECT * FROM `Groups` WHERE GroupID = :groupID");
        $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
        $stmt->execute();
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if the group exists
        if (!$group) {
            die("Group not found.");
        }

        // Check if the member is in the group
        $stmt = $pdo->prepare("SELECT * FROM GroupMembers WHERE GroupID = :groupID AND MemberID = :memberID");
        $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
        $stmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);
        $stmt->execute();
        $membership = $stmt->fetch(PDO::FETCH_ASSOC);

        // If the member is not part of the group, show an error
        if (!$membership) {
            die("You are not a member of this group.");
        }

        // Get all the MemberIDs in GroupMembers
        $stmt = $pdo->prepare("SELECT MemberID FROM GroupMembers WHERE GroupID = :groupID");
        $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
        $stmt->execute();
        $memberIDArray = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get the details of every Member in the group
        $stmt = $pdo->prepare("
            SELECT m.MemberID, m.Username, m.FirstName, m.LastName, gm.Role 
            FROM Members m
            JOIN GroupMembers gm ON m.MemberID = gm.MemberID
            WHERE gm.GroupID = :groupID
        ");
        $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
        $stmt->execute();
        $groupMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Check if the current session is an admin in the group
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM GroupMembers 
            WHERE MemberID = :memberID AND Role = 'Admin'
        ");
        $stmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);
        $stmt->execute();
        $isAdmin = $stmt->fetchColumn();


        // Handle role update
        if (isset($_POST['MemberID'], $_POST['Role'])) {
            $targetMemberID = intval($_POST['MemberID']);
            $newRole = htmlspecialchars($_POST['Role']);

            // Return true if the member is an admin in any group, false otherwise
            if ($isAdmin) {
                // Ensure the admin does not target himself
                if ($targetMemberID !== $memberID) {
                    $stmt = $pdo->prepare("
                        UPDATE GroupMembers 
                        SET Role = :role 
                        WHERE GroupID = :groupID AND MemberID = :memberID
                    ");
                    $stmt->bindParam(':role', $newRole, PDO::PARAM_STR);
                    $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
                    $stmt->bindParam(':memberID', $targetMemberID, PDO::PARAM_INT);
                    $stmt->execute();

                }
                // Redirect to the same page after processing the form
                header("Location: " . $_SERVER['PHP_SELF'] . "?GroupID=" . intval($_POST['GroupID']));
                exit;
            } else {
                echo "You cannot modify your own role.";
            }
        }
    }

    // Group member removal
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['RemoveMemberID'], $_POST['GroupID'])) {
        $groupID = intval($_POST['GroupID']);
        $removeMemberID = intval($_POST['RemoveMemberID']);
        
        // Check if the current user is an admin in the group
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM GroupMembers 
            WHERE MemberID = :memberID AND GroupID = :groupID AND Role = 'Admin'
        ");
        $stmt->bindParam(':memberID', $_SESSION['MemberID'], PDO::PARAM_INT);
        $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
        $stmt->execute();
        $isAdmin = $stmt->fetchColumn();
        
        if ($isAdmin) {
            // Prevent admin from removing themselves
            if ($removeMemberID === $_SESSION['MemberID']) {
                die("You cannot remove yourself from the group.");
            }

            // Remove the member from the group
            $stmt = $pdo->prepare("
                DELETE FROM GroupMembers 
                WHERE GroupID = :groupID AND MemberID = :memberID
            ");
            $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
            $stmt->bindParam(':memberID', $removeMemberID, PDO::PARAM_INT);
            $stmt->execute();

            // Redirect after successful removal
            header("Location: " . $_SERVER['PHP_SELF'] . "?GroupID=" . $groupID);
            exit;
        } else {
            die("You do not have permission to remove members.");
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../styles.css?<?php echo time(); ?>" rel="stylesheet"/>
    <title>View Group Members</title>
</head>
<body>

    <h1>View Group Members</h1>

    <?php if ($privilege === 'Junior' || $privilege === 'Senior' || $privilege === 'Administrator'): ?>
        <!-- Display the Group's Name -->
        <h2>Group Name: <?php echo htmlspecialchars($group['GroupName']); ?></h2>

        <!-- Display Member Details -->
        <div class="member-details">
            <div class="member-details-head">
                <div class="head-item">Member ID</div>
                <div class="head-item">Username</div>
                <div class="head-item">First Name</div>
                <div class="head-item">Last Name</div>
                <div class="head-item">Role</div>
                <div class="head-item">Role Action</div>
                <div class="head-item">Member Action</div>
            </div>
            <?php foreach ($groupMembers as $member): ?>
                <div class="member-details-body">
                    <div class="body-item"><?php echo htmlspecialchars($member['MemberID']); ?></div>
                    <div class="body-item"><?php echo htmlspecialchars($member['Username']); ?></div>
                    <div class="body-item"><?php echo htmlspecialchars($member['FirstName']); ?></div>
                    <div class="body-item"><?php echo htmlspecialchars($member['LastName']); ?></div>
                    <div class="body-item"><?php echo htmlspecialchars($member['Role']); ?></div>

                    <!-- Display form only for other members -->
                    <!-- Case 1: Other member, but not group admin -->
                    <?php if ($member['MemberID'] !== $_SESSION['MemberID'] && !$isAdmin): ?>
                        <div class="body-item">
                            <form method="POST" action="">
                                <input type="hidden" name="GroupID" value="<?php echo htmlspecialchars($groupID); ?>">
                                <input type="hidden" name="MemberID" value="<?php echo htmlspecialchars($member['MemberID']); ?>">
                                
                                <label for="Role_<?php echo htmlspecialchars($member['MemberID']); ?>">Role:</label>
                                <select name="Role" id="Role_<?php echo htmlspecialchars($member['MemberID']); ?>">
                                    <option value="Member" <?php echo $member['Role'] === 'Member' ? 'selected' : ''; ?>>Member</option>
                                    <option value="Admin" <?php echo $member['Role'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                                <button type="submit">Update</button>
                            </form>
                        </div>
                    <!-- Case 2: Other member, but group admin -->
                    <?php elseif ($member['MemberID'] !== $_SESSION['MemberID'] && $isAdmin): ?>
                        <div class="body-item">
                            <form method="POST" action="">
                                <input type="hidden" name="GroupID" value="<?php echo htmlspecialchars($groupID); ?>">
                                <input type="hidden" name="MemberID" value="<?php echo htmlspecialchars($member['MemberID']); ?>">
                                
                                <label for="Role_<?php echo htmlspecialchars($member['MemberID']); ?>">Role:</label>
                                <select name="Role" id="Role_<?php echo htmlspecialchars($member['MemberID']); ?>">
                                    <option value="Member" <?php echo $member['Role'] === 'Member' ? 'selected' : ''; ?>>Member</option>
                                    <option value="Admin" <?php echo $member['Role'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                                <button type="submit">Update</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="body-item">
                            <p>No action</p>
                        </div>
                    <?php endif; ?>

                    
                    <!-- Display a remove member button only for admins -->
                    <div class="body-item">
                        <?php if ($isAdmin && $member['MemberID'] !== $_SESSION['MemberID']): ?>
                            <form method="POST" action="">
                                <input type="hidden" name="GroupID" value="<?php echo htmlspecialchars($groupID); ?>">
                                <input type="hidden" name="RemoveMemberID" value="<?php echo htmlspecialchars($member['MemberID']); ?>">
                                <button type="submit" class="remove-button">Remove</button>
                            </form>
                        <?php else: ?>
                            <p>No action</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>You do not have permission to view this page.</p>
    <?php endif; ?>

</body>
</html>
