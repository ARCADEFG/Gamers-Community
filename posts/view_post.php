<?php
// posts/view_post.php - View a single post, allow read for all, like/comment only for authenticated
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../config/db.php';

$postId = $_GET['id'] ?? 0;
$uid = $_SESSION['user_id'] ?? null; // Get user ID from session

// Fetch post details
$stmt = $conn->prepare("SELECT posts.id, posts.title, posts.content, posts.created_at, posts.user_id, users.username, posts.media_path, posts.media_type, posts.game_name, (SELECT COUNT(*) FROM post_likes WHERE post_id = posts.id) as like_count" . ($uid ? ", (SELECT 1 FROM post_likes WHERE post_id = ? AND user_id = ?) as liked" : "") . " FROM posts JOIN users ON users.id = posts.user_id WHERE posts.id = ?");

if ($stmt === false) {
    die('Prepare failed: ' . htmlspecialchars($conn->error));
}

if ($uid) {
    $stmt->bind_param("iii", $postId, $uid, $postId); // postId for liked subquery, uid, then postId for main query
} else {
    $stmt->bind_param("i", $postId);
}

$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if (!$post) {
    // Redirect or show error if post not found
    header("Location: all.php?error=postnotfound");
    exit;
}

$isOwner = $uid && $uid == $post['user_id'];
$current_post_liked = $post['liked'] ?? 0;
$current_like_count = $post['like_count'];

// Fetch comments
$comments_stmt = $conn->prepare("SELECT comments.*, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE comments.post_id = ? ORDER BY comments.created_at ASC");
$comments_stmt->bind_param("i", $postId);
$comments_stmt->execute();
$comments_result = $comments_stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($post['title']) ?> - Gamers Community</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Orbitron:wght@700;800&family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Specific styles for view_post.php */
        .post-container {
    background: #23233a;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.18);
    overflow: hidden;
    margin-bottom: 2em;
    position: relative; /* Added for better z-index control */
    color: #f4f4f4; /* Base text color */
    font-family: 'Montserrat', sans-serif; /* Base font */
    z-index: 1; 
}

