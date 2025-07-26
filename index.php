<?php // index.php ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gamers Community</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Orbitron:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="main-layout">
    <div class="main-content">
        <h2 style="font-family:'Orbitron',sans-serif;font-size:2.2em;margin-bottom:1.2em;letter-spacing:1px;font-weight:800;color:var(--primary);">Welcome to the Gamers Community</h2>
        <?php
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        include 'config/db.php';
        $uid = $_SESSION['user_id'] ?? null; // Get user ID from session

        // Create Post button - always visible
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
                        $current_post_liked = $row['liked'] ?? 0; // Default to 0 if not logged in or not liked
                        $current_like_count = $row['like_count'];

                        echo '<article class="post-card">';
                        echo '<header class="post-card-header">';
                        echo '<div class="post-game"><i class="fa-solid fa-gamepad" style="margin-right:0.4em;"></i>' . htmlspecialchars($row['game_name']) . '</div>';
                        echo '<h3 class="post-title"><a href="posts/view_post.php?id=' . $row['id'] . '" style="color:var(--primary);text-decoration:none;">' . htmlspecialchars($row['title']) . '</a></h3>';
                        echo '<span class="meta">by ' . htmlspecialchars($row['username']) . ' on ' . $row['created_at'] . '</span>';
                        echo '</header>';
                        // Media display for the post, or placeholder if no media
                        echo '<div class="media-container">'; // Using class from style.css
                        if (!empty($row['media_path']) && $row['media_type'] === 'image') {
                            $mediaPath = htmlspecialchars($row['media_path']); // Corrected path for root index.php
                            echo '<img src="' . $mediaPath . '" alt="Post Image">';
                        } elseif (!empty($row['media_path']) && $row['media_type'] === 'video') {
                            $mediaPath = htmlspecialchars($row['media_path']); // Corrected path for root index.php
                            echo '<video controls><source src="' . $mediaPath . '" type="video/mp4">Your browser does not support the video tag.</video>';
                        } else {
                            echo '<div style="width:100%;height:160px;display:flex;align-items:center;justify-content:center;background:#23233a;">'
                                . '<i class="fa-regular fa-image" style="font-size:3em;color:#35355a;"></i><br><span style="color:#b0b0b0;font-size:0.9em;margin-top:0.5em;">No media attached</span></div>';
                        }
                        echo '</div>';
                        echo '<div class="post-content">' . nl2br(htmlspecialchars($row['content'])) . '</div>';
                        // Footer for likes, comments, and edit/delete buttons
                        echo '<div class="post-action-buttons">';
                        // Like and Comment buttons on the left
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
                            // Unauthenticated: Like button becomes a link to register
                            echo '<a href="auth/register.php" class="like-btn"><i class="fa-solid fa-thumbs-up"></i> Like</a>';
                            echo '<span class="like-count-display">';
                            echo '<i class="fa-solid fa-heart"></i>';
                            echo '<span class="count">' . $current_like_count . '</span> Like' . ($current_like_count == 1 ? '' : 's') . '</span>';
                            // Unauthenticated: Comment button becomes a link to register
                            echo '<a href="auth/register.php" class="comment-btn"><i class="fa-solid fa-comment"></i> Comment</a>';
                        }
                        echo '</div>';
                        // Edit/Delete buttons on the right
                        if ($isOwner) {
                            echo '<div class="edit-delete-buttons">';
                            echo '<a href="posts/edit_post.php?id=' . $row['id'] . '">✏️ Edit</a>';
                            echo '<form action="posts/delete_post.php" method="GET" onsubmit="return confirm(\'Are you sure you want to delete this post?\');" style="display:inline;">';
                            echo '<input type="hidden" name="id" value="' . htmlspecialchars($row['id']) . '">';
                            echo '<button type="submit">🗑 Delete</button>';
                            echo '</form>';
                            echo '</div>';
                        }
                        echo '</div>';
                        echo '</article>';
                    }
                } else {
                    echo '<p style="text-align:center;color:#b0b0b0;font-size:1.1em;margin-top:2em;">No posts found. Be the first to create one!</p>';
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
        <li style="margin-bottom:0.8em;">🎙️ <b>Discord AMA</b> with devs – July 28</li>
        <li style="margin-bottom:0.8em;">🏆 <b>Monthly Screenshot Contest</b></li>
    </ul>

</aside>
</div>
<?php include 'includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.like-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission

            const postId = this.dataset.postId; // Get post ID from data attribute
            const likeButton = this.querySelector('.like-btn');
            const likeCountDisplay = this.closest('div').querySelector('.like-count-display .count');
            const heartIcon = this.closest('div').querySelector('.fa-heart');

            fetch('posts/like_post.php', {
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
                        window.location.href = 'auth/register.php'; // Redirect to register
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
