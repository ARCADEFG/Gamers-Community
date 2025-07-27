<?php
session_start();
include '../config/db.php';

define('FS_BASE', dirname(__DIR__));
define('WEB_BASE', '/Gamers_Community/');

if (!isset($_GET['id'])) {
    header("Location: " . WEB_BASE . "communities/community_view.php");
    exit;
}

$community_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'] ?? null;

// Get community info
$community = [];
$is_member = false;
try {
    $stmt = $conn->prepare("SELECT c.*, u.username as creator_name FROM communities c JOIN users u ON c.created_by = u.id WHERE c.id = ?");
    $stmt->bind_param("i", $community_id);
    $stmt->execute();
    $community = $stmt->get_result()->fetch_assoc();
    
    if (!$community) {
        header("Location: " . WEB_BASE . "communities/community_view.php");
        exit;
    }

    // Check if user is member
    if ($user_id) {
        $stmt = $conn->prepare("SELECT 1 FROM community_members WHERE community_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $community_id, $user_id);
        $stmt->execute();
        $is_member = $stmt->get_result()->num_rows > 0;
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
}

// Handle new post submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_post']) && $is_member) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $game_name = trim($_POST['game_name']);
    
    // Handle file upload
    $media_path = null;
    $media_type = null;
    
    if (!empty($_FILES['media']['name'])) {
        $upload_dir = FS_BASE . '/uploads/community_posts/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'webm'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $file_name = uniqid() . '.' . $file_ext;
            $target_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['media']['tmp_name'], $target_path)) {
                $media_path = WEB_BASE . 'uploads/community_posts/' . $file_name;
                $media_type = in_array($file_ext, ['mp4', 'webm']) ? 'video' : 'image';
            }
        }
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO posts (user_id, community_id, title, content, media_path, media_type, game_name) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssss", $user_id, $community_id, $title, $content, $media_path, $media_type, $game_name);
        $stmt->execute();
        
        $_SESSION['success'] = "Post created successfully!";
        header("Location: " . WEB_BASE . "communities/community_view.php?id=" . $community_id);
        exit;
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        $_SESSION['error'] = "Failed to create post";
    }
}

// Get posts for this community
$posts = [];
try {
    $query = "SELECT posts.id, posts.title, posts.content, posts.created_at, posts.user_id, users.username, 
              posts.media_path, posts.media_type, posts.game_name, 
              (SELECT COUNT(*) FROM post_likes WHERE post_id=posts.id) as like_count" . 
              ($user_id ? ", (SELECT 1 FROM post_likes WHERE post_id=posts.id AND user_id=$user_id) as liked" : "") . 
              " FROM posts JOIN users ON users.id = posts.user_id 
              WHERE posts.community_id = ? 
              ORDER BY posts.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $community_id);
    $stmt->execute();
    $posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Add the additional properties to each post
    foreach ($posts as &$post) {
        $post['canInteract'] = $is_member; // Only members can interact
        $post['current_like_count'] = $post['like_count'] ?? 0;
        $post['current_post_liked'] = $post['liked'] ?? 0;
        $post['isOwner'] = isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $post['user_id']);
    }
    unset($post); // Break the reference
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
}

