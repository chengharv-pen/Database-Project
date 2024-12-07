<?php

    //
    //  Written for: Bipin C. Desai
    //  Class: COMP353 / Fall 2024 / Section F  
    //  Author: Chengharv Pen (40279890)
    //
    
    // Initialize variables for filters
    $filterFrom = $_GET['postFrom'] ?? null;
    $filterTime = $_GET['postTime'] ?? 'Oldest';
    $filterType = $_GET['postType'] ?? null;
    $filterMetrics = $_GET['postMetrics'] ?? null;

    try {
        // Start with the base query
        $query = "
            SELECT p.PostID, p.AuthorID, p.Content, p.PostDate, 
                (SELECT COUNT(*) FROM PostLikes pl WHERE pl.PostID = p.PostID) AS Likes,
                (SELECT COUNT(*) FROM PostDislikes pd WHERE pd.PostID = p.PostID) AS Dislikes, 
                p.CommentsCount, p.VisibilitySettings, m.MediaType, m.MediaURL
            FROM Posts p
            LEFT JOIN PostMedia m ON p.PostID = m.PostID
            LEFT JOIN BlockedMembers b ON p.AuthorID = b.BlockedID AND b.BlockerID = :currentUserID
            WHERE b.BlockedID IS NULL
        ";

        // Add filters to the query
        $params = [':currentUserID' => $memberID]; // Current user's ID

        // Filter by "From" (e.g., posts by others or the user)
        if ($filterFrom === 'You') {
            $query .= " AND p.AuthorID = :memberID";
            $params[':memberID'] = $memberID;
        } elseif ($filterFrom === 'Others') {
            $query .= " AND p.AuthorID <> :memberID";
            $params[':memberID'] = $memberID;
        }

        // Filter by visibility type
        if ($filterType) {
            if ($filterType === 'Private') {
                $query .= "
                    AND p.VisibilitySettings = 'Private'
                    AND (
                        p.authorID = :currentUserID 
                        OR EXISTS (
                            SELECT 1 
                            FROM Relationships r 
                            WHERE 
                                ((r.SenderMemberID = p.AuthorID AND r.ReceiverMemberID = :currentUserID)
                                OR (r.SenderMemberID = :currentUserID AND r.ReceiverMemberID = p.AuthorID))
                                AND r.Status = 'Active'
                        )
                    )
                ";
            } elseif ($filterType === 'Group') {
                $query .= "
                    AND p.VisibilitySettings = 'Group'
                    AND EXISTS (
                        SELECT 1 
                        FROM PostGroups pg
                        JOIN GroupMembers gm ON pg.GroupID = gm.GroupID
                        WHERE pg.PostID = p.PostID AND gm.MemberID = :currentUserID
                    )
                ";
            } else {
                $query .= " AND p.VisibilitySettings = :visibility";
                $params[':visibility'] = $filterType;
            }
        }

        // Order by time
        if ($filterTime === 'Newest') {
            $query .= " ORDER BY p.PostDate DESC";
        } else {
            $query .= " ORDER BY p.PostDate ASC";
        }

        // Apply metrics filter
        if ($filterMetrics === 'Most Likes') {
            $query = str_replace("ORDER BY p.PostDate", "ORDER BY Likes DESC, p.PostDate", $query);
        } elseif ($filterMetrics === 'Most Comments') {
            $query = str_replace("ORDER BY p.PostDate", "ORDER BY p.CommentsCount DESC, p.PostDate", $query);
        }

        // Execute the query
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch comments for each post
        $comments = [];
        foreach ($posts as $post) {
            $postID = $post['PostID'];
            $commentStmt = $pdo->prepare("
                SELECT c.CommentID, c.AuthorID, c.Content, c.CreationDate 
                FROM Comments c WHERE c.PostID = :post_id ORDER BY c.CreationDate ASC
            ");
            $commentStmt->execute([':post_id' => $postID]);
            $comments[$postID] = $commentStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        die("Error fetching posts or comments: " . $e->getMessage());
    }
?>