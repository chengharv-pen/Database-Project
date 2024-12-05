<?php
    include '../db-connect.php';

    $filters = [];
    $params = [];

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (!empty($_POST['interest'])) {
            $filters[] = "Interests LIKE :interest";
            $params[':interest'] = '%' . $_POST['interest'] . '%';
        }
        if (!empty($_POST['profession'])) {
            $filters[] = "Profession = :profession";
            $params[':profession'] = $_POST['profession'];
        }
        if (!empty($_POST['region'])) {
            $filters[] = "Region = :region";
            $params[':region'] = $_POST['region'];
        }
        if (!empty($_POST['min_age']) && !empty($_POST['max_age'])) {
            $filters[] = "TIMESTAMPDIFF(YEAR, DOB, CURDATE()) BETWEEN :min_age AND :max_age";
            $params[':min_age'] = $_POST['min_age'];
            $params[':max_age'] = $_POST['max_age'];
        }

        $sql = "SELECT MemberID, Username, FirstName, LastName, Profession, Region, Interests FROM Members";
        if ($filters) {
            $sql .= " WHERE " . implode(" AND ", $filters);
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../styles.css?<?php echo time(); ?>" rel="stylesheet"/>
    <link href="./filtered-search.css?<?php echo time(); ?>" rel="stylesheet"/>
</head>
<body>
    <div class="filtered-search-wrapper">
        <div class="display-filtered-search">
            <h3> Filtered Search </h3>
            <!-- Search for a Profile by Filters -->
            <form action="./filtered-search-members.php" method="POST">
                <!-- Interest Filter -->
                <label for="interest">Interest:</label>
                <input type="text" id="interest" name="interest" placeholder="Enter interest">
                    
                <!-- Age Range Filter -->
                <label for="min-age">Min Age (years):</label>
                <input type="number" id="min-age" name="min_age" min="0" placeholder="Min Age"><br><br>
                    
                <label for="max-age">Max Age (years):</label>
                <input type="number" id="max-age" name="max_age" min="0" placeholder="Max Age"><br><br>
                    
                <!-- Profession Filter -->
                <label for="profession">Profession:</label>
                <input type="text" id="profession" name="profession" placeholder="Enter profession">
                    
                <!-- Region Filter -->
                <label for="region">Region:</label>
                <input type="text" id="region" name="region" placeholder="Enter region">
                    
                <!-- Submit Button -->
                <button type="submit" name="search-button" class="search-button">Search by Filters</button>
            </form>
        </div>

        <div class="result-row-container">
            <?php if ($results): ?>
                <?php foreach ($results as $row): ?>
                    <div class="result-row">
                        <span>Username: [<?php echo htmlspecialchars($row['Username']); ?>] 
                            - Profession: [<?php echo htmlspecialchars($row['Profession']); ?>] 
                            - Region: [<?php echo htmlspecialchars($row['Region']); ?>]
                        </span> 
                        <form action="./search-members.php" method="POST">
                            <input type="hidden" id="username" name="username" value="<?php echo htmlspecialchars($row['Username']); ?>" required>
                            <button type="submit" name="search-button" class="filtered-search-button">Search</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No results found.</p>
            <?php endif; ?>
        </div>
    </div>
    
</body>
</html>
