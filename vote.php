<?php
require_once 'config.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $vote_type = (int)$_POST['vote_type']; // 1 for upvote, -1 for downvote
    $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : null;
    $comment_id = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : null;
    $user_id = $_SESSION['user_id'];
    
    $pdo = getConnection();
    
    try {
        if ($post_id) {
            // Handle post voting
            // Check if user already voted on this post
            $stmt = $pdo->prepare("SELECT vote_type FROM votes WHERE user_id = ? AND post_id = ?");
            $stmt->execute([$user_id, $post_id]);
            $existing_vote = $stmt->fetch();
            
            if ($existing_vote) {
                if ($existing_vote['vote_type'] == $vote_type) {
                    // Remove vote if clicking same vote type
                    $stmt = $pdo->prepare("DELETE FROM votes WHERE user_id = ? AND post_id = ?");
                    $stmt->execute([$user_id, $post_id]);
                } else {
                    // Update vote type if different
                    $stmt = $pdo->prepare("UPDATE votes SET vote_type = ? WHERE user_id = ? AND post_id = ?");
                    $stmt->execute([$vote_type, $user_id, $post_id]);
                }
            } else {
                // Insert new vote
                $stmt = $pdo->prepare("INSERT INTO votes (user_id, post_id, vote_type) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $post_id, $vote_type]);
            }
            
            // Redirect back to the post or homepage
            $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
            header("Location: $redirect_url");
            
        } elseif ($comment_id) {
            // Handle comment voting
            // Check if user already voted on this comment
            $stmt = $pdo->prepare("SELECT vote_type FROM votes WHERE user_id = ? AND comment_id = ?");
            $stmt->execute([$user_id, $comment_id]);
            $existing_vote = $stmt->fetch();
            
            if ($existing_vote) {
                if ($existing_vote['vote_type'] == $vote_type) {
                    // Remove vote if clicking same vote type
                    $stmt = $pdo->prepare("DELETE FROM votes WHERE user_id = ? AND comment_id = ?");
                    $stmt->execute([$user_id, $comment_id]);
                } else {
                    // Update vote type if different
                    $stmt = $pdo->prepare("UPDATE votes SET vote_type = ? WHERE user_id = ? AND comment_id = ?");
                    $stmt->execute([$vote_type, $user_id, $comment_id]);
                }
            } else {
                // Insert new vote
                $stmt = $pdo->prepare("INSERT INTO votes (user_id, comment_id, vote_type) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $comment_id, $vote_type]);
            }
            
            // Redirect back to the post
            $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
            header("Location: $redirect_url");
        }
        
    } catch (PDOException $e) {
        // Handle database errors
        error_log("Vote error: " . $e->getMessage());
        header('Location: index.php');
    }
    
} else {
    // Redirect if not POST request
    header('Location: index.php');
}

exit;
?>