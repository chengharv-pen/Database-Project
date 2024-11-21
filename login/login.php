<?php
    // Cookie parameter
    session_set_cookie_params([
        'lifetime' => 86400, // Duration of the session cookie
        'path' => '/', // Make the session available across the entire domain
        'secure' => true, // Ensure secure cookies (only over HTTPS)
        'httponly' => true, // Prevent JavaScript access to session cookies
    ]);

    // Start session
    session_start();

    // Database connection
    $host = "localhost"; // Change if using a different host
    $dbname = "db-schema";
    $username = "root";
    $password = "";

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }   

    // Handle login form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Get input from the form
        $usernameInput = $_POST['username'];
        $passwordInput = $_POST['password'];

        // Query to find user by username
        $sql = "SELECT MemberID, Username, Password, Privilege, Status, NeedsPasswordChange FROM Members WHERE Username = :username LIMIT 1";
        $statement = $pdo->prepare($sql);
        $statement->bindParam(':username', $usernameInput, PDO::PARAM_STR);
        $statement->execute();
        
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        // Check if the user exists
        if ($user) {

            // Verify the password
            if (password_verify($passwordInput, $user['Password'])) {

                echo "IN PASSWORD IF";

                // Check if account status is active
                if ($user['Status'] === 'Active') {
                    // Store user information in the session
                    $_SESSION['MemberID'] = $user['MemberID'];
                    $_SESSION['Username'] = $user['Username'];
                    $_SESSION['Privilege'] = $user['Privilege'];

                    // Update the LastLogin field
                    $updateSql = "UPDATE Members SET LastLogin = NOW() WHERE MemberID = :memberID";
                    $updateStmt = $pdo->prepare($updateSql);
                    $updateStmt->bindParam(':memberID', $user['MemberID'], PDO::PARAM_INT);
                    $updateStmt->execute();

                    // Redirect to a dashboard or welcome page
                    header("Location: ../index.php");
                    exit();
                } else {
                    $error = "Your account is not active.";
                }
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="../styles.css" rel="stylesheet"/>
</head>
<body>
    <form action="login.php" method="POST">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        
        <button type="submit">Login</button>
    </form>

    <?php if (!empty($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
</body>
</html>