<?php
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
    $dbname = "db-schema2";
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
        $sql = "SELECT MemberID, Username, Password, Privilege, Status, NeedsPasswordChange 
                FROM Members 
                WHERE Username = :username LIMIT 1";
        
        $statement = $pdo->prepare($sql);
        $statement->bindParam(':username', $usernameInput, PDO::PARAM_STR);
        $statement->execute();
        
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        // Check if the user exists
        if ($user) {

            // Verify the password
            if (password_verify($passwordInput, $user['Password'])) {

                // Check if account status is inactive and set it to active
                if ($user['Status'] === 'Inactive') {
                    // Update the user's status to Active upon successful login
                    $updateStatusSql = "UPDATE Members SET Status = 'Active' WHERE MemberID = :memberID";
                    $updateStatusStmt = $pdo->prepare($updateStatusSql);
                    $updateStatusStmt->bindParam(':memberID', $user['MemberID'], PDO::PARAM_INT);
                    $updateStatusStmt->execute();
                }

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
    <link href="../styles.css?<?php echo time(); ?>" rel="stylesheet"/>
</head>
<body>
    <header>
        <div class="header-top">
            <div class="header-title">
                <a class="header-title-link" href="../index.php">
                    <h1> Login to your Member account! </h1>
                </a>
            </div>
        </div>
    </header>

    <div class="forms-div">
        <form action="login.php" method="POST">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            
            <button type="submit">Login</button>
        </form>

        <a href="./create-account.php">Do not have an account?</a>
        </br>
        <a href="./change-login.php">Forgot Password?</a>

        <?php if (!empty($error)): ?>
            <div class="feedback">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>