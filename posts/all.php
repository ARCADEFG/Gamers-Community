<?php
// posts/all.php - Show all posts, allow read for all, like/comment only for authenticated
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../config/db.php';

$uid = $_SESSION['user_id'] ?? null; // Assuming user_id is stored in session
$search_query = trim($_GET['search_query'] ?? '');

$sql = "SELECT posts.id, posts.title, posts.content, posts.created_at, posts.user_id, users.username, posts.media_path, posts.media_type, posts.game_name";
$sql .= ", (SELECT COUNT(*) FROM post_likes WHERE post_id=posts.id) as like_count";
if ($uid) {
    $sql .= ", (SELECT 1 FROM post_likes WHERE post_id=posts.id AND user_id=?) as liked";
}
$sql .= " FROM posts JOIN users ON users.id = posts.user_id";

$params = [];
$types = '';

if (!empty($search_query)) {
    $sql .= " WHERE posts.title LIKE ? OR posts.game_name LIKE ?";
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';
    $types .= 'ss';
}

$sql .= " ORDER BY posts.created_at DESC";

if ($uid) {
    // Need to bind uid even if no search query for the liked subquery
    array_unshift($params, $uid); // Add uid to the beginning for the first ?
    $types = 'i' . $types; // Prepend 'i' for integer
}

