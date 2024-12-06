<?php
    // Start session
    session_start();

    // Database connection
    $host = "npc353.encs.concordia.ca"; // Change if using a different host
    $dbname = "npc353_2";
    $username = "npc353_2";
    $password = "WrestFrugallyErrant43";

    // Initialize feedback variable
    $feedback = "";

    try {
        $pdo = new PDO("mysql:host=$host; dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Get input from the form and sanitize inputs
        $usernameInput = $_POST['username'] ?? '';
        $emailInput = $_POST['email'] ?? '';
        $passwordInput1 = $_POST['password-1'] ?? '';
        $passwordInput2 = $_POST['password-2'] ?? '';

        
        if ($passwordInput1 == $passwordInput2) {
            // Hash the password securely
            $hashedPassword = password_hash($passwordInput1, PASSWORD_DEFAULT);
            
            // SQL query with placeholders
            $sql = "UPDATE Members
                    SET Password = :password
                    WHERE Username = :username AND Email = :email";

            try {
                // Prepare the statement
                $statement = $pdo->prepare($sql);
    
                // Bind values to the placeholders
                $statement->bindParam(':username', $usernameInput);
                $statement->bindParam(':email', $emailInput);
                $statement->bindParam(':password', $hashedPassword);
    
                // Execute the query and check success
                if ($statement->execute()) {
                    $feedback = "Password succesfully changed!";
                } else {
                    $feedback = "Failed to change the password.";
                }
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
        } else {
            $feedback = "Passwords do not match.";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Login!</title>
    <link href="../styles.css?<?php echo time(); ?>" rel="stylesheet"/>
</head>
<body>
    <header>    
        <div class="header-top">
            <div class="header-title">
                <a class="header-title-link" href="../index.php">
                    <h1> Change Password </h1>
                </a>
            </div>
        </div>
    </header>

    <div class="forms-div">
        <form action="change-login.php" method="POST">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="email">Email Address:</label>
            <input type="email" id="email" name="email" required>

            <label for="password-1">New Password:</label>
            <input type="password" id="password-1" name="password-1" required>

            <label for="password-2">Retype the New Password:</label>
            <input type="password" id="password-2" name="password-2" required>

            <button type="submit">Change Password</button>
        </form>

        <a href="./login.php">Login to Account?</a>
        </br>

        <!-- Display feedback below the form -->
        <?php if (!empty($feedback)): ?>
            <div class="feedback">
                <?php echo htmlspecialchars($feedback); ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>