<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //
    
    session_start();

    // Check if EndDate is set in the session
    if (isset($_SESSION['EndDate'])) {
        $suspensionEnd = new DateTime($_SESSION['EndDate']);
        $now = new DateTime();
        $remainingTime = $now->diff($suspensionEnd);

        // Format the remaining time as days, hours, and minutes
        $remaining = $remainingTime->format('%d days, %h hours, and %i minutes');
    } else {
        header("Location: ./login.php");
        exit();
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
    <h1>Your account is suspended.</h1>
    <p>You will regain access in: <strong><?php echo htmlspecialchars($remaining); ?></strong></p>
    <p>Please contact support if you have any questions.</p>
    <br>
    <a href="./logout.php" target="_self">Logout?</a>
</body>
</html>