.post-header {
    padding: 1.5em;
    border-bottom: 1px solid #35355a;
    background: linear-gradient(to right, #23233a, #2a2a45);
}

.post-header h1 {
    font-family: 'Orbitron', sans-serif;
    font-size: 2.5em;
    margin: 0 0 0.2em 0;
    letter-spacing: 1px;
    font-weight: 800;
    color: var(--primary);
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.post-meta {
    color: #b0b0b0;
    font-size: 0.95em;
    display: flex;
    gap: 1em;
    align-items: center;
}

.post-game-name {
    font-family: 'Orbitron', sans-serif;
    color: #8f94fb;
    font-size: 1.1em;
    margin: 0.5em 0;
    display: inline-block;
    padding: 0.3em 0.8em;
    background: rgba(143, 148, 251, 0.1);
    border-radius: 20px;
}

.post-content-body {
    padding: 1.5em;
    font-size: 1.15em;
    line-height: 1.7;
    color: inherit; /* Inherits from .post-container */
}

.media-container {
    text-align: center;
    padding: 1em 0;
    min-height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #1d1d32;
    margin: 0 1.5em 1.5em;
    border-radius: 8px;
    overflow: hidden;
}

.media-container img,
.media-container video {
    max-width: 96%;
    max-height: 340px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    object-fit: contain; /* Changed from 'cover' to 'contain' for better media display */
    transition: transform 0.3s ease;
}

.media-container img:hover,
.media-container video:hover {
    transform: scale(1.02);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .post-header h1 {
        font-size: 2em;
    }
    
    .media-container {
        margin: 0 0 1em 0;
        border-radius: 0;
    }
    
    .post-content-body {
        padding: 1em;
        font-size: 1em;
    }
}

        .post-actions-footer { display: flex; align-items: center; justify-content: space-between; padding: 0 1.5em 1.5em 1.5em; }
        .like-comment-group { display: flex; align-items: center; gap: 1.2em; }
        .like-btn, .comment-link-btn { 
            background: linear-gradient(90deg,#4e54c8,#8f94fb); 
            color:#fff; padding:0.7em 1.7em; border-radius:999px; border:none;
            font-family:Orbitron,sans-serif; font-weight:700; font-size:1.15em;
            display:inline-flex; align-items:center; gap:0.5em; box-shadow:0 2px 8px rgba(0,0,0,0.10);
            cursor:pointer; transition:background 0.2s, opacity 0.2s;
            text-decoration: none; /* For links */
        }
        .like-btn:hover, .comment-link-btn:hover { opacity:0.9; }
        .like-btn.liked { background:linear-gradient(90deg,#e74c3c,#ffb6b6); }
        .like-count-display { font-size:1.13em;color:#8f94fb;font-family:Orbitron,sans-serif;font-weight:700;display:flex;align-items:center;gap:0.4em; }
        .like-count-display i { transition:color 0.2s; }
        .like-count-display .liked-heart { color:#e74c3c; }
        .edit-delete-buttons { display: flex; gap: 0.7em; }
        .edit-delete-buttons a, .edit-delete-buttons button { 
            padding:0.7em 1.5em; border-radius:999px; text-decoration:none;
            font-family:Orbitron,sans-serif; font-weight:700; box-shadow:0 2px 8px rgba(0,0,0,0.10);
            display:inline-flex; align-items:center; gap:0.5em; font-size:1.05em; transition:opacity 0.2s;
        }
        .edit-delete-buttons a { background:linear-gradient(90deg,#4e54c8,#8f94fb); color:#fff; }
        .edit-delete-buttons button { background-color:#e74c3c; color:#fff; border:none; cursor:pointer; }
        .edit-delete-buttons a:hover, .edit-delete-buttons button:hover { opacity:0.9; }

        /* Comments Section */
        .comments-section { background:#19192b; border-radius:12px; padding:2em; margin-top:2em; box-shadow:0 2px 12px rgba(0,0,0,0.18); }
        .comments-section h3 { font-family:Orbitron,sans-serif; color:var(--primary); font-size:1.8em; margin-bottom:1em; }
        .comment { background:#23233a; border-radius:8px; padding:1.2em 1.5em; margin-bottom:1em; box-shadow:0 1px 6px rgba(0,0,0,0.1); }
        .comment-meta { font-size:0.9em; color:#b0b0b0; margin-bottom:0.5em; }
        .comment-meta strong { color:#8f94fb; }
        .comment-content { font-size:1em; line-height:1.6; color:#f4f4f4; }

        /* Add Comment Form */
        .comment-form { margin-top:2em; padding-top:1.5em; border-top:1px solid #35355a; }
        .comment-form textarea { 
            width:calc(100% - 22px); padding:1em; border-radius:7px; border:1px solid #35355a;
            background:#1e1e2e; color:#f4f4f4; font-size:1em; font-family:Montserrat,sans-serif; resize:vertical; min-height:80px;
        }
        .comment-form button { 
            margin-top:1em; padding:0.8em 1.5em; font-size:1.05em; font-family:Orbitron,sans-serif;
            font-weight:700; background:linear-gradient(90deg,#4e54c8,#8f94fb); color:#fff; border:none;
            border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.13); cursor:pointer; transition:background 0.2s;
        }
        .comment-form button:hover { opacity:0.9; }
        /* Adjusted prompt styles for general use */
        .auth-prompt { background:#19192b;padding:1em 1.5em;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.13);text-align:center;font-family:Montserrat,sans-serif; margin-top:1.5em; }
        .auth-prompt p { color:#e0e0e0;font-size:1.1em; }
        .auth-prompt a { color:#8f94fb;text-decoration:none;font-weight:600; }

    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>
<main class="main-content" style="max-width:1000px;margin:2em auto;">
    <?php if ($post): ?>
        <article class="post-container">
            <header class="post-header">
                <div class="post-game-name"><i class="fa-solid fa-gamepad" style="margin-right:0.4em;"></i><?= htmlspecialchars($post['game_name']) ?></div>
                <h1><?= htmlspecialchars($post['title']) ?></h1>
                <p class="post-meta">by <strong><?= htmlspecialchars($post['username']) ?></strong> on <?= $post['created_at'] ?></p>
            </header>

            <div class="media-container">
                <?php
                $media_path = $post['media_path'];
                $media_type = $post['media_type'];
                if (!empty($media_path) && $media_type === 'image') {
                    $mediaPath = (strpos($media_path, 'uploads/') === 0) ? '../' . $media_path : '../posts/' . $media_path;
                    echo '<img src="' . htmlspecialchars($mediaPath) . '" alt="Post Image">';
                } elseif (!empty($media_path) && $media_type === 'video') {
                    $mediaPath = (strpos($media_path, 'uploads/') === 0) ? '../' . $media_path : '../posts/' . $media_path;
                    echo '<video controls><source src="' . htmlspecialchars($mediaPath) . '" type="video/mp4">Your browser does not support the video tag.</video>';
                } else {
                    echo '<div style="width:100%;height:160px;display:flex;align-items:center;justify-content:center;background:#23233a;">'
                        . '<i class="fa-regular fa-image" style="font-size:3em;color:#35355a;"></i><br><span style="color:#b0b0b0;font-size:0.9em;margin-top:0.5em;">No media attached</span></div>';
                }
                ?>
            </div>

            <div class="post-content-body">
                <?= nl2br(htmlspecialchars($post['content'])) ?>
            </div>

            <div class="post-actions-footer">
                <div class="like-comment-group">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <form class="like-form" data-post-id="<?= $post['id'] ?>">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                            <button type="submit" class="like-btn<?= $current_post_liked ? ' liked' : '' ?>">
                                <?= $current_post_liked ? '<i class="fa-solid fa-thumbs-down"></i> Unlike' : '<i class="fa-solid fa-thumbs-up"></i> Like' ?>
                            </button>
                        </form>
                        <span class="like-count-display">
                            <i class="fa-solid fa-heart<?= $current_post_liked ? ' liked-heart' : '' ?>"></i>
                            <span class="count"><?= $current_like_count ?></span> Like<?= $current_like_count == 1 ? '' : 's' ?>
                        </span>
                        <a href="#comments" class="comment-link-btn" style="background:linear-gradient(90deg,#2575fc,#6a11cb); margin-left:10px;"><i class="fa-solid fa-comment"></i> Comment</a>
                    <?php else: ?>
                        <!-- Unauthenticated: Like button becomes a link to register -->
                        <a href="../auth/register.php" class="like-btn"><i class="fa-solid fa-thumbs-up"></i> Like</a>
                        <span class="like-count-display">
                            <i class="fa-solid fa-heart"></i>
                            <span class="count"><?= $current_like_count ?></span> Like<?= $current_like_count == 1 ? '' : 's' ?>
                        </span>
                        <!-- Unauthenticated: Comment button becomes a link to register -->
                        <a href="../auth/register.php" class="comment-link-btn" style="background:linear-gradient(90deg,#2575fc,#6a11cb); margin-left:10px;"><i class="fa-solid fa-comment"></i> Comment</a>
                    <?php endif; ?>
                </div>
                <?php if ($isOwner): ?>
                    <div class="edit-delete-buttons">
                        <a href="edit_post.php?id=<?= $post['id'] ?>">✏️ Edit</a>
                        <form action="delete_post.php" method="GET" onsubmit="return confirm('Are you sure you want to delete this post?');">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($post['id']) ?>">
                            <button type="submit">🗑 Delete</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </article>

    <section class="comments-section" id="comments">
    <h3><i class="fa-solid fa-comments" style="margin-right:0.5em;"></i>Comments</h3>
    <?php 
    // Make sure $comments_result is properly set before using it
    if (isset($comments_result) && $comments_result->num_rows > 0): ?>
        <div class="comments-list">
            <?php while ($comment = $comments_result->fetch_assoc()): ?>
                <div class="comment">
                    <p class="comment-meta"><strong><?= htmlspecialchars($comment['username']) ?></strong> on <?= $comment['created_at'] ?></p>
                    <p class="comment-content"><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p style="color:#b0b0b0;text-align:center;">No comments yet. Be the first to comment!</p>
    <?php endif; ?>

    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="comment-form">
            <h4>Add a Comment</h4>
            <form action="comment_post.php" method="POST">
                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                <textarea name="comment_text" placeholder="Write your comment..." required></textarea>
                <button type="submit">Post Comment</button>
            </form>
        </div>
    <?php else: ?>
        <div class="auth-prompt">
            <p>Please <a href="../auth/login.php">login</a> or <a href="../auth/register.php">register</a> to add a comment.</p>
        </div>
    <?php endif; ?>
    </section>

    <?php else: // Post not found ?>
        <div style="text-align:center;margin-top:5em;font-size:1.2em;color:#e74c3c;">
            <p>❌ Post ID missing or invalid.</p>
            <p><a href="all.php" style="color:#8f94fb;text-decoration:none;">Go back to all posts</a></p>
        </div>
    <?php endif; ?>
</main>
<?php include '../includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.like-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission

            const postId = this.dataset.postId; // Get post ID from data attribute
            const likeButton = this.querySelector('.like-btn');
            const likeCountDisplay = form.closest('.post-actions-footer').querySelector('.like-count-display .count');
            const heartIcon = form.closest('.post-actions-footer').querySelector('.fa-heart');

            fetch('like_post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest' // Identify as AJAX request
                },
                body: `post_id=${postId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    likeCountDisplay.textContent = data.like_count; // Update count
                    if (data.is_liked) {
                        likeButton.innerHTML = '<i class="fa-solid fa-thumbs-down"></i> Unlike';
                        likeButton.classList.add('liked');
                        likeButton.style.background = 'linear-gradient(90deg,#e74c3c,#ffb6b6)';
                        heartIcon.classList.add('liked-heart');
                    } else {
                        likeButton.innerHTML = '<i class="fa-solid fa-thumbs-up"></i> Like';
                        likeButton.classList.remove('liked');
                        likeButton.style.background = 'linear-gradient(90deg,#4e54c8,#8f94fb)';
                        heartIcon.classList.remove('liked-heart');
                    }
                } else {
                    // If not successful, and it's because login is required, redirect to register
                    if (data.message === 'Login required to like posts.') {
                        alert(data.message + ' Please register or log in.');
                        window.location.href = '../auth/register.php'; // Redirect to register
                    } else {
                        alert(data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    });
});
</script>
</body>
</html>
