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
    $res = $conn->query("SELECT posts.id, posts.title, posts.content, posts.created_at, users.username FROM posts JOIN users ON users.id = posts.user_id ORDER BY posts.created_at DESC");
    while ($row = $res->fetch_assoc()) {
        echo '<article class="post-card" style="margin-bottom:2em;">';
        echo '<header class="post-card-header">';
        echo '<h3 class="post-title"><a href="view_post.php?id=' . $row['id'] . '">' . htmlspecialchars($row['title']) . '</a></h3>';
        echo '<span class="meta">by ' . htmlspecialchars($row['username']) . ' on ' . $row['created_at'] . '</span>';
        echo '</header>';
        echo '<div class="post-content">' . nl2br(htmlspecialchars($row['content'])) . '</div>';
        echo '</article>';
    }
    ?>
    </div>
    </section>
</main>
<?php include '../includes/footer.php'; ?>
</body>
</html>