try {
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        // If prepare fails, it will now throw an exception due to mysqli_report
        // This line might become redundant but kept for clarity/fallback
        throw new mysqli_sql_exception('Failed to prepare statement.');
    }

    if (!empty($params)) {
        // Dynamically bind parameters based on their types
        $bind_names[] = &$types;
        for ($i = 0; $i < count($params); $i++) {
            $bind_name = 'bind' . $i;
            $$bind_name = $params[$i];
            $bind_names[] = &$$bind_name;
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_names);
    }

    $stmt->execute();
    $res = $stmt->get_result();
} catch (mysqli_sql_exception $e) {
    error_log("Database error in all.php: " . $e->getMessage());
    // Handle the error gracefully, maybe show a message to the user or redirect
    $res = false; // Indicate that results could not be fetched
    echo "<div class='error'>&#x274c; A database error occurred while fetching posts. Please try again later.</div>";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gamers Community</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Orbitron:wght@700;800&family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Your CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .create-post-btn { background:linear-gradient(90deg,#4e54c8,#8f94fb); color:#fff; padding:10px 20px; border-radius:8px; text-decoration:none; font-family:Orbitron,sans-serif; font-weight:700; font-size:1.1em; display:inline-flex; align-items:center; gap:0.5em; box-shadow:0 2px 8px rgba(0,0,0,0.13); transition:background 0.2s; }
        .create-post-btn:hover { opacity:0.9; }
        .search-container { display:flex; gap:10px; margin-bottom:20px; }
        .search-input { flex-grow:1; padding:10px; border:1px solid #35355a; border-radius:8px; background:#23233a; color:#f4f4f4; font-family:Montserrat,sans-serif; font-size:1em; }
        .search-button { padding:10px 20px; background:linear-gradient(90deg,#4e54c8,#8f94fb); color:#fff; border:none; border-radius:8px; cursor:pointer; font-family:Orbitron,sans-serif; font-weight:700; font-size:1em; box-shadow:0 2px 8px rgba(0,0,0,0.13); transition:background 0.2s; }
        .search-button:hover { opacity:0.9; }
        .like-btn, .comment-btn { 
            background: linear-gradient(90deg,#4e54c8,#8f94fb); 
            color:#fff; padding:0.7em 1.7em; border-radius:999px; border:none;
            font-family:Orbitron,sans-serif; font-weight:700; font-size:1.15em;
            display:inline-flex; align-items:center; gap:0.5em; box-shadow:0 2px 8px rgba(0,0,0,0.10);
            cursor:pointer; transition:background 0.2s, opacity 0.2s;
            text-decoration: none; /* For links */
        }
        .like-btn:hover, .comment-btn:hover { opacity:0.9; }
        .like-btn.liked { background: #e74c3c; /* This will be overridden by JS for authenticated users */ }
        .like-count-display { font-size:1.13em;color:#8f94fb;font-family:Orbitron,sans-serif;font-weight:700;display:flex;align-items:center;gap:0.4em; }
        .like-count-display i { transition:color 0.2s; }
        .like-count-display .liked-heart { color:#e74c3c; }
        .comment-btn { 
            background:linear-gradient(90deg,#2575fc,#6a11cb); 
            margin-left: 10px;
        }
        .edit-delete-buttons { display: flex; gap: 0.7em; }
        .edit-delete-buttons a, .edit-delete-buttons button { 
            padding:0.7em 1.5em; border-radius:999px; text-decoration:none;
            font-family:Orbitron,sans-serif; font-weight:700; box-shadow:0 2px 8px rgba(0,0,0,0.10);
            display:inline-flex; align-items:center; gap:0.5em; font-size:1.05em; transition:opacity 0.2s;
        }
        .edit-delete-buttons a { background:linear-gradient(90deg,#4e54c8,#8f94fb); color:#fff; }
        .edit-delete-buttons button { background-color:#e74c3c; color:#fff; border:none; cursor:pointer; }
        .edit-delete-buttons a:hover, .edit-delete-buttons button:hover { opacity:0.9; }

    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>
<main class="main-content" style="max-width:1300px;margin:2em auto;">
    <h2>All Posts</h2>

    <?php // Create Post button - always visible
    if (isset($_SESSION['user_id'])): ?>
        <div style="margin-bottom:2em;">
            <a href="create_post.php" class="create-post-btn">
                <i class="fa-solid fa-plus-circle"></i> Create New Post
            </a>
        </div>
    <?php else: ?>
        <div style="margin-bottom:2em;">
            <a href="../auth/register.php" class="create-post-btn">
                <i class="fa-solid fa-plus-circle"></i> Create New Post
            </a>
        </div>
    <?php endif; ?>
    
    <form method="GET" action="all.php" class="search-container">
        <input type="text" name="search_query" placeholder="Search posts by title or game..." value="<?= htmlspecialchars($search_query) ?>" class="search-input">
        <button type="submit" class="search-button"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    </form>

    <section class="posts-section">
    <div class="posts-list">
    <?php
    if ($res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $isOwner = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $row['user_id'];
            $current_post_liked = $row['liked'] ?? 0; // Default to 0 if not logged in or not liked
            $current_like_count = $row['like_count'];

            echo '<article class="post-card" style="margin-bottom:2em; background:#23233a; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.18); overflow:hidden;">';
            echo '<header class="post-card-header" style="padding:1.2em 1.5em 0.5em 1.5em;">';
            echo '<div class="post-game"><i class="fa-solid fa-gamepad" style="margin-right:0.4em;"></i>' . htmlspecialchars($row['game_name']) . '</div>';
            echo '<h3 class="post-title" style="font-family:Orbitron,sans-serif;font-size:2em;margin:0 0 0.2em 0;letter-spacing:1px;font-weight:800;">'
                . '<a href="view_post.php?id=' . $row['id'] . '" style="color:var(--primary);text-decoration:none;">' . htmlspecialchars($row['title']) . '</a></h3>';
            echo '<span class="meta" style="color:#b0b0b0;font-size:0.95em;">by ' . htmlspecialchars($row['username']) . ' on ' . $row['created_at'] . '</span>';
            echo '</header>';
            echo '<div style="text-align:center;padding:1em 0 0.5em 0;min-height:180px;display:flex;align-items:center;justify-content:center;background:#23233a;">';
            if (!empty($row['media_path']) && $row['media_type'] === 'image') {
                $mediaPath = (strpos($row['media_path'], 'uploads/') === 0) ? '../' . $row['media_path'] : '../posts/' . $row['media_path'];
                echo '<img src="' . htmlspecialchars($mediaPath) . '" alt="Post Image" style="max-width:96%;max-height:340px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.15);object-fit:cover;">';
            } elseif (!empty($row['media_path']) && $row['media_type'] === 'video') {
                $mediaPath = (strpos($row['media_path'], 'uploads/') === 0) ? '../' . $row['media_path'] : '../posts/' . $row['media_path'];
                echo '<video controls style="max-width:96%;max-height:340px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.15);background:#000;">';
                echo '<source src="' . htmlspecialchars($mediaPath) . '" type="video/mp4">Your browser does not support the video tag.';
                echo '</video>';
            } else {
                echo '<div style="width:100%;height:160px;display:flex;align-items:center;justify-content:center;background:#23233a;">'
                    . '<i class="fa-regular fa-image" style="font-size:3em;color:#35355a;"></i><br><span style="color:#b0b0b0;font-size:0.9em;margin-top:0.5em;">No media attached</span></div>';
            }
            echo '</div>';
            echo '<div class="post-content" style="padding:1em 1.5em 1.5em 1.5em;font-size:1.15em;line-height:1.7;color:#f4f4f4;font-family:Montserrat,sans-serif;">' . nl2br(htmlspecialchars($row['content'])) . '</div>';
            // Footer for likes and edit/delete buttons
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
                echo '<a href="view_post.php?id=' . $row['id'] . '#comments" class="comment-btn"><i class="fa-solid fa-comment"></i> Comment</a>';
            } else {
                // Unauthenticated: Like button becomes a link to register
                echo '<a href="../auth/register.php" class="like-btn"><i class="fa-solid fa-thumbs-up"></i> Like</a>';
                echo '<span class="like-count-display">';
                echo '<i class="fa-solid fa-heart"></i>';
                echo '<span class="count">' . $current_like_count . '</span> Like' . ($current_like_count == 1 ? '' : 's') . '</span>';
                // Unauthenticated: Comment button becomes a link to register
                echo '<a href="../auth/register.php" class="comment-btn"><i class="fa-solid fa-comment"></i> Comment</a>';
            }
            echo '</div>';
            // Edit/Delete buttons on the right
            if ($isOwner) {
                echo '<div class="edit-delete-buttons">';
                echo '<a href="edit_post.php?id=' . $row['id'] . '">✏ Edit</a>';
                echo '<form action="delete_post.php" method="GET" onsubmit="return confirm(\'Are you sure you want to delete this post?\');" style="display:inline;">';
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
</main>
<?php include '../includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.like-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission

            const postId = this.dataset.postId; // Get post ID from data attribute
            const likeButton = this.querySelector('.like-btn');
            const likeCountDisplay = form.closest('.post-action-buttons').querySelector('.like-count-display .count');
            const heartIcon = form.closest('.post-action-buttons').querySelector('.fa-heart');

            fetch('like_post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest' // Identify as AJAX request
                },
                body: post_id=${postId}
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