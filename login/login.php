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
    $host = "localhost";
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

                // Check if the account is suspended
                if ($user['Status'] === 'Suspended') {
                    // Query to check remaining suspension time
                    $suspensionQuery = $pdo->prepare("
                        SELECT EndDate 
                        FROM Suspensions 
                        WHERE MemberID = :member_id AND EndDate > NOW() 
                        ORDER BY EndDate DESC LIMIT 1
                    ");
                    $suspensionQuery->execute([':member_id' => $user['MemberID']]);
                    $suspension = $suspensionQuery->fetch(PDO::FETCH_ASSOC);

                    if ($suspension) {
                        // Redirect to suspension notice page
                        $_SESSION['EndDate'] = $suspension['EndDate'];
                        header("Location: ./suspended.php");
                        exit();
                    } else {
                        // If the suspension period has ended, update the member's status to Active
                        $updateStatusSql = "UPDATE Members SET Status = 'Active' WHERE MemberID = :memberID";
                        $updateStatusStmt = $pdo->prepare($updateStatusSql);
                        $updateStatusStmt->bindParam(':memberID', $user['MemberID'], PDO::PARAM_INT);
                        $updateStatusStmt->execute();
                
                        // Proceed with login
                        $_SESSION['MemberID'] = $user['MemberID'];
                        $_SESSION['Username'] = $user['Username'];
                        $_SESSION['Privilege'] = $user['Privilege'];
                
                        // Update the LastLogin field
                        $updateSql = "UPDATE Members SET LastLogin = NOW() WHERE MemberID = :memberID";
                        $updateStmt = $pdo->prepare($updateSql);
                        $updateStmt->bindParam(':memberID', $user['MemberID'], PDO::PARAM_INT);
                        $updateStmt->execute();
                
                        // Redirect to the dashboard or welcome page
                        header("Location: ../index.php");
                        exit();
                    }
                    
                } else {
                    // If Inactive, set the status to Active
                    if ($user['Status'] !== 'Active') {
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

        <br><a href="./create-account.php">Do not have an account?</a>
        <br>
        <br><a href="./change-login.php">Forgot Password?</a>

        <?php if (!empty($error)): ?>
            <div class="feedback">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>