<?php 
session_start();

// Define the root directory path
$rootDir = __DIR__; // Points to C:\xampp\htdocs\Gamers_Community

// Include config file using absolute path
require_once $rootDir . '/config/config.php';
require_once $rootDir . '/config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gamers Community</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Orbitron:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6a11cb;
            --secondary: #2575fc;
            --accent: #ff4081;
            --light: #f4f4f4;
            --dark-bg: #121212;
            --card-bg: #2b2b2b;
            --input-bg: #3a3a3a;
            --text-muted: #aaa;
        }

        body {
            margin: 0;
            font-family: 'Montserrat', sans-serif;
            background-color: var(--dark-bg);
            color: white;
            line-height: 1.6;
        }

        /* Main Layout */
        .main-layout {
            display: flex;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            gap: 20px;
        }

        .main-content {
            margin-top: 80px; /* Adjust based on your header height */
            padding: 20px;
            min-height: calc(90vh - [header_height] - [footer_height]);
            z-index: 1; /* Lower than header */
        }

        .posts-section {
            flex: 3;
            margin-top: 20px;
        }

        .sidebar {
            flex: 1;
            min-width: 300px;
            position: sticky;
            top: 100px; /* Below header */
            align-self: flex-start;
            height: calc(100vh - 120px);
            overflow-y: auto;
        }

        /* Post Cards */
        .post-card {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: auto; /* Let it flow naturally */
        }

        .post-card-header {
            margin-bottom: 15px;
            z-index: auto !important;
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

        .meta {
            color: var(--text-muted);
            font-size: 0.8em;
        }

        .media-container {
            margin: 15px 0;
        }

        .media-container img, 
        .media-container video {
            max-width: 100%;
            border-radius: 4px;
        }

        .post-content {
            margin: 15px 0;
            line-height: 1.6;
        }

        /* Action Buttons */
        .post-action-buttons {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1em 1.5em;
        border-top: 1px solid #35355a;
        background-color: #1c1c2e;
        flex-wrap: wrap; /* Optional: for smaller screens */
        gap: 1em; /* Optional: adds spacing between like/comment and edit/delete */
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

        /* Create Post Button */
         .create-post-btn { 
            background:linear-gradient(90deg,#4e54c8,#8f94fb); 
            color:#fff; 
            padding:10px 20px; 
            border-radius:8px; 
            text-decoration:none; 
            font-family:Orbitron,sans-serif; 
            font-weight:700; 
            font-size:1.1em; 
            display:inline-flex; 
            align-items:center; 
            gap:0.5em; 
            box-shadow:0 2px 8px rgba(0,0,0,0.13); 
            transition:background 0.2s; 
        }

        .create-post-btn:hover { 
            opacity:0.9; 
        }

        /* Sidebar Styles */
        .sidebar h3 {
            color: var(--primary);
            border-bottom: 2px solid var(--secondary);
            padding-bottom: 10px;
        }

        .sidebar ul {
            margin-top: 15px;
        }

        .sidebar li {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--input-bg);
        }

        .sidebar a {
            color: var(--secondary);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .sidebar a:hover {
            color: var(--accent);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-layout {
                flex-direction: column;
                padding-top: 70px;
            }
            
            .sidebar {
                position: static;
                height: auto;
                order: -1;
                margin-bottom: 20px;
            }
            
            .main-content {
                margin-top: 20px;
            }
        }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="main-layout">
    <div class="main-content">
        <h2 style="font-family:'Orbitron',sans-serif;font-size:2.2em;margin-bottom:1.2em;letter-spacing:1px;font-weight:800;color:var(--primary);">Welcome to the Gamers Community</h2>
        <?php
        $uid = $_SESSION['user_id'] ?? null;

        // Create Post button
        if (isset($_SESSION['user_id'])): ?>
            <div style="margin-bottom:2em;">
                <a href="posts/create_post.php" class="create-post-btn">
                    <i class="fa-solid fa-plus-circle"></i> Create New Post
                </a>
            </div>
        <?php else: ?>
            <div style="margin-bottom:2em;">
                <a href="auth/register.php" class="create-post-btn">
                    <i class="fa-solid fa-plus-circle"></i> Create New Post
                </a>
            </div>
        <?php endif; ?>

        <section class="posts-section">
            <h3 style="font-family:Orbitron,sans-serif;font-size:1.5em;color:var(--primary);margin-bottom:1.2em;">Recent Posts</h3>
            <div class="posts-list">
                <?php
                $res = $conn->query("SELECT posts.id, posts.title, posts.content, posts.created_at, posts.user_id, users.username, posts.media_path, posts.media_type, posts.game_name, (SELECT COUNT(*) FROM post_likes WHERE post_id=posts.id) as like_count" . (isset($_SESSION['user_id']) ? ", (SELECT 1 FROM post_likes WHERE post_id=posts.id AND user_id=$uid) as liked" : "") . " FROM posts JOIN users ON users.id = posts.user_id ORDER BY posts.created_at DESC LIMIT 10");
                
                if ($res->num_rows > 0) {
                    while ($row = $res->fetch_assoc()) {
                        $isOwner = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $row['user_id'];
                        $current_post_liked = $row['liked'] ?? 0;
                        $current_like_count = $row['like_count'];

                        echo '<article class="post-card">';
                        echo '<header class="post-card-header">';
                        echo '<div class="post-game"><i class="fa-solid fa-gamepad"></i> ' . htmlspecialchars($row['game_name']) . '</div>';
                        echo '<h3 class="post-title"><a href="posts/view_post.php?id=' . $row['id'] . '">' . htmlspecialchars($row['title']) . '</a></h3>';
                        echo '<span class="meta">by ' . htmlspecialchars($row['username']) . ' on ' . $row['created_at'] . '</span>';
                        echo '</header>';
                        
                        // Media display
                        if (!empty($row['media_path']) && $row['media_type'] === 'image') {
                            echo '<div class="media-container"><img src="' . htmlspecialchars($row['media_path']) . '" alt="Post Image"></div>';
                        } elseif (!empty($row['media_path']) && $row['media_type'] === 'video') {
                            echo '<div class="media-container"><video controls><source src="' . htmlspecialchars($row['media_path']) . '" type="video/mp4"></video></div>';
                        } else {
                            echo '<div class="media-container" style="background:#23233a;display:flex;align-items:center;justify-content:center;height:160px;">';
                            echo '<i class="fa-regular fa-image" style="font-size:3em;color:#35355a;"></i>';
                            echo '</div>';
                        }
                        
                        echo '<div class="post-content">' . nl2br(htmlspecialchars($row['content'])) . '</div>';
                        
                        // Action buttons
                        echo '<div class="post-action-buttons">';
                        echo '<div class="like-group">';
                        
                        if (isset($_SESSION['user_id'])) {
                            echo '<form class="like-form" data-post-id="' . $row['id'] . '">';
                            echo '<input type="hidden" name="post_id" value="' . $row['id'] . '">';
                            echo '<button type="submit" class="like-btn' . ($current_post_liked ? ' liked' : '') . '">';
                            echo ($current_post_liked ? '<i class="fa-solid fa-thumbs-down"></i> Unlike' : '<i class="fa-solid fa-thumbs-up"></i> Like');
                            echo '</button>';
                            echo '</form>';
                            
                            echo '<span class="like-count-display">';
                            echo '<i class="fa-solid fa-heart' . ($current_post_liked ? ' liked-heart' : '') . '"></i>';
                            echo '<span class="count">' . $current_like_count . '</span> Like' . ($current_like_count == 1 ? '' : 's') . '</span>';
                            
                            echo '<a href="posts/view_post.php?id=' . $row['id'] . '#comments" class="comment-btn"><i class="fa-solid fa-comment"></i> Comment</a>';
                        } else {
                            echo '<a href="auth/register.php" class="like-btn"><i class="fa-solid fa-thumbs-up"></i> Like</a>';
                            echo '<span class="like-count-display">';
                            echo '<i class="fa-solid fa-heart"></i>';
                            echo '<span class="count">' . $current_like_count . '</span> Like' . ($current_like_count == 1 ? '' : 's') . '</span>';
                            echo '<a href="auth/register.php" class="comment-btn"><i class="fa-solid fa-comment"></i> Comment</a>';
                        }
                        
                        echo '</div>';
                        
                        if ($isOwner) {
                            echo '<div class="edit-delete-buttons">';
                            echo '<a href="posts/edit_post.php?id=' . $row['id'] . '">✏️ Edit</a>';
                            echo '<form action="posts/delete_post.php" method="GET" onsubmit="return confirm(\'Are you sure you want to delete this post?\');">';
                            echo '<input type="hidden" name="id" value="' . $row['id'] . '">';
                            echo '<button type="submit">🗑 Delete</button>';
                            echo '</form>';
                            echo '</div>';
                        }
                        
                        echo '</div>';
                        echo '</article>';
                    }
                } else {
                    echo '<p style="text-align:center;color:var(--text-muted);font-size:1.1em;margin-top:2em;">No posts found. Be the first to create one!</p>';
                }
                ?>
            </div>
        </section>
    </div>
    
    <aside class="sidebar" style="padding: 1em; background-color: #1e1e1e; color: #ffffff; border-radius: 8px; font-family: Orbitron, sans-serif;">

    <!-- Game News & Updates -->
    <h3><i class="fa-solid fa-newspaper" style="margin-right:0.5em;"></i>Game News & Updates</h3>
    <ul style="list-style:none;padding:0;">
        <li style="margin-bottom:1em;"><b>Cyberpunk 2077 2.0 Patch</b><br><span style="color:#b0b0b0;">Major update released with new features and bug fixes.</span></li>
        <li style="margin-bottom:1em;"><b>Valorant New Agent</b><br><span style="color:#b0b0b0;">Riot teases a new agent coming next month.</span></li>
        <li style="margin-bottom:1em;"><b>Fortnite x Marvel Event</b><br><span style="color:#b0b0b0;">Special crossover event live now!</span></li>
        <li style="margin-bottom:1em;"><b>Steam Summer Sale</b><br><span style="color:#b0b0b0;">Huge discounts on top games until July 15.</span></li>
    </ul>

    <!-- Upcoming Releases -->
    <h3><i class="fa-solid fa-calendar-days" style="margin-right:0.5em;"></i>Upcoming Releases</h3>
    <ul style="list-style:none;padding:0;">
        <li style="margin-bottom:0.8em;"><b>Starfield DLC</b> – Aug 12</li>
        <li style="margin-bottom:0.8em;"><b>GTA VI Teaser Trailer</b> – Sep 5</li>
        <li style="margin-bottom:0.8em;"><b>Assassin’s Creed Red</b> – Q4 2025</li>
    </ul>

    <!-- Trending Topics -->
    <h3><i class="fa-solid fa-fire" style="margin-right:0.5em;"></i>Trending Topics</h3>
    <ul style="list-style:none;padding:0;">
        <li style="margin-bottom:0.8em;"><a href="https://www.cbr.com/best-new-rpgs-2025/" style="color:#00ffff;">Top 10 RPGs of 2025</a></li>
        <li style="margin-bottom:0.8em;"><a href="https://www.reddit.com/r/AndroidGaming/comments/8rlb59/why_does_almost_every_mobile_game_are_p2w/" style="color:#00ffff;">Is pay-to-win killing mobile games?</a></li>
        <li style="margin-bottom:0.8em;"><a href="https://www.rockpapershotgun.com/elden-ring-lore-story-explained" style="color:#00ffff;">Elden Ring Lore Breakdown</a></li>
    </ul>

    <!-- Esports Highlights -->
    <h3><i class="fa-solid fa-trophy" style="margin-right:0.5em;"></i>Esports Highlights</h3>
    <ul style="list-style:none;padding:0;">
        <li style="margin-bottom:0.8em;"><b>Team Liquid</b> wins CS2 Major</li>
        <li style="margin-bottom:0.8em;"><b>LOL Worlds 2025</b> starts next week</li>
        <li style="margin-bottom:0.8em;"><b>DOTA 2 TI</b> prize pool hits $30M+</li>
    </ul>

    <!-- Community Events -->
    <h3><i class="fa-solid fa-users" style="margin-right:0.5em;"></i>Community Events</h3>
    <ul style="list-style:none;padding:0;">
        <li style="margin-bottom:0.8em;">🎮 <b>Game Jam 2025</b> – Aug 2–4</li>
        <li style="margin-bottom:0.8em;">🎙 <b>Discord AMA</b> with devs – July 28</li>
        <li style="margin-bottom:0.8em;">🏆 <b>Monthly Screenshot Contest</b></li>
    </ul>

    </aside>
</div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.like-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const postId = this.dataset.postId;
            const likeButton = this.querySelector('.like-btn');
            const likeCountDisplay = this.closest('div').querySelector('.like-count-display .count');
            const heartIcon = this.closest('div').querySelector('.fa-heart');

            fetch('posts/like_post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `post_id=${postId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    likeCountDisplay.textContent = data.like_count;
                    if (data.is_liked) {
                        likeButton.innerHTML = '<i class="fa-solid fa-thumbs-down"></i> Unlike';
                        likeButton.classList.add('liked');
                        heartIcon.classList.add('liked-heart');
                    } else {
                        likeButton.innerHTML = '<i class="fa-solid fa-thumbs-up"></i> Like';
                        likeButton.classList.remove('liked');
                        heartIcon.classList.remove('liked-heart');
                    }
                } else if (data.message === 'Login required to like posts.') {
                    alert(data.message + ' Please register or log in.');
                    window.location.href = 'auth/register.php';
                } else {
                    alert(data.message);
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