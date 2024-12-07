<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //
    
    include '../db-connect.php';

    // A variable that gives feedback for accept/reject join requests
    $feedback = "";

    // Handle GET request to fetch group details
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['GroupID'])) {
        $groupID = intval($_GET['GroupID']);

        // Fetch group details along with the role of the current user
        $stmt = $pdo->prepare("
            SELECT g.GroupName, g.OwnerID, gm.Role, g.GroupID
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

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_group'])) {

        // Ensure GroupID is present for editing
        if (!isset($_POST['GroupID']) || empty($_POST['GroupID'])) {
            die("Invalid request. GroupID is required.");
        }

        $groupID = intval($_POST['GroupID']);
        
        // Fetch group details to ensure the group exists and belongs to the correct member
        $stmt = $pdo->prepare("SELECT GroupName, OwnerID FROM `Groups` WHERE GroupID = :groupID");
        $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
        $stmt->execute();
        $group = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$group) {
            die("Group not found.");
        }

        // Initialize an array for updates
        $updates = [];
        $params = [];
    
        // List of possible fields to update
        $fields = [
            'group_name' => 'GroupName',
            'interest_category' => 'InterestCategory',
            'region' => 'Region',
            'group_type' => 'GroupType',
        ];
    
        // Loop through fields to collect updates
        foreach ($fields as $formField => $dbField) {
            if (!empty($_POST[$formField])) {
                $updates[] = "$dbField = :$formField";
                $params[":$formField"] = $_POST[$formField];
            }
        }
    
        // Check if there are updates
        if (!empty($updates)) {
            // Add the GroupID condition
            $updateSQL = "UPDATE `Groups` SET " . implode(", ", $updates) . " WHERE GroupID = :groupID";
            $params[':groupID'] = $groupID;
    
            try {
                $stmt = $pdo->prepare($updateSQL);
                $stmt->execute($params);
                header("Location: ./display-groups.php?message=Group edited successfully!");
            } catch (PDOException $e) {
                die("Error updating Group: " . $e->getMessage());
            }
        } else {
            header("Location: ./display-groups.php?message=No changes to Group.");
        }
    }
    
    // Only show join requests if the current user is the group owner or admin
    // Fetch pending join requests for the group
    $stmt = $pdo->prepare("SELECT RequestID, MemberID, RequestDate FROM GroupJoinRequests WHERE GroupID = :groupID AND Status = 'Pending'");
    $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle approval or rejection of a join request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['request_id'])) {
        $requestID = intval($_POST['request_id']);
        $action = $_POST['action']; // Either 'approve' or 'reject'
        $groupID = intval($_POST['group_id']); // Ensure group_id is passed via POST
    
        if (!$groupID || !$requestID || !$action) {
            die("Invalid request. Missing required fields.");
        }
    
        if ($action === 'approve' || $action === 'reject') {
            // Fetch MemberID associated with the request
            $stmt = $pdo->prepare("
                SELECT MemberID 
                FROM GroupJoinRequests 
                WHERE RequestID = :requestID AND GroupID = :groupID AND Status = 'Pending'
            ");
            $stmt->bindParam(':requestID', $requestID, PDO::PARAM_INT);
            $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if (!$result || !isset($result['MemberID'])) {
                die("Invalid join request or no MemberID found.");
            }
    
            $requesterMemberID = $result['MemberID'];
    
            // Update the status of the join request
            $status = ($action === 'approve') ? 'Approved' : 'Rejected';
            $stmt = $pdo->prepare("
                UPDATE GroupJoinRequests 
                SET Status = :status, ReviewedBy = :memberID, ReviewDate = (CURDATE()), ReviewComments = 'Sample Text' 
                WHERE RequestID = :requestID
            ");
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':requestID', $requestID, PDO::PARAM_INT);
            $stmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);
            $stmt->execute();
    
            // If approved, add member to GroupMembers
            if ($status === 'Approved') {
                $stmt = $pdo->prepare("INSERT INTO GroupMembers (GroupID, MemberID, Role, DateAdded) VALUES (:groupID, :memberID, 'Member', CURRENT_DATE)");
                $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
                $stmt->bindParam(':memberID', $requesterMemberID, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    $feedback = "Member approved and added to Group!!!";
                } else {
                    $feedback = "Member already in Group...";
                }
            }
    
            // Redirect to avoid resubmission
            header("Location: ./edit-groups.php?GroupID=$groupID");
            exit;
        }
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

    <?php if ($feedback !== ""): ?>
        <p style='color: green; font-size: 30px; font-weight: bold;'><?php echo htmlspecialchars($feedback); ?></p>     
    <?php endif ?>

    <!-- This should only be accessible to a Senior/Admin -->
    <?php if ($group['Role'] === 'Admin' && $privilege !== 'Junior'): ?>
        <div class="container-2-horizontal">
            <div class="edit-group-form">
                <h1> Edit Groups </h1>
                <form action="./edit-groups.php" method="POST">

                    <!-- Hidden input to store GroupID -->
                    <input type="hidden" name="GroupID" value="<?php echo htmlspecialchars($group['GroupID']); ?>">

                    <label for="group_name">New Group Name:</label>
                    <input type="text" id="group_name" name="group_name">
                    
                    <label for="interest_category">New Interest Category:</label>
                    <input type="text" id="interest_category" name="interest_category">

                    <label for="region">New Region:</label>
                    <input type="text" id="region" name="region">

                    <!-- Private means "Only visible to Friends, Family and Colleagues" -->
                    <label for="group_type">Group Type:</label></br>
                    <select name="group_type">
                            <option value="Family">Family</option>
                            <option value="Friends">Friends</option>
                            <option value="Colleagues">Colleagues</option>
                            <option value="Other">Other</option>
                    </select>
                    </br>
                    </br>

                    <button type="submit" name="edit_group" value="edit">Submit Group Changes</button>
                </form>
            </div>

            <!-- This is where a Senior/Admin should see the join requests from Members -->
            <div class="join-requests">
                <h2>Join Requests</h2>
                <?php if (!empty($requests)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Member ID</th>
                                <th>Request Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td><?= htmlspecialchars($request['MemberID']) ?></td>
                                    <td><?= htmlspecialchars($request['RequestDate']) ?></td>
                                    <td>
                                        <!-- Approve and Reject buttons -->
                                        <form action="./edit-groups.php" method="POST">
                                            <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($groupID); ?>">
                                            <input type="hidden" name="request_id" value="<?= $request['RequestID'] ?>">
                                            <button type="submit" name="action" value="approve">Approve</button>
                                            <button type="submit" name="action" value="reject">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No pending join requests.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</body>
</html>