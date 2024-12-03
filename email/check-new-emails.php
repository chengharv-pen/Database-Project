<?php
    session_start();

    // Check if user is authorized
    if (!isset($_SESSION['MemberID']) || !isset($_SESSION['Privilege'])) {
        die("Access denied. Please log in.");
    }

    $memberID = $_SESSION['MemberID'];

    // Database connection
    $host = "localhost"; // Change if using a different host
    $dbname = "db-schema";
    $username = "root";
    $password = "";

    try {
        $pdo = new PDO("mysql:host=$host; dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) AS NewEmails FROM Email WHERE ReceiverID = :memberID AND DateSent > NOW() - INTERVAL 5 SECOND");
    $stmt->bindParam(':memberID', $memberID, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['newEmails' => $result['NewEmails'] > 0]);
?>
