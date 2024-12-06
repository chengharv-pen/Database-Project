<?php
    include '../db-connect.php';
    
    // Handle form submission for group creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $groupNameInput = $_POST['group_name'] ?? '';
        $interestsInput = $_POST['interest_category'] ?? '';
        $regionInput = $_POST['region'] ?? '';
        $groupTypeInput = $_POST['group_type'] ?? '';
        $creationDateInput = "";
    
        // SQL query with placeholders to insert into Groups table
        $sql = "INSERT INTO `Groups` (
            OwnerID,
            GroupName,
            CreationDate,
            GroupType,
            Region,
            InterestCategory
        ) VALUES (
            :memberID,
            :groupName,
            NOW(),
            :groupType,
            :region,
            :interestCategory
        )";

        try {
            // Prepare the statement for inserting the new group
            $statement = $pdo->prepare($sql);

            // Bind values to the placeholders
            $statement->bindParam(':memberID', $memberID);
            $statement->bindParam(':groupName', $groupNameInput);
            $statement->bindParam(':groupType', $groupTypeInput);
            $statement->bindParam(':region', $regionInput);
            $statement->bindParam(':interestCategory', $interestsInput);

            // Execute the query and check success
            if ($statement->execute()) {
                // 1. Fetch the last inserted GroupID using lastInsertId()
                $groupID = $pdo->lastInsertId();

                // 2. Insert into GroupMembers table to add the creator as the first member
                $sql = "INSERT INTO GroupMembers (
                    GroupID,
                    MemberID,
                    Role,
                    DateAdded
                ) VALUES (
                    :groupID,
                    :memberID,
                    'Admin',
                    NOW()
                )";

                // Prepare the statement for inserting the creator into GroupMembers
                $statement = $pdo->prepare($sql);
                $statement->bindParam(':groupID', $groupID);
                $statement->bindParam(':memberID', $memberID);

                // Execute the insertion into GroupMembers
                if ($statement->execute()) {
                    // Redirect to display-groups.php with a success message
                    header("Location: ./display-groups.php?message=Group successfully added!");
                    exit;
                } else {
                    // If insertion into GroupMembers fails
                    header("Location: ./display-groups.php?message=Failed to add member to the group.");
                    exit;
                }
            } else {
                // If insertion into Groups fails
                header("Location: ./display-groups.php?message=Failed to add the group.");
                exit;
            }
        } catch (PDOException $e) {
            // Catch any exception and display an error message
            echo "Error: " . $e->getMessage();
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

    <!-- This should only be accessible to a Senior/Admin -->
    <h1> Create Groups </h1>

    <?php if ($privilege !== 'Junior'): ?>
        <form action="./create-groups.php" method="POST">
            <label for="group_name">Group Name:</label>
            <input type="text" id="group_name" name="group_name" required>
                    
            <label for="interest_category">Interest Category:</label>
            <input type="text" id="interest_category" name="interest_category">

            <label for="region">Region:</label>
            <input type="text" id="region" name="region">

            <!-- Private means "Only visible to Friends, Family and Colleagues" -->
            <label for="group_type">Group Type:</label></br>
            <select name="group_type">
                <option value="Family">Family</option>
                <option value="Friends">Friends</option>
                <option value="Colleagues">Colleagues</option>
                <option value="Other">Other</option>
            </select>
            <br>
            <br>

            <button type="submit">Create Group</button>
        </form>
        <br>
        <a href="./display-groups.php">Display Groups?</a>
    <?php endif; ?>

</body>
</html>