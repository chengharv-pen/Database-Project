<?php
    include '../db-connect.php';

    // Get MemberID from session
    $memberID = $_SESSION['MemberID'];

    // Query to fetch member details
    $sql = "SELECT *
            FROM Members 
            WHERE MemberID = :memberID";

    try {
        $statement = $pdo->prepare($sql);
        $statement->bindParam(':memberID', $memberID, PDO::PARAM_INT);
        $statement->execute();

        // Fetch member details
        $member = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$member) {
            die("Member not found.");
        }

    } catch (PDOException $e) {
        die("Query failed: " . $e->getMessage());
    }

    // Query to submit edits to a Member's profile
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Initialize an array for updates
        $updates = [];
        $params = [];
    
        // List of possible fields to update
        $fields = [
            'username' => 'Username',
            'email' => 'Email',
            'pseudonym' => 'Pseudonym',
            'address' => 'Address',
            'profession' => 'Profession',
            'region' => 'Region',
            'public_bio' => 'PublicInformation',
            'private_bio' => 'PrivateInformation',
            'interests' => 'Interests',
            'group_visibility' => 'GroupVisibilitySettings',
            'profile_visibility' => 'ProfileVisibilitySettings',
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
            $updateSQL = "UPDATE Members SET " . implode(", ", $updates) . " WHERE MemberID = :memberID";
            $params[':memberID'] = $memberID;
    
            try {
                $stmt = $pdo->prepare($updateSQL);
                $stmt->execute($params);
                echo "<p style='color: green; font-size: 30px; font-weight: bold;'>Profile updated successfully!</p>";
            } catch (PDOException $e) {
                die("Error updating profile: " . $e->getMessage());
            }
        } else {
            echo "<p style='color: green; font-size: 30px; font-weight: bold;'>No changes to update.</p>";
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

    <h1>Edit Your Profile</h1>

    <!-- 
        A form that lets a user modify their attributes 

        If a field is empty, DO NOT CHANGE THE OLD FIELD
    -->
    <form action="./edit-members.php" method="POST">
        <label for="username">New Username:</label>
        <input type="text" id="username" name="username">
        
        <label for="email">New Email:</label>
        <input type="email" id="email" name="email">

        <label for="pseudonym">New Pseudonym:</label>
        <input type="text" id="pseudonym" name="pseudonym">

        <label for="address">New Address:</label>
        <input type="text" id="address" name="address">

        <label for="profession">New Profession:</label>
        <input type="text" id="profession" name="profession">

        <label for="region">New Region:</label>
        <input type="text" id="region" name="region">

        <label for="public_bio">New Bio (Public):</label></br>
        <textarea name="public_bio" placeholder="Enter your public bio" rows="2" cols="40"></textarea>
        </br>
        </br>

        <label for="private_bio">New Bio (Private):</label></br>
        <textarea name="private_bio" placeholder="Enter your private bio" rows="2" cols="40"></textarea>
        </br>
        </br>

        <label for="interests">New Interests:</label></br>
        <textarea name="interests" placeholder="Enter your interests" rows="2" cols="40"></textarea>
        </br>
        </br>

        <!-- Private means "Only visible to Friends, Family and Colleagues" -->
        <label for="group_visibility">Group Visibility:</label></br>
        <select name="group_visibility">
                <option value="Public">Public</option>
                <option value="Private">Private</option>
                <option value="Invisible">Invisible</option>
        </select>
        </br>
        </br>

        <!-- Private means "Only visible to Friends, Family and Colleagues" -->
        <label for="profile_visibility">Profile Visibility:</label></br>
        <select name="profile_visibility">
                <option value="Public">Public</option>
                <option value="Private">Private</option>
        </select>
        </br>
        </br>

        <button type="submit">Submit Profile Changes</button>
    </form>

    <a href="./display-members.php">Display Your Profile?</a>
</body>
</html>