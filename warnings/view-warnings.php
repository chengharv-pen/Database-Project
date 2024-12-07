<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //
    
    include '../db-connect.php';

    if ($privilege !== 'Administrator') {
        die("Access denied");
    }

    if (isset($_GET['member_id'])) {
        $memberID = $_GET['member_id'];
        $stmt = $pdo->prepare("
            SELECT w.Reason, w.CreatedAt, p.Content AS PostContent, c.Content AS CommentContent, 
                e.Body AS EmailBody, me.Content AS MessageContent, m.Username
            FROM Warnings w
            LEFT JOIN Posts p ON w.PostID = p.PostID
            LEFT JOIN Comments c ON w.CommentID = c.CommentID
            LEFT JOIN Email e ON w.EmailID = e.EmailID
            LEFT JOIN Messages me ON w.MessageID = me.MessageID
            LEFT JOIN Members m ON w.IssuedBy = m.MemberID
            WHERE w.MemberID = :member_id
        ");
        $stmt->execute([':member_id' => $memberID]);
        $warnings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($warnings) {
            echo "<h1>Warnings for Member</h1>";
            echo "<table border='1'>
                    <tr>
                        <th>Reason</th>
                        <th>Created At</th>
                        <th>Post/Comment/Email/Message</th>
                        <th>Issued By</th>
                    </tr>";

            foreach ($warnings as $warning) {
                echo "<tr>
                        <td>{$warning['Reason']}</td>
                        <td>{$warning['CreatedAt']}</td>
                        <td>";

                // Display the content based on available fields
                if (!empty($warning['PostContent'])) {
                    echo "Post: " . htmlspecialchars($warning['PostContent']) . "<br>";
                }
                if (!empty($warning['CommentContent'])) {
                    echo "Comment: " . htmlspecialchars($warning['CommentContent']) . "<br>";
                }
                if (!empty($warning['EmailBody'])) {
                    echo "Email: " . htmlspecialchars($warning['EmailBody']) . "<br>";
                }
                if (!empty($warning['MessageContent'])) {
                    echo "Message: " . htmlspecialchars($warning['MessageContent']) . "<br>";
                }

                echo "</td>
                        <td>{$warning['Username']}</td>
                    </tr>";
            }

            echo "</table>";
        } else {
            echo "No warnings found for this member.";
        }
    } else {
        echo "Invalid member ID.";
    }
?>

