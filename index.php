<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //

    session_start(); // Ensure session is started

    // Check if user is logged in
    if (isset($_SESSION['MemberID']) && $_SESSION['MemberID'] > 0) {
        // Database connection
        $host = "npc353.encs.concordia.ca"; // Change if using a different host
        $dbname = "npc353_2";
        $username = "npc353_2";
        $password = "WrestFrugallyErrant43";

        try {
            $pdo = new PDO("mysql:host=$host; dbname=$dbname", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Query to get the member's status, username change, and password change flags
            $sql = "SELECT NeedsUsernameChange, NeedsPasswordChange FROM Members WHERE MemberID = :memberID LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':memberID', $_SESSION['MemberID'], PDO::PARAM_INT);
            $stmt->execute();
            
            $userFlags = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userFlags) {
                // Check if the user needs to change their username or password
                if ($userFlags['NeedsUsernameChange'] || $userFlags['NeedsPasswordChange']) {
                    // Redirect to a page where they can update their username/password
                    header('Location: ./login/needs-userorpass-change.php');
                    exit;
                }
            }
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COSN Project</title>
    <link href="./styles.css?<?php echo time(); ?>" rel="stylesheet"/>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-top">
            <div class="header-title">
                <a class="header-title-link" href="./home.php" target="content">
                    <h1>Community Online Social Network (COSN)</h1>
                </a>
            </div>
            <div class="header-login-info">
				<!-- Display Login/Logout Tab based on session -->
				<?php
					if (isset($_SESSION['MemberID']) && $_SESSION['MemberID'] > 0) {
						// Show Logout tab if logged in
                        echo "<p>Welcome, " . $_SESSION['Username'] . "!</p>";
						echo '<a href="./login/logout.php" target="_self">Logout</a>';
					} else {
						// Show Login tab if not logged in
						echo '<a class="active" href="./login/login.php" target="_self">Login</a>';
					}
            	?>
			</div>
        </div>
        
        <!-- Header Menu -->
        <div class="header-tabs">
            <div class="tab">
                <a class="" href="./members/display-members.php" target="content">Members</a>
            </div>
            <div class="tab">
                <a class="" href="./groups/display-groups.php" target="content">Groups</a>
            </div>
            <div class="tab">
                <a class="" href="./friends/display-friends.php" target="content">Friends</a>
            </div>
            <div class="tab">
                <a class="" href="./publish-posts/publish-posts.php" target="content">Publish Posts</a>
            </div>
            <div class="tab">
                <a class="" href="./view-posts/view-posts.php" target="content">View Posts</a>
            </div>
            <div class="tab">
                <a class="" href="./chat/chat.php" target="content">Chat</a>
            </div>
            <div class="tab">
                <a class="" href="./email/email-system.php" target="content">Email</a>
            </div>
            <div class="tab">
                <a class="" href="./events/display-events.php" target="content">Events</a>
            </div>
            <div class="tab">
                <a class="" href="./gift-exchange/create-gift-exchange.php" target="content">Gift Exchange</a>
            </div>
        </div>
    </header>

    <!-- Main Layout -->
    <div class="content-area">
        <iframe name="content" id="iframe" class="content" src="./home.php"></iframe>
    </div>

    <script>
        // Some JavaScript to handle color change on tab clicking
        document.querySelectorAll('.tab a').forEach((tab) => {
            tab.addEventListener('click', (e) => {
                document.querySelectorAll('.tab a').forEach((t) => t.classList.remove('active')); // Remove all active classes from tab
                e.target.classList.add('active'); // Add active class to the clicked tab
            });
        });
    </script>
</body>
</html>