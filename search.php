<?php
require_once 'config.php';

$search_query = '';
$results = [];

if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
    $search_query = trim($_GET['q']);
    $pdo = getConnection();
    
    // Search in posts and comments
    $stmt = $pdo->prepare("
        SELECT 'post' as type, p.id, p.title, p.content, u.username, p.created_at,
               COALESCE(SUM(v.vote_type), 0) as vote_score
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        LEFT JOIN votes v ON p.id = v.post_id 
        WHERE p.title LIKE ? OR p.content LIKE ?
        GROUP BY p.id
        
        UNION ALL
        
        SELECT 'comment' as type, c.id, p.title, c.content, u.username, c.created_at,
               COALESCE(SUM(v.vote_type), 0) as vote_score
        FROM comments c 
        JOIN posts p ON c.post_id = p.id
        JOIN users u ON c.user_id = u.id 
        LEFT JOIN votes v ON c.id = v.comment_id 
        WHERE c.content LIKE ?
        GROUP BY c.id
        
        ORDER BY created_at DESC
    ");
    
    $search_term = '%' . $search_query . '%';
    $stmt->execute([$search_term, $search_term, $search_term]);
    $results = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - reddit</title>
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
        
        .header a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
        }
        
        .header a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .search-form {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .search-form h2 {
            margin-bottom: 1rem;
            color: #333;
        }
        
        .search-form input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            margin-bottom: 1rem;
        }
        
        .search-form button {
            background-color: #ff4500;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
        }
        
        .search-form button:hover {
            background-color: #e03e00;
        }
        
        .search-results {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .result-item {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .result-item:last-child {
            border-bottom: none;
        }
        
        .result-type {
            display: inline-block;
            background-color: #ff4500;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .result-title {
            font-size: 1.1rem;
            color: #333;
            text-decoration: none;
            font-weight: bold;
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .result-title:hover {
            color: #ff4500;
        }
        
        .result-content {
            color: #666;
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }
        
        .result-meta {
            color: #999;
            font-size: 0.9rem;
        }
        
        .no-results {
            text-align: center;
            padding: 2rem;
            color: #666;
        }
        
        .highlight {
            background-color: yellow;
            padding: 0.1rem 0.2rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><a href="index.php" style="color: white; text-decoration: none;">reddit</a></h1>
        <div>
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
        <div class="search-form">
            <h2>Search Posts and Comments</h2>
            <form method="GET">
                <input type="text" name="q" placeholder="Enter search terms..." 
                       value="<?= sanitize($search_query) ?>" required>
                <button type="submit">Search</button>
            </form>
        </div>

        <?php if ($search_query): ?>
            <div class="search-results">
                <?php if (empty($results)): ?>
                    <div class="no-results">
                        <h3>No results found</h3>
                        <p>Try different keywords or check your spelling.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($results as $result): ?>
                        <div class="result-item">
                            <span class="result-type"><?= strtoupper($result['type']) ?></span>
                            
                            <?php if ($result['type'] == 'post'): ?>
                                <a href="post.php?id=<?= $result['id'] ?>" class="result-title">
                                    <?= highlightSearch(sanitize($result['title']), $search_query) ?>
                                </a>
                                
                                <?php if ($result['content']): ?>
                                    <div class="result-content">
                                        <?= highlightSearch(sanitize(substr($result['content'], 0, 200)), $search_query) ?>
                                        <?= strlen($result['content']) > 200 ? '...' : '' ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="result-title">
                                    Comment on: <?= sanitize($result['title']) ?>
                                </div>
                                
                                <div class="result-content">
                                    <?= highlightSearch(sanitize(substr($result['content'], 0, 200)), $search_query) ?>
                                    <?= strlen($result['content']) > 200 ? '...' : '' ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="result-meta">
                                By <?= sanitize($result['username']) ?> • 
                                <?= date('M j, Y g:i A', strtotime($result['created_at'])) ?> • 
                                Score: <?= $result['vote_score'] ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
function highlightSearch($text, $search) {
    if (empty($search)) return $text;
    return preg_replace('/(' . preg_quote($search, '/') . ')/i', '<span class="highlight">$1</span>', $text);
}
?>