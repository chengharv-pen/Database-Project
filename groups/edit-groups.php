<?php
    // Start session
    session_start();

    $memberID = $_SESSION['MemberID'];
    $privilege = $_SESSION['Privilege'];

    // Check if user is authorized
    if (!isset($_SESSION['MemberID']) || !isset($_SESSION['Privilege'])) {
        die("Access denied. Please log in.");
    }

    // Database connection
    $host = "localhost";
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

    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_group'])) {
        
        // Handle edit
        if (!isset($_POST['GroupID']) || empty($_POST['GroupID'])) {
            die("Invalid request. GroupID is required.");
        }

        $groupID = intval($_POST['GroupID']);

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
            // Add the MemberID condition
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
    } else {
        die("Invalid request.");
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

        // Validate action
        if ($action === 'approve' || $action === 'reject') {
            // Update the status of the request
            $status = ($action === 'approve') ? 'Approved' : 'Rejected';
            $stmt = $pdo->prepare("UPDATE GroupJoinRequests SET Status = :status WHERE RequestID = :requestID");
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':requestID', $requestID, PDO::PARAM_INT);
            $stmt->execute();

            // If approved, add member to GroupMembers
            if ($status === 'Approved') {
                $stmt = $pdo->prepare("INSERT INTO GroupMembers (GroupID, MemberID, Role, DateAdded) VALUES (:groupID, :memberID, 'Member', CURRENT_DATE)");
                $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
                $stmt->bindParam(':memberID', $requests[0]['MemberID'], PDO::PARAM_INT);
                $stmt->execute();
            }

            // Redirect to avoid resubmission on refresh
            header("Location: ./edit-groups.php?GroupID=$groupID");
            exit;
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

    <!-- This should only be accessible to a Senior/Admin -->
    <h1> Edit Groups </h1>

    <?php if ($group['Role'] === 'Admin' && $privilege !== 'Junior'): ?>
        <div class="container-2-horizontal">
            <div class="edit-group-form">
                <form action="./edit-groups.php" method="POST">
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

                    <button type="submit">Submit Group Changes</button>
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