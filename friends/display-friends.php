<?php
    include '../db-connect.php';

    // Fetch all relationships sent by the session's MemberID
    $stmt = $pdo->prepare("SELECT * FROM `Relationships` 
                        WHERE SenderMemberID = :memberID OR ReceiverMemberID = :memberID");
    $stmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);
    $stmt->execute();
    $relationships = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $requesterUsernames = [];
    $senderUsernames = [];
    if ($relationships) {
        foreach ($relationships as $relationship) {
            // Determine the requester (the other member in the relationship)
            $requesterID = $relationship['SenderMemberID'];
            
            // Fetch username for the requester
            $userStmt = $pdo->prepare("SELECT Username FROM Members WHERE MemberID = :requesterID");
            $userStmt->bindParam(':requesterID', $requesterID, PDO::PARAM_INT);
            $userStmt->execute();
            $userResult = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            $requesterUsernames[$relationship['RelationshipID']] = $userResult ? $userResult['Username'] : 'Unknown';

            // Determine the sender
            $requesterID = $relationship['ReceiverMemberID'];
            
            // Fetch username for the sender
            $userStmt = $pdo->prepare("SELECT Username FROM Members WHERE MemberID = :requesterID");
            $userStmt->bindParam(':requesterID', $requesterID, PDO::PARAM_INT);
            $userStmt->execute();
            $userResult = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            $senderUsernames[$relationship['RelationshipID']] = $userResult ? $userResult['Username'] : 'Unknown';
        }
    }

    // Handle approve or reject actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['relationship_id'])) {
        $relationshipID = intval($_POST['relationship_id']);
        $action = $_POST['action'];

        if ($action === 'update_relationship_type' && isset($_POST['relationship_type'])) {
            $newRelationshipType = $_POST['relationship_type'];
    
            // Update the relationship type in the database
            $updateStmt = $pdo->prepare("UPDATE Relationships SET RelationshipType = :relationshipType WHERE RelationshipID = :relationshipID");
            $updateStmt->bindParam(':relationshipType', $newRelationshipType, PDO::PARAM_STR);
            $updateStmt->bindParam(':relationshipID', $relationshipID, PDO::PARAM_INT);
            $updateStmt->execute();
    
            $message = "Relationship type updated successfully. " . "[ " . $newRelationshipType . " ]";
        } elseif ($action === 'approve') {
            // Approve the friend request
            $updateStmt = $pdo->prepare("UPDATE Relationships SET Status = 'Active' WHERE RelationshipID = :relationshipID");
            $updateStmt->bindParam(':relationshipID', $relationshipID, PDO::PARAM_INT);
            $updateStmt->execute();
            $message = "Friend request approved.";
        } elseif ($action === 'reject') {
            // Reject the friend request
            $deleteStmt = $pdo->prepare("DELETE FROM Relationships WHERE RelationshipID = :relationshipID");
            $deleteStmt->bindParam(':relationshipID', $relationshipID, PDO::PARAM_INT);
            $deleteStmt->execute();
            $message = "Friend request rejected.";
        } else {
            $message = "Invalid action.";
        }

        // Redirect to avoid form resubmission
        header("Location: ./display-friends.php?message=" . urlencode($message));
        exit;
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
    <?php if (isset($_GET['message'])): ?>
            <p class="success-message"><?= htmlspecialchars($_GET['message']); ?></p>
    <?php endif; ?>

    <div class="container-2-horizontal">
        <div class="display-friends">
            <h1>Display Friends</h1>

            <!-- 
                Print ONLY the relationships that are Active for the Session's MemberID 
                We're going to reuse the styling from view-members-groups.php
            -->
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Status</th>
                        <th>Request Date</th>
                        <th>Relationship Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($relationships as $relationship): ?>
                    <tr>
                    <?php if ($relationship['Status'] === 'Active' && 
                             ($relationship['ReceiverMemberID'] === $memberID || $relationship['SenderMemberID'] === $memberID)): ?>

                        <?php if ($relationship['SenderMemberID'] === $memberID): ?>
                            <td><?= htmlspecialchars($senderUsernames[$relationship['RelationshipID']] ?? 'Unknown') ?></td>
                        <?php endif; ?>

                        <?php if ($relationship['ReceiverMemberID'] === $memberID): ?>
                            <td><?= htmlspecialchars($requesterUsernames[$relationship['RelationshipID']] ?? 'Unknown') ?></td>
                        <?php endif; ?>
                            
                        <td>Status</td>
                        
                        <td><?= htmlspecialchars($relationship['CreationDate']) ?></td>

                        <!-- Relationship Type dropdown -->
                        <td>
                            <form action="./display-friends.php" method="POST">
                                <input type="hidden" name="relationship_id" value="<?= htmlspecialchars($relationship['RelationshipID']); ?>">

                                <select name="relationship_type">
                                    <option value="Family" <?= $relationship['RelationshipType'] === 'Family' ? 'selected' : '' ?>>Family</option>
                                    <option value="Friend" <?= $relationship['RelationshipType'] === 'Friend' ? 'selected' : '' ?>>Friend</option>
                                    <option value="Colleague" <?= $relationship['RelationshipType'] === 'Colleague' ? 'selected' : '' ?>>Colleague</option>
                                </select>

                                <button type="submit" name="action" value="update_relationship_type">Update Type</button>
                            </form>
                        </td>

                        <!-- Approve and Reject buttons -->
                        <td>
                            <form action="./display-friends.php" method="POST">
                                <input type="hidden" name="relationship_id" value="<?= htmlspecialchars($relationship['RelationshipID']); ?>">
                                <button type="submit" name="action" value="reject" class="remove-relationship">Remove</button>
                            </form>
                        </td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- This is where we should see Friend requests -->
        <div class="friend-requests">
        <h2>Friend Requests</h2>
        <?php if (!empty($relationships)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Request Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($relationships as $relationship): ?>
                        <?php if ($relationship['Status'] === 'Pending' && $relationship['ReceiverMemberID'] === $memberID): ?>
                            <tr>
                                <td><?= htmlspecialchars($requesterUsernames[$relationship['RelationshipID']] ?? 'Unknown') ?></td>
                                <td><?= htmlspecialchars($relationship['CreationDate']) ?></td>
                                <td>
                                    <!-- Approve and Reject buttons -->
                                    <form action="./display-friends.php" method="POST">
                                        <input type="hidden" name="relationship_id" value="<?= htmlspecialchars($relationship['RelationshipID']); ?>">
                                        <button type="submit" name="action" value="approve" class="approve-relationship">Approve</button>
                                        <button type="submit" name="action" value="reject" class="remove-relationship">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No pending friend requests.</p>
        <?php endif; ?>
    </div>

    <script>
        // Add confirmation popup for the approve buttons
        document.querySelectorAll('.approve-relationship').forEach((button) => {
            button.addEventListener('click', (event) => {
                const confirmed = confirm("Do you want to Approve this Relationship? This action cannot be undone.");
                if (!confirmed) {
                    // Prevent form submission if the user cancels
                    event.preventDefault();
                }
            });
        });

        // Add confirmation popup for the remove/reject buttons
        document.querySelectorAll('.remove-relationship').forEach((button) => {
            button.addEventListener('click', (event) => {
                const confirmed = confirm("Do you want to Remove/Reject this Relationship? This action cannot be undone.");
                if (!confirmed) {
                    // Prevent form submission if the user cancels
                    event.preventDefault();
                }
            });
        });
    </script>
</body>
</html>