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
        $firstNameInput = $_POST['firstName'] ?? '';
        $lastNameInput = $_POST['lastName'] ?? '';
        $DOBInput = $_POST['DOB'] ?? '';
        $passwordInput = $_POST['password'] ?? '';
        $accountTypeInput = $_POST['accountType'] ?? '';

        // Validate email to ensure it's a ProtonMail email address
        if (substr($emailInput, -15) !== '@protonmail.com') {
            $feedback = "Email must be a ProtonMail address (e.g., user@protonmail.com).";
        } else {
            // Hash the password securely
            $hashedPassword = password_hash($passwordInput, PASSWORD_DEFAULT);

            // SQL query with placeholders
            $sql = "INSERT INTO Members (
                        Password,
                        Username,
                        FirstName,
                        LastName,
                        Pseudonym,
                        Email,
                        Address,
                        DOB,
                        DateJoined,
                        Privilege,
                        AccountType,
                        Status,
                        NeedsPasswordChange,
                        NeedsUsernameChange
                    ) VALUES (
                        :password,
                        :username,
                        :firstName,
                        :lastName,
                        NULL,
                        :email,
                        NULL,
                        :dob,
                        CURDATE(),
                        'Junior',
                        :accountType,
                        'Inactive',
                        FALSE,
                        FALSE
                    )";

            try {
                // Prepare the statement
                $statement = $pdo->prepare($sql);

                // Bind values to the placeholders
                $statement->bindParam(':password', $hashedPassword);
                $statement->bindParam(':username', $usernameInput);
                $statement->bindParam(':firstName', $firstNameInput);
                $statement->bindParam(':lastName', $lastNameInput);
                $statement->bindParam(':email', $emailInput);
                $statement->bindParam(':dob', $DOBInput);
                $statement->bindParam(':accountType', $accountTypeInput);

                // Execute the query and check success
                if ($statement->execute()) {
                    $feedback = "User successfully added!";
                } else {
                    $feedback = "Failed to add the user.";
                }
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account!</title>
    <link href="../styles.css?<?php echo time(); ?>" rel="stylesheet"/>
</head>
<body>
    <header>    
        <div class="header-top">
            <div class="header-title">
                <a class="header-title-link" href="../index.php">
                    <h1> Create Account </h1>
                </a>
            </div>
        </div>
    </header>

    <div class="forms-div">
        <form action="create-account.php" method="POST">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="email">Email Address:</label>
            <input type="email" id="email" name="email" required>

            <label for="firstName">First Name:</label>
            <input type="text" id="firstName" name="firstName" required>

            <label for="lastName">Last Name:</label>
            <input type="text" id="lastName" name="lastName" required>

            <label for="DOB">DOB:</label>
            <input type="date" id="DOB" name="DOB" required>

            </br>
            </br>
            
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <label for="accountType">Account Type:</label>
            <select name="accountType">
                <option value="Real-person">Real Person</option>
                <option value="Business">Business</option>
            </select>
            
            <button type="submit">Create Account</button>
        </form>
        
        <br><a href="./login.php">Login to Account?</a>

        <!-- Display feedback below the form -->
        <?php if (!empty($feedback)): ?>
            <div class="feedback">
                <?php echo htmlspecialchars($feedback); ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
