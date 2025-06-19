<?php
require_once 'config.php';

$pdo = getConnection();

// Get posts with vote counts
$stmt = $pdo->prepare("
    SELECT p.*, u.username,
           COALESCE(SUM(v.vote_type), 0) as vote_score,
           COUNT(DISTINCT c.id) as comment_count
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    LEFT JOIN votes v ON p.id = v.post_id 
    LEFT JOIN comments c ON p.id = c.post_id
    GROUP BY p.id 
    ORDER BY vote_score DESC, p.created_at DESC
");
$stmt->execute();
$posts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>reddit</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            line-height: 1.6;
        }
        
        .header {
            background-color: #ff4500;
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 1.5rem;
        }
        
        .nav-links {
            display: flex;
            gap: 1rem;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .nav-links a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .search-form {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .search-form input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .post {
            background: white;
            border-radius: 8px;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .post-content {
            padding: 1rem;
        }
        
        .post-title {
            font-size: 1.2rem;
            color: #333;
            text-decoration: none;
            font-weight: bold;
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .post-title:hover {
            color: #ff4500;
        }
        
        .post-meta {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .post-text {
            color: #333;
            margin-bottom: 1rem;
        }
        
        .post-image {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
        }
        
        .post-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
            border-top: 1px solid #eee;
            padding: 0.5rem 1rem;
            background-color: #f8f9fa;
        }
        
        .vote-buttons {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .vote-btn {
            background: none;
            border: 1px solid #ddd;
            padding: 0.25rem 0.5rem;
            cursor: pointer;
            border-radius: 4px;
            color: #666;
        }
        
        .vote-btn:hover {
            background-color: #f0f0f0;
        }
        
        .vote-score {
            font-weight: bold;
            min-width: 30px;
            text-align: center;
        }
        
        .comment-link {
            color: #666;
            text-decoration: none;
        }
        
        .comment-link:hover {
            color: #ff4500;
        }
        
        .create-post-btn {
            background-color: #ff4500;
            color: white;
            padding: 1rem 2rem;
            text-decoration: none;
            border-radius: 8px;
            display: inline-block;
            margin-bottom: 2rem;
            font-weight: bold;
        }
        
        .create-post-btn:hover {
            background-color: #e03e00;
        }
        .post-video video {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>reddit</h1>
        <div class="nav-links">
            <?php if (isLoggedIn()): ?>
                <span>Welcome, <?= sanitize(getCurrentUser()['username']) ?>!</span>
                <a href="post.php">Create Post</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <!-- Search Form -->
        <form class="search-form" action="search.php" method="GET">
            <input type="text" name="q" placeholder="Search posts..." value="<?= isset($_GET['q']) ? sanitize($_GET['q']) : '' ?>">
        </form>

        <?php if (isLoggedIn()): ?>
            <a href="post.php" class="create-post-btn">Create New Post</a>
        <?php endif; ?>

        <!-- Posts List -->
        <?php if (empty($posts)): ?>
            <div class="post">
                <div class="post-content">
                    <p>No posts found. Be the first to create one!</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="post">
                    <div class="post-content">
                        <a href="post.php?id=<?= $post['id'] ?>" class="post-title">
                            <?= sanitize($post['title']) ?>
                        </a>
                        
                        <div class="post-meta">
                            By <?= sanitize($post['username']) ?> • 
                            <?= date('M j, Y g:i A', strtotime($post['created_at'])) ?>
                        </div>

                        <?php if ($post['post_type'] == 'text' && $post['content']): ?>
                            <div class="post-text">
                                <?= nl2br(sanitize(substr($post['content'], 0, 300))) ?>
                                <?= strlen($post['content']) > 300 ? '...' : '' ?>
                            </div>
                        <?php elseif ($post['post_type'] == 'image' && $post['file_path']): ?>
                            <img src="<?= sanitize($post['file_path']) ?>" alt="Post image" class="post-image">
                        <?php elseif ($post['post_type'] == 'link' && $post['link_url']): ?>
                            <div class="post-text">
                                <a href="<?= sanitize($post['link_url']) ?>" target="_blank" rel="noopener">
                                    <?= sanitize($post['link_url']) ?>
                                </a>
                            </div>
                        <?php elseif ($post['post_type'] == 'video' && $post['file_path']): ?>
                            <div class="post-video">
                                <video controls>
                                    <source src="<?= sanitize($post['file_path']) ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="post-actions">
                        <div class="vote-buttons">
                            <?php if (isLoggedIn()): ?>
                                <form style="display: inline;" action="vote.php" method="POST">
                                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                    <input type="hidden" name="vote_type" value="1">
                                    <button type="submit" class="vote-btn">▲</button>
                                </form>
                            <?php endif; ?>
                            
                            <span class="vote-score"><?= $post['vote_score'] ?></span>
                            
                            <?php if (isLoggedIn()): ?>
                                <form style="display: inline;" action="vote.php" method="POST">
                                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                    <input type="hidden" name="vote_type" value="-1">
                                    <button type="submit" class="vote-btn">▼</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        
                        <a href="post.php?id=<?= $post['id'] ?>" class="comment-link">
                            <?= $post['comment_count'] ?> comments
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>