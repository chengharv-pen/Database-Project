<?php
    include '../db-connect.php';  

    // Check if the user is an administrator
    if ($privilege !== 'Administrator') {
        die("Access denied");
    }

    // Fetch all pending promotion requests
    $sql = "SELECT pr.RequestID, pr.MemberID, m.Username 
            FROM PromotionRequests pr
            JOIN Members m ON pr.MemberID = m.MemberID
            WHERE pr.Status = 'pending'";

    try {
        $statement = $pdo->prepare($sql);
        $statement->execute();
        $requests = $statement->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Query failed: " . $e->getMessage());
    }

    // Handle the approval or denial of requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $requestId = $_POST['request_id'];
        $action = $_POST['action'];

        try {
            if ($action === 'approve') {
                // Approve the request: Update the status to 'approved' in PromotionRequests
                $sql = "UPDATE PromotionRequests SET Status = 'approved' WHERE RequestID = :request_id";
                $statement = $pdo->prepare($sql);
                $statement->bindParam(':request_id', $requestId, PDO::PARAM_INT);
                $statement->execute();

                // Update the member's privilege to Senior
                $sql = "UPDATE Members SET Privilege = 'Senior' WHERE MemberID = (SELECT MemberID FROM PromotionRequests WHERE RequestID = :request_id)";
                $statement = $pdo->prepare($sql);
                $statement->bindParam(':request_id', $requestId, PDO::PARAM_INT);
                $statement->execute();
                
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } elseif ($action === 'deny') {
                // Deny the request: Update the status to 'denied' in PromotionRequests
                $sql = "UPDATE PromotionRequests SET Status = 'denied' WHERE RequestID = :request_id";
                $statement = $pdo->prepare($sql);
                $statement->bindParam(':request_id', $requestId, PDO::PARAM_INT);
                $statement->execute();

                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }
        } catch (PDOException $e) {
            die("Query failed: " . $e->getMessage());
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../styles.css?<?php echo time(); ?>" rel="stylesheet"/>
    <title>Approve Senior Requests</title>
</head>
<body>
    <h1>Approve Senior Requests</h1>

    <?php if (empty($requests)): ?>
        <p>No pending promotion requests.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($request['Username']); ?></td>
                        <td>
                            <!-- Approve and Deny Buttons -->
                            <form action="./approve-senior-requests.php" method="POST" style="display:inline;">
                                <input type="hidden" name="request_id" value="<?php echo $request['RequestID']; ?>">
                                <button type="submit" name="action" value="approve" class="approve-button">Approve</button>
                            </form>
                            <form action="./approve-senior-requests.php" method="POST" style="display:inline;">
                                <input type="hidden" name="request_id" value="<?php echo $request['RequestID']; ?>">
                                <button type="submit" name="action" value="deny" class="deny-button">Deny</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
