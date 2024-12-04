<?php
    session_start();

    // Ensure user is logged in
    if (!isset($_SESSION['MemberID'])) {
        header("Location: ./login/login.php"); // Redirect if not logged in
        exit();
    }

    $memberID = $_SESSION['MemberID'];

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Get the new username and/or password from the form
        $newUsername = $_POST['username'] ?? '';
        $newPassword = $_POST['password'] ?? '';

        // Database connection
        $host = "localhost";
        $dbname = "db-schema2";
        $username = "root";
        $password = "";

        try {
            $pdo = new PDO("mysql:host=$host; dbname=$dbname", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Update username and/or password if provided
            if (!empty($newUsername)) {
                $updateUsernameSql = "UPDATE Members SET Username = :username, NeedsUsernameChange = 0 WHERE MemberID = :memberID";
                $stmt = $pdo->prepare($updateUsernameSql);
                $stmt->bindParam(':username', $newUsername, PDO::PARAM_STR);
                $stmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);
                $stmt->execute();
            }

            if (!empty($newPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT); // Hash the new password
                $updatePasswordSql = "UPDATE Members SET Password = :password, NeedsPasswordChange = 0 WHERE MemberID = :memberID";
                $stmt = $pdo->prepare($updatePasswordSql);
                $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
                $stmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);
                $stmt->execute();
            }

            // Redirect back to the home page after updating
            header("Location: ../index.php");
            exit();
        } catch (PDOException $e) {
            die("Database error: " . $e->getMessage());
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
    <header>
        <div class="header-top">
            <div class="header-title">
                <h1>Update Your Credentials (Admin)</h1>
            </div>
        </div>
    </header>

    <div class="forms-div">
    <form action="./needs-userorpass-change.php" method="POST">
        <!-- Username field -->
        <label for="username">New Username:</label>
        <input type="text" name="username" id="username" required><br><br>

        <!-- Password field -->
        <label for="password">New Password:</label>
        <input type="password" name="password" id="password" required>

        <button type="submit">Update</button>
    </form>
    </div>
</body>
</html>
