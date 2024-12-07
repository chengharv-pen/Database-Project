<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //
    
    include '../db-connect.php';
        
    if ($privilege !== 'Administrator') {
        die("Access denied");
    }

    // Fetch members from the database
    $stmt = $pdo->prepare("SELECT * FROM Members");
    $stmt->execute();
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../styles.css?<?php echo time(); ?>" rel="stylesheet">
</head>
<body>
    <h1>Admin Dashboard</h1>
    <h2>Manage Members</h2>
    <table border="1">
        <tr>
            <th>Username</th>
            <th>Warnings</th>
            <th>Suspensions</th>
            <th>Fines</th>
            <th>Account Type</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($members as $member) { ?>
            <tr>
                <td><?php echo htmlspecialchars($member['Username']); ?></td>
                <td><?php echo $member['Warnings']; ?></td>
                <td><?php echo $member['Suspensions']; ?></td>
                <td><?php echo $member['Fines']; ?></td>
                <td><?php echo $member['AccountType']; ?></td>
                <td><?php echo $member['Status']; ?></td>
                <td>
                    <a href="./view-warnings.php?member_id=<?php echo $member['MemberID']; ?>">View Warning</a>
                    <a href="./issue-warning.php?member_id=<?php echo $member['MemberID']; ?>">Issue Warning</a>
                </td>
            </tr>
        <?php } ?>
    </table>
</body>
</html>