<?php
// posts/all.php - Show all posts, allow read for all, like/comment only for authenticated
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../config/db.php';
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
</head>
<body>
<?php include '../includes/header.php'; ?>
<main class="main-content" style="max-width:800px;margin:2em auto;">
    <h2>All Posts</h2>
    <section class="posts-section">
    <div class="posts-list">
    <?php
    $res = $conn->query("SELECT posts.id, posts.title, posts.content, posts.created_at, users.username, posts.media_path, posts.media_type FROM posts JOIN users ON users.id = posts.user_id ORDER BY posts.created_at DESC");
    while ($row = $res->fetch_assoc()) {
        echo '<article class="post-card" style="margin-bottom:2em; background:#23233a; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.18); overflow:hidden;">';
        echo '<header class="post-card-header" style="padding:1.2em 1.5em 0.5em 1.5em;">';
        echo '<h3 class="post-title" style="font-family:Orbitron,sans-serif;font-size:2em;margin:0 0 0.2em 0;letter-spacing:1px;font-weight:800;">'
            . '<a href="view_post.php?id=' . $row['id'] . '" style="color:var(--primary);text-decoration:none;">' . htmlspecialchars($row['title']) . '</a></h3>';
        echo '<span class="meta" style="color:#b0b0b0;font-size:0.95em;">by ' . htmlspecialchars($row['username']) . ' on ' . $row['created_at'] . '</span>';
        echo '</header>';
        echo '<div style="text-align:center;padding:1em 0 0.5em 0;min-height:180px;display:flex;align-items:center;justify-content:center;background:#23233a;">';
        if ($row['media_path'] && $row['media_type'] === 'image') {
            $mediaPath = (strpos($row['media_path'], 'uploads/') === 0 || strpos($row['media_path'], 'assets/') === 0) ? '../' . $row['media_path'] : '../posts/' . $row['media_path'];
            echo '<img src="' . htmlspecialchars($mediaPath) . '" alt="Post Image" style="max-width:96%;max-height:340px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.15);object-fit:cover;">';
        } elseif ($row['media_path'] && $row['media_type'] === 'video') {
            $mediaPath = (strpos($row['media_path'], 'uploads/') === 0 || strpos($row['media_path'], 'assets/') === 0) ? '../' . $row['media_path'] : '../posts/' . $row['media_path'];
            echo '<video controls style="max-width:96%;max-height:340px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.15);background:#000;">';
            echo '<source src="' . htmlspecialchars($mediaPath) . '" type="video/mp4">Your browser does not support the video tag.';
            echo '</video>';
        } else {
            // Placeholder for no media
            echo '<div style="width:100%;height:160px;display:flex;align-items:center;justify-content:center;background:#23233a;">'
                . '<i class="fa-regular fa-image" style="font-size:3em;color:#35355a;"></i></div>';
        }
        echo '</div>';
        echo '<div class="post-content" style="padding:1em 1.5em 1.5em 1.5em;font-size:1.15em;line-height:1.7;color:#f4f4f4;font-family:Montserrat,sans-serif;">' . nl2br(htmlspecialchars($row['content'])) . '</div>';
        echo '</article>';
    }
    ?>
    </div>
    </section>
</main>
<?php include '../includes/footer.php'; ?>
</body>
</html>
