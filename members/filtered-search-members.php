<?php
    include '../db-connect.php';

    $filters = [];
    $params = [];

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../styles.css?<?php echo time(); ?>" rel="stylesheet"/>
</head>
<body>
    <?php foreach ($results as $row) {
        echo "<div class='result-row'>
                <span>Username: [{$row['Username']}] - Profession: [{$row['Profession']}] - Region: [{$row['Region']}]</span> 
                <form action='./search-members.php' method='POST'>
                    <input type='hidden' id='username' name='username' value={$row['Username']} required>
                    <button type='submit' name='search-button' class='filtered-search-button'>Search</button>
                </form>
            </div>";
    }
    ?>
</body>
</html>
