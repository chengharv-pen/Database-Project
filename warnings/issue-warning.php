<?php
    include '../db-connect.php';

    if ($privilege !== 'Administrator') {
        die("Access denied");
    }

    // Fetch all members to be displayed in the dropdown
    $membersStmt = $pdo->prepare("SELECT MemberID, Username FROM Members WHERE Status = 'Active' OR Status = 'Inactive'");
    $membersStmt->execute();
    $members = $membersStmt->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['member_id']) && isset($_POST['reason'])) {
            $receiverMemberID = $_POST['member_id'];
            $reason = $_POST['reason'];

            // Fetch member details to get the current warning count and account type
            $stmt = $pdo->prepare("SELECT Warnings, AccountType, Suspensions, Fines FROM Members WHERE MemberID = :member_id");
            $stmt->execute([':member_id' => $receiverMemberID]);
            $member = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($member) {
                // Increment warning count
                $newWarnings = $member['Warnings'] + 1;
                $stmt = $pdo->prepare("UPDATE Members SET Warnings = :warnings WHERE MemberID = :member_id");
                $stmt->execute([':warnings' => $newWarnings, ':member_id' => $receiverMemberID]);

                // Insert warning into Warnings table
                $warningStmt = $pdo->prepare("INSERT INTO Warnings (MemberID, Reason, IssuedBy, WarningType) VALUES (:member_id, :reason, :issued_by, 'Post')");
                $warningStmt->execute([
                    ':member_id' => $receiverMemberID,
                    ':reason' => $reason,
                    ':issued_by' => $memberID, // Administrator issuing the warning
                ]);

                // Check if a suspension or fine is required
                // For a Real-person Member, every 3 new warnings, there will be a suspension
                if ($newWarnings % 3 == 0) {
                    if ($member['AccountType'] == 'Real-person') {
                        // Get the number of suspensions already applied to the member
                        $suspensionsCount = $member['Suspensions'];

                        // Determine the suspension period based on the number of previous suspensions
                        if ($suspensionsCount == 0) {
                            $suspensionPeriod = 8; // First suspension
                        } elseif ($suspensionsCount == 1) {
                            $suspensionPeriod = 30; // Second suspension
                        } else {
                            $suspensionPeriod = 365; // Third suspension and beyond
                        }

                        

                        // Suspend member and create an entry in the Suspensions table
                        $stmt = $pdo->prepare("UPDATE Members SET Status = 'Suspended', Suspensions = Suspensions + 1 WHERE MemberID = :member_id");
                        $stmt->execute([':member_id' => $receiverMemberID]);

                        // Insert into Suspensions table with the calculated suspension time
                        $suspensionStmt = $pdo->prepare("INSERT INTO Suspensions (MemberID, SuspendedAt, SuspensionEnd, Reason, SuspendedBy) 
                                                        VALUES (:member_id, NOW(), DATE_ADD(NOW(), INTERVAL :suspension_period DAY), :reason, :suspended_by)");
                        $suspensionStmt->execute([
                            ':member_id' => $receiverMemberID,
                            ':suspension_period' => $suspensionPeriod,
                            ':reason' => 'Excessive warnings',
                            ':suspended_by' => $memberID // Administrator issuing the suspension
                        ]);
                    }
                }

                // For a Business Member, after the second warning, there will be a fine.
                // This will apply to every warning after it
                if ($newWarnings > 2) {
                    if ($member['AccountType'] == 'Business') {
                        // Increment fines
                        $newFines = $member['Fines'] + 1;
                        $stmt = $pdo->prepare("UPDATE Members SET Fines = :fines WHERE MemberID = :member_id");
                        $stmt->execute([':fines' => $newFines, ':member_id' => $receiverMemberID]);

                        // Determine fine amount based on the number of fines
                        $fineAmount = 50; // Default fine
                        if ($newFines == 2) {
                            $fineAmount = 100;
                        } elseif ($newFines >= 3) {
                            $fineAmount = 200;
                        }

                        // Insert fine payment record
                        $paymentStmt = $pdo->prepare("INSERT INTO Payments (MemberID, Amount, PaymentDate, Description) 
                                                      VALUES (:member_id, :amount, NOW(), :description)");
                        $paymentStmt->execute([
                            ':member_id' => $receiverMemberID,
                            ':amount' => $fineAmount,
                            ':description' => "Fine for excessive warnings (Fine #$newFines)",
                        ]);

                        // The Business account shall be suspended, if there are more than 2 fines
                        if ($newFines > 2) {
                            // Get the number of suspensions already applied to the member
                            $suspensionsCount = $member['Suspensions'];

                            // Determine the suspension period based on the number of previous suspensions
                            if ($suspensionsCount == 0) {
                                $suspensionPeriod = '7 DAY'; // First suspension
                            } elseif ($suspensionsCount == 1) {
                                $suspensionPeriod = '30 DAY'; // Second suspension
                            } else {
                                $suspensionPeriod = '1 YEAR'; // Third suspension and beyond
                            }

                            // Increment a Member's Suspensions by 1
                            $stmt = $pdo->prepare("UPDATE Members SET Status = 'Suspended', Suspensions = Suspensions + 1 WHERE MemberID = :member_id");
                            $stmt->execute([':member_id' => $receiverMemberID]);

                            // Log the suspension in the Suspensions table
                            $suspensionStmt = $pdo->prepare("INSERT INTO Suspensions (MemberID, SuspendedAt, SuspensionEnd, Reason, SuspendedBy) 
                                                            VALUES (:member_id, NOW(), DATE_ADD(NOW(), INTERVAL :suspension_period), :reason, :suspended_by)");
                            $suspensionStmt->execute([
                                ':member_id' => $receiverMemberID,
                                ':suspension_period' => $suspensionPeriod,
                                ':reason' => 'Excessive fines (more than 2)',
                                ':suspended_by' => $memberID // Administrator issuing the suspension
                            ]);
                        }
                    }
                }

                echo "Warning issued and suspension (if applicable) applied successfully.";
            }
        } else {
            echo "Please fill out all fields.";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Warning</title>
</head>
<body>

    <h1>Issue a Warning</h1>

    <form method="POST" action="">
        <!-- Select Member -->
        <label for="member_id">Select Member:</label>
        <select name="member_id" id="member_id" required>
            <option value="">-- Select a Member --</option>
            <?php
                foreach ($members as $member) {
                    echo "<option value='{$member['MemberID']}'>{$member['Username']}</option>";
                }
            ?>
        </select><br><br>

        <!-- Warning Reason -->
        <label for="reason">Reason for Warning:</label><br>
        <textarea name="reason" id="reason" rows="4" cols="50" required></textarea><br><br>

        <!-- Submit Button -->
        <button type="submit">Issue Warning</button>
    </form>

</body>
</html>