$header_path = FS_BASE . '/includes/header.php';
$footer_path = FS_BASE . '/includes/footer.php';
$has_header = file_exists($header_path);
$has_footer = file_exists($footer_path);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($community['name']) ?> | Nexus</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;800&family=Montserrat:wght@400;500;600;700&family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= WEB_BASE ?>assets/css/style.css">
    <style>
        /* Centered Layout Container */
        .community-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Community Header */
        .community-header {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 2rem;
            margin: 0 auto 2rem;
            border: 1px solid var(--primary);
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            max-width: 800px;
            text-align: center;
        }
        
        .community-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 2rem;
            color: var(--accent);
            margin-bottom: 0.5rem;
        }
        
        .community-description {
            color: var(--light);
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        
        .community-meta {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        .member-badge {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 0.3rem 0.6rem;
            border-radius: 50px;
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }

        /* Create Post Button */
        .create-post-container {
            text-align: center;
            margin: 2rem auto;
            max-width: 800px;
        }
        
        .create-post-btn {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.1em;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .create-post-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(106, 17, 203, 0.2);
        }

        /* Posts Section */
        .posts-section {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .posts-section h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.5em;
            color: var(--primary);
            margin-bottom: 1.2em;
            text-align: center;
        }
        
        .posts-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .post-card {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .post-card-header {
            margin-bottom: 15px;
        }
        
        .post-game {
            color: var(--accent);
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        
        .post-title {
            margin: 0;
            font-size: 1.3em;
        }
        
        .post-title a {
            color: white;
            text-decoration: none;
        }
        
        .post-title a:hover {
            color: var(--secondary);
        }
        
        .meta {
            color: var(--text-muted);
            font-size: 0.8em;
        }
        
        .media-container {
    margin: 12px 0;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: #23233a;
    border-radius: 8px;
    overflow: hidden;
    max-height: 500px;
}

.media-container img,
.media-container video {
    max-width: 100%;
    max-height: 400px;
    width: auto;
    height: auto;
    border-radius: 8px;
    object-fit: contain;
    display: block;
    margin: 0 auto;
}

/* For landscape-oriented media */
.media-container img[width] > [height],
.media-container video[width] > [height] {
    max-height: 400px;
    width: 100%;
}

/* For portrait-oriented media */
.media-container img[height] > [width],
.media-container video[height] > [width] {
    max-width: 100%;
    height: auto;
}
        
        .post-content {
            margin: 15px 0;
            line-height: 1.6;
        }
        
        .post-action-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1em 1.5em;
            border-top: 1px solid #35355a;
            background-color: #1c1c2e;
            flex-wrap: wrap;
            gap: 1em;
        }
        
        .like-group {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .like-btn, .comment-btn {
            padding: 8px 15px;
            border-radius: 20px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .like-btn {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            color: white;
        }
        
        .like-btn.liked {
            background: linear-gradient(90deg, #e74c3c, #ffb6b6);
        }
        
        .comment-btn {
            background: var(--input-bg);
            color: white;
        }
        
        .like-count-display {
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--text-muted);
            font-size: 0.9em;
        }
        
        .fa-heart {
            color: var(--text-muted);
        }
        
        .fa-heart.liked-heart {
            color: var(--accent);
        }
        
        .edit-delete-buttons {
            display: flex;
            gap: 10px;
        }
        
        .edit-delete-buttons a,
        .edit-delete-buttons button {
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9em;
        }
        
        .edit-delete-buttons a {
            background: var(--secondary);
        }
        
        .edit-delete-buttons button {
            background: var(--accent);
            border: none;
            cursor: pointer;
        }
        
        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .community-container {
                padding: 10px;
            }
            
            .community-header {
                padding: 1.5rem;
            }
            
            .community-title {
                font-size: 1.5rem;
            }
            
            .post-card {
                padding: 15px;
            }
            
            .post-action-buttons {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .like-group {
                width: 100%;
                justify-content: space-between;
            }
            
            .edit-delete-buttons {
                width: 100%;
                justify-content: flex-end;
            }

            media-container {
            max-height: 350px;
            }
    
            .media-container img,
            .media-container video {
            max-height: 300px;
            }
        }
        
        @media (max-width: 480px) {
            .community-header {
                padding: 1rem;
            }
            
            .create-post-btn {
                width: 100%;
                justify-content: center;
            }
            
            .post-title {
                font-size: 1.1em;
            }

            .media-container {
            max-height: 250px;
            }
    
            .media-container img,
            .media-container video {
            max-height: 200px;
            }   
        }
    </style>
</head>
<body>
    <?php if ($has_header) include $header_path; ?>

    <div class="community-container">
        <!-- Community Header -->
        <div class="community-header">
            <h1 class="community-title"><?= htmlspecialchars($community['name']) ?></h1>
            <p class="community-description"><?= htmlspecialchars($community['description']) ?></p>
            <div class="community-meta">
                Created by <?= htmlspecialchars($community['creator_name']) ?> on <?= date('M d, Y', strtotime($community['created_at'])) ?>
                <?php if ($is_member): ?>
                    <span class="member-badge"><i class="fas fa-check"></i> Member</span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Create Post Button (Conditional for members) -->
        <?php if ($is_member): ?>
            <div class="create-post-container">
                <a href="<?= WEB_BASE ?>posts/create_post.php?community_id=<?= $community_id ?>" class="create-post-btn">
                    <i class="fas fa-plus"></i> Create New Post
                </a>
            </div>
        <?php elseif ($user_id): ?>
            <div class="create-post-container">
                <p>Join this community to create posts</p>
            </div>
        <?php else: ?>
            <div class="create-post-container">
                <a href="<?= WEB_BASE ?>auth/login.php" class="create-post-btn">
                    <i class="fas fa-sign-in-alt"></i> Login to Create Posts
                </a>
            </div>
        <?php endif; ?>
        
        <!-- Posts Section -->
<div class="posts-section">
    <h3>Community Posts</h3>
    <div class="posts-list">
        <?php if (empty($posts)): ?>
            <p style="text-align:center;color:var(--text-muted);font-size:1.1em;margin-top:2em;">No posts found. Be the first to create one!</p>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <article class="post-card">
                    <header class="post-card-header">
                        <div class="post-game"><i class="fa-solid fa-gamepad"></i> <?= htmlspecialchars($post['game_name']) ?></div>
                        <h3 class="post-title"><a href="<?= WEB_BASE ?>posts/view_post.php?id=<?= $post['id'] ?>"><?= htmlspecialchars($post['title']) ?></a></h3>
                        <span class="meta">by <?= htmlspecialchars($post['username']) ?> on <?= $post['created_at'] ?></span>
                    </header>
                    
                    <?php if (!empty($post['media_path'])): ?>
                        <?php if ($post['media_type'] === 'image'): ?>
                            <div class="media-container">
                                <img src="<?= WEB_BASE . htmlspecialchars($post['media_path']) ?>" alt="Post Image" style="max-width:100%;height:auto;border-radius:8px;">
                            </div>
                        <?php else: ?>
                            <div class="media-container">
                                <video controls style="max-width:100%;border-radius:8px;">
                                    <source src="<?= WEB_BASE . htmlspecialchars($post['media_path']) ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="media-container" style="background:#23233a;display:flex;align-items:center;justify-content:center;height:160px;border-radius:8px;">
                            <i class="fa-regular fa-image" style="font-size:3em;color:#35355a;"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="post-content"><?= nl2br(htmlspecialchars($post['content'])) ?></div>
                    
                    <div class="post-action-buttons">
                        <div class="like-group">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <?php if ($post['canInteract']): ?>
                                    <form class="like-form" data-post-id="<?= $post['id'] ?>">
                                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                        <button type="submit" class="like-btn<?= $post['current_post_liked'] ? ' liked' : '' ?>">
                                            <?= ($post['current_post_liked'] ? '<i class="fa-solid fa-thumbs-down"></i> Unlike' : '<i class="fa-solid fa-thumbs-up"></i> Like') ?>
                                        </button>
                                    </form>
                                    
                                    <span class="like-count-display">
                                        <i class="fa-solid fa-heart<?= $post['current_post_liked'] ? ' liked-heart' : '' ?>"></i>
                                        <span class="count"><?= $post['current_like_count'] ?></span> Like<?= ($post['current_like_count'] == 1 ? '' : 's') ?>
                                    </span>
                                    
                                    <a href="posts/view_post.php?id=<?= $post['id'] ?>#comments" class="comment-btn"><i class="fa-solid fa-comment"></i> Comment</a>
                                <?php else: ?>
                                    <span class="like-count-display">
                                        <i class="fa-solid fa-heart"></i>
                                        <span class="count"><?= $post['current_like_count'] ?></span> Like<?= ($post['current_like_count'] == 1 ? '' : 's') ?>
                                    </span>
                                    <a href="community_view.php?id=<?= $community_id ?>" class="comment-btn"><i class="fa-solid fa-comment"></i> Join to comment</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="auth/register.php" class="like-btn"><i class="fa-solid fa-thumbs-up"></i> Like</a>
                                <span class="like-count-display">
                                    <i class="fa-solid fa-heart"></i>
                                    <span class="count"><?= $post['current_like_count'] ?></span> Like<?= ($post['current_like_count'] == 1 ? '' : 's') ?>
                                </span>
                                <a href="auth/register.php" class="comment-btn"><i class="fa-solid fa-comment"></i> Comment</a>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($post['isOwner']): ?>
                            <div class="edit-delete-buttons">
                                <a href="<?= WEB_BASE ?>posts/edit_post.php?id=<?= $post['id'] ?>">✏️ Edit</a>
                                <form action="<?= WEB_BASE ?>posts/delete_post.php" method="GET" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                    <input type="hidden" name="id" value="<?= $post['id'] ?>">
                                    <button type="submit">🗑 Delete</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
    </div>

    <?php if ($has_footer) include $footer_path; ?>

    <script>
        // Like functionality
        document.querySelectorAll('.like-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const postId = this.dataset.postId;
                const likeBtn = this.querySelector('.like-btn');
                const likeCount = this.closest('.like-group').querySelector('.count');
                const heartIcon = this.closest('.like-group').querySelector('.fa-heart');
                
                fetch('<?= WEB_BASE ?>posts/like_post.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `post_id=${postId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        likeBtn.innerHTML = data.liked ? 
                            '<i class="fa-solid fa-thumbs-down"></i> Unlike' : 
                            '<i class="fa-solid fa-thumbs-up"></i> Like';
                        likeBtn.classList.toggle('liked');
                        heartIcon.classList.toggle('liked-heart');
                        likeCount.textContent = data.like_count;
                    }
                });
            });
        });
    </script>
</body>
</html>