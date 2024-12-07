<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //
    
    include '../db-connect.php';

    // Get filter inputs
    $interestCategory = $_GET['interest_category'] ?? '';
    $minAge = $_GET['min_age'] ?? 0;
    $maxAge = $_GET['max_age'] ?? 9999999999;
    $region = $_GET['region'] ?? '';

    $filters = [];
    $params = [];

    // Build SQL query dynamically based on provided filters
    if (!empty($interestCategory)) {
        $filters[] = "g.InterestCategory LIKE :interestCategory";
        $params[':interestCategory'] = '%' . $interestCategory . '%';
    }
    if (!empty($region)) {
        $filters[] = "g.Region = :region";
        $params[':region'] = $region;
    }
    if (!empty($minAge) && !empty($maxAge)) {
        $filters[] = "TIMESTAMPDIFF(SECOND, g.CreationDate, NOW()) BETWEEN :minAge AND :maxAge";
        $params[':minAge'] = $minAge;
        $params[':maxAge'] = $maxAge;
    }

    // Base SQL query
    $sql = "
        SELECT g.*, m.FirstName, m.LastName, TIMESTAMPDIFF(SECOND, g.CreationDate, NOW()) AS Age
        FROM `Groups` g
        JOIN Members m ON g.OwnerID = m.MemberID
    ";

    // Append filters if any are provided
    if (!empty($filters)) {
        $sql .= " WHERE " . implode(" AND ", $filters);
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching filtered groups: " . $e->getMessage());
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../styles.css?<?php echo time(); ?>" rel="stylesheet"/>
    <title>Filtered Groups</title>
</head>
<body>
    <form method="GET" action="filtered-groups.php">
        <!-- Interest Category -->
        <label for="interest-category">Interest Category:</label>
        <input type="text" id="interest-category" name="interest_category" placeholder="Enter interest category">

        <!-- Age Range -->
        <label for="min-age">Min Age (seconds):</label>
        <input type="number" id="min-age" name="min_age" min="0" placeholder="Min Age"><br><br>

        <label for="max-age">Max Age (seconds):</label>
        <input type="number" id="max-age" name="max_age" min="0" placeholder="Max Age"><br><br>

        <!-- Region -->
        <label for="region">Region:</label>
        <input type="text" id="region" name="region" placeholder="Enter region">

        <button type="submit">Filter Groups</button>
    </form>

    <h1>Filtered Groups</h1>
    <?php if (count($groups) > 0): ?>
        <div class="groups">
            <?php foreach ($groups as $group): ?>
                <div class="group">
                    <strong><?= htmlspecialchars($group['GroupName']) ?> (Age: <?= htmlspecialchars($group['Age']) ?> seconds) <br></strong>
                    (<?= htmlspecialchars($group['GroupType']) ?>, <?= htmlspecialchars($group['Region']) ?>)<br><br>
                    Interest Category: <?= htmlspecialchars($group['InterestCategory']) ?>
                    <p>Owner: <?= htmlspecialchars($group['FirstName'] . ' ' . $group['LastName']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No groups found matching the criteria.</p>
    <?php endif; ?>
</body>
</html>