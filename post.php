<?php
require_once 'config.php';

// Redirect if not logged in for creating posts
if (!isLoggedIn() && !isset($_GET['id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';
$post = null;
$comments = [];

// Handle viewing a specific post
if (isset($_GET['id'])) {
    $post_id = (int)$_GET['id'];
    $pdo = getConnection();
    
    // Get post details with vote count
    $stmt = $pdo->prepare("
        SELECT p.*, u.username,
               COALESCE(SUM(v.vote_type), 0) as vote_score
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        LEFT JOIN votes v ON p.id = v.post_id 
        WHERE p.id = ?
        GROUP BY p.id
    ");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();
    
    if (!$post) {
        header('Location: index.php');
        exit;
    }
    
    // Get comments in threaded format
    $stmt = $pdo->prepare("
        SELECT c.*, u.username,
               COALESCE(SUM(v.vote_type), 0) as vote_score
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        LEFT JOIN votes v ON c.id = v.comment_id 
        WHERE c.post_id = ?
        GROUP BY c.id
        ORDER BY c.parent_id IS NULL DESC, c.created_at ASC
    ");
    $stmt->execute([$post_id]);
    $all_comments = $stmt->fetchAll();
    
    // Organize comments in threaded structure
    $comments = [];
    $replies = [];
    
    foreach ($all_comments as $comment) {
        if ($comment['parent_id'] === null) {
            $comments[] = $comment;
        } else {
            $replies[$comment['parent_id']][] = $comment;
        }
    }
}

// Handle post creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_post'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $post_type = $_POST['post_type'];
    $link_url = trim($_POST['link_url']);
    
    if (empty($title)) {
        $error = 'Title is required.';
    } else {
        $pdo = getConnection();
        $file_path = null;
        
        // Handle file upload for image/video
        if (($post_type == 'image' || $post_type == 'video') && isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ($post_type == 'image') ? ['jpg', 'jpeg', 'png', 'gif'] : ['mp4', 'webm', 'ogg'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $file_name = uniqid() . '.' . $file_extension;
                $file_path = $upload_dir . $file_name;
                
                if (!move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
                    $error = 'File upload failed.';
                    $file_path = null;
                }
            } else {
                $error = 'Invalid file type.';
            }
        }
        
        if (!$error) {
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, post_type, file_path, link_url) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$_SESSION['user_id'], $title, $content, $post_type, $file_path, $link_url])) {
                $new_post_id = $pdo->lastInsertId();
                header("Location: post.php?id=$new_post_id");
                exit;
            } else {
                $error = 'Failed to create post.';
            }
        }
    }
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_comment'])) {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
    
    $comment_content = trim($_POST['comment_content']);
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    
    if (empty($comment_content)) {
        $error = 'Comment cannot be empty.';
    } else {
        $pdo = getConnection();
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, parent_id, content) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$post_id, $_SESSION['user_id'], $parent_id, $comment_content])) {
            header("Location: post.php?id=$post_id");
            exit;
        } else {
            $error = 'Failed to add comment.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $post ? sanitize($post['title']) . ' - ' : 'Create Post - ' ?>reddit</title>
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
        
        .form-container, .post-detail, .comment-section {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn {
            background-color: #ff4500;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
        }
        
        .btn:hover {
            background-color: #e03e00;
        }
        
        .error {
            background-color: #fee;
            color: #c33;
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            border: 1px solid #fcc;
        }
        
        .post-title {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .post-meta {
            color: #666;
            margin-bottom: 1rem;
        }
        
        .post-content {
            color: #333;
            margin-bottom: 1rem;
        }
        
        .post-image, .post-video {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        .vote-section {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .vote-btn {
            background: none;
            border: 1px solid #ddd;
            padding: 0.5rem;
            cursor: pointer;
            border-radius: 4px;
        }
        
        .vote-score {
            font-weight: bold;
            min-width: 40px;
            text-align: center;
        }
        
        .comment {
            border-left: 3px solid #ddd;
            padding-left: 1rem;
            margin-bottom: 1rem;
        }
        
        .comment-reply {
            margin-left: 2rem;
            border-left-color: #ff4500;
        }
        
        .comment-meta {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .comment-content {
            margin-bottom: 0.5rem;
        }
        
        .comment-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .reply-form {
            margin-top: 1rem;
            display: none;
        }
        
        .show-reply {
            color: #ff4500;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .file-type-options {
            margin-bottom: 1rem;
        }
        
        .file-type-options input[type="radio"] {
            width: auto;
            margin-right: 0.5rem;
        }
        
        .file-type-options label {
            display: inline;
            margin-right: 1rem;
            font-weight: normal;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><a href="index.php" style="color: white; text-decoration: none;">reddit</a></h1>
        <div>
            <?php if (isLoggedIn()): ?>
                <span>Welcome, <?= sanitize(getCurrentUser()['username']) ?>!</span>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <?php if ($error): ?>
            <div class="error"><?= sanitize($error) ?></div>
        <?php endif; ?>

        <?php if (!$post): ?>
            <!-- Create Post Form -->
            <div class="form-container">
                <h2>Create New Post</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Title:</label>
                        <input type="text" id="title" name="title" required 
                               value="<?= isset($_POST['title']) ? sanitize($_POST['title']) : '' ?>">
                    </div>

                    <div class="form-group">
                        <label>Post Type:</label>
                        <div class="file-type-options">
                            <label><input type="radio" name="post_type" value="text" checked> Text</label>
                            <label><input type="radio" name="post_type" value="image"> Image</label>
                            <label><input type="radio" name="post_type" value="link"> Link</label>
                            <label><input type="radio" name="post_type" value="video"> Video</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="content">Content (optional):</label>
                        <textarea id="content" name="content"><?= isset($_POST['content']) ? sanitize($_POST['content']) : '' ?></textarea>
                    </div>

                    <div class="form-group" id="file-upload" style="display: none;">
                        <label for="file">Upload File:</label>
                        <input type="file" id="file" name="file" accept="">
                    </div>

                    <div class="form-group" id="link-input" style="display: none;">
                        <label for="link_url">Link URL:</label>
                        <input type="url" id="link_url" name="link_url" 
                               value="<?= isset($_POST['link_url']) ? sanitize($_POST['link_url']) : '' ?>">
                    </div>

                    <button type="submit" name="create_post" class="btn">Create Post</button>
                </form>
            </div>
        <?php else: ?>
            <!-- Post Detail View -->
            <div class="post-detail">
                <h1 class="post-title"><?= sanitize($post['title']) ?></h1>
                
                <div class="post-meta">
                    By <?= sanitize($post['username']) ?> • 
                    <?= date('M j, Y g:i A', strtotime($post['created_at'])) ?>
                </div>

                <?php if (isLoggedIn()): ?>
                    <div class="vote-section">
                        <form style="display: inline;" action="vote.php" method="POST">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                            <input type="hidden" name="vote_type" value="1">
                            <button type="submit" class="vote-btn">▲</button>
                        </form>
                        
                        <span class="vote-score"><?= $post['vote_score'] ?></span>
                        
                        <form style="display: inline;" action="vote.php" method="POST">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                            <input type="hidden" name="vote_type" value="-1">
                            <button type="submit" class="vote-btn">▼</button>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if ($post['content']): ?>
                    <div class="post-content"><?= nl2br(sanitize($post['content'])) ?></div>
                <?php endif; ?>

                <?php if ($post['post_type'] == 'image' && $post['file_path']): ?>
                    <img src="<?= sanitize($post['file_path']) ?>" alt="Post image" class="post-image">
                <?php elseif ($post['post_type'] == 'video' && $post['file_path']): ?>
                    <video controls class="post-video">
                        <source src="<?= sanitize($post['file_path']) ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                <?php elseif ($post['post_type'] == 'link' && $post['link_url']): ?>
                    <div class="post-content">
                        <a href="<?= sanitize($post['link_url']) ?>" target="_blank" rel="noopener">
                            <?= sanitize($post['link_url']) ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Comment Section -->
            <div class="comment-section">
                <h3>Comments</h3>

                <?php if (isLoggedIn()): ?>
                    <form method="POST" style="margin-bottom: 2rem;">
                        <div class="form-group">
                            <textarea name="comment_content" placeholder="Add a comment..." required></textarea>
                        </div>
                        <button type="submit" name="add_comment" class="btn">Add Comment</button>
                    </form>
                <?php endif; ?>

                <?php foreach ($comments as $comment): ?>
                    <div class="comment">
                        <div class="comment-meta">
                            <?= sanitize($comment['username']) ?> • 
                            <?= date('M j, Y g:i A', strtotime($comment['created_at'])) ?>
                            
                            <?php if (isLoggedIn()): ?>
                                <span class="vote-section" style="margin-left: 1rem;">
                                    <form style="display: inline;" action="vote.php" method="POST">
                                        <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                        <input type="hidden" name="vote_type" value="1">
                                        <button type="submit" class="vote-btn" style="padding: 0.2rem;">▲</button>
                                    </form>
                                    
                                    <span class="vote-score"><?= $comment['vote_score'] ?></span>
                                    
                                    <form style="display: inline;" action="vote.php" method="POST">
                                        <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                        <input type="hidden" name="vote_type" value="-1">
                                        <button type="submit" class="vote-btn" style="padding: 0.2rem;">▼</button>
                                    </form>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="comment-content"><?= nl2br(sanitize($comment['content'])) ?></div>
                        
                        <?php if (isLoggedIn()): ?>
                            <div class="comment-actions">
                                <span class="show-reply" onclick="toggleReply(<?= $comment['id'] ?>)">Reply</span>
                            </div>
                            
                            <form method="POST" class="reply-form" id="reply-<?= $comment['id'] ?>">
                                <input type="hidden" name="parent_id" value="<?= $comment['id'] ?>">
                                <div class="form-group">
                                    <textarea name="comment_content" placeholder="Reply..." required></textarea>
                                </div>
                                <button type="submit" name="add_comment" class="btn">Reply</button>
                            </form>
                        <?php endif; ?>

                        <!-- Show replies -->
                        <?php if (isset($replies[$comment['id']])): ?>
                            <?php foreach ($replies[$comment['id']] as $reply): ?>
                                <div class="comment comment-reply">
                                    <div class="comment-meta">
                                        <?= sanitize($reply['username']) ?> • 
                                        <?= date('M j, Y g:i A', strtotime($reply['created_at'])) ?>
                                        
                                        <?php if (isLoggedIn()): ?>
                                            <span class="vote-section" style="margin-left: 1rem;">
                                                <form style="display: inline;" action="vote.php" method="POST">
                                                    <input type="hidden" name="comment_id" value="<?= $reply['id'] ?>">
                                                    <input type="hidden" name="vote_type" value="1">
                                                    <button type="submit" class="vote-btn" style="padding: 0.2rem;">▲</button>
                                                </form>
                                                
                                                <span class="vote-score"><?= $reply['vote_score'] ?></span>
                                                
                                                <form style="display: inline;" action="vote.php" method="POST">
                                                    <input type="hidden" name="comment_id" value="<?= $reply['id'] ?>">
                                                    <input type="hidden" name="vote_type" value="-1">
                                                    <button type="submit" class="vote-btn" style="padding: 0.2rem;">▼</button>
                                                </form>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="comment-content"><?= nl2br(sanitize($reply['content'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Handle post type selection
        const postTypeRadios = document.querySelectorAll('input[name="post_type"]');
        const fileUpload = document.getElementById('file-upload');
        const linkInput = document.getElementById('link-input');
        const fileInput = document.getElementById('file');

        postTypeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                fileUpload.style.display = 'none';
                linkInput.style.display = 'none';
                
                if (this.value === 'image') {
                    fileUpload.style.display = 'block';
                    fileInput.setAttribute('accept', 'image/*');
                } else if (this.value === 'video') {
                    fileUpload.style.display = 'block';
                    fileInput.setAttribute('accept', 'video/*');
                } else if (this.value === 'link') {
                    linkInput.style.display = 'block';
                }
            });
        });

        // Toggle reply form
        function toggleReply(commentId) {
            const replyForm = document.getElementById('reply-' + commentId);
            replyForm.style.display = replyForm.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>