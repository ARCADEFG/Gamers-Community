

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Post</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
      header { position: relative !important; top: unset !important; z-index: unset !important; }
    </style>
</head>
<body>

<?php include '../includes/header.php'; ?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../config/db.php';

if (!isset($_GET['id'])) {
    echo "<div class='error'>&#x274c; Post ID missing.</div>";
    include '../includes/footer.php';
    echo '</body></html>';
    exit;
}

$pid = $_GET['id'];

$stmt = $conn->prepare("SELECT posts.title, posts.content, posts.created_at, users.username FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = ?");
$stmt->bind_param("i", $pid);
$stmt->execute();
$stmt->bind_result($title, $content, $created, $author);
$stmt->fetch();
$stmt->close();

$postId = $pid;

echo '<section class="post-card" style="max-width:700px;margin:2em auto;">';
echo '<header class="post-card-header">';
echo '<h2 class="post-title">' . htmlspecialchars($title) . '</h2>';
echo '<span class="meta">by ' . htmlspecialchars($author) . ' on ' . $created . '</span>';
echo '</header>';
echo '<div class="post-content">' . nl2br(htmlspecialchars($content)) . '</div>';


// Count likes & check if user liked
$likes = 0;
$isLiked = 0;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $res = $conn->query("SELECT COUNT(*) as likes, SUM(user_id=$uid) as isLiked FROM post_likes WHERE post_id = $postId");
    $data = $res->fetch_assoc();
    $likes = $data['likes'];
    $isLiked = $data['isLiked'];
} else {
    $res = $conn->query("SELECT COUNT(*) as likes FROM post_likes WHERE post_id = $postId");
    $data = $res->fetch_assoc();
    $likes = $data['likes'];
}
echo '<div class="post-likes" style="margin:1em 0;">';
echo '<span class="meta">&#x1F44D; Likes: ' . $likes . '</span>';
if (isset($_SESSION['user_id'])) {
    $btn = $isLiked ? 'Unlike' : 'Like';
    echo '<form method="POST" action="like_post.php" style="display:inline-block;margin-left:1em;">';
    echo '<input type="hidden" name="post_id" value="' . $postId . '">';
    echo '<button type="submit">' . $btn . '</button>';
    echo '</form>';
}
echo '</div>';

echo '</section>';

// Load comments
echo '<section class="form-section" style="max-width:700px;margin:2em auto;">';
echo '<h3>Comments</h3>';
$res = $conn->query("SELECT comments.content, users.username, comments.created_at FROM comments JOIN users ON users.id=comments.user_id WHERE comments.post_id=$postId ORDER BY comments.created_at ASC");
echo '<div class="comments-list">';
while ($row = $res->fetch_assoc()) {
    echo '<article class="comment-card" style="background:var(--secondary);padding:1em 1.2em;border-radius:8px;margin-bottom:1em;box-shadow:0 2px 8px rgba(0,0,0,0.12);color:#fff;">';
    echo '<header class="comment-header">';
    echo '<b>' . htmlspecialchars($row['username']) . '</b> <span class="meta">(' . $row['created_at'] . '):</span>';
    echo '</header>';
    echo '<div class="comment-content">' . nl2br(htmlspecialchars($row['content'])) . '</div>';
    echo '</article>';
}
echo '</div>';

// Comment form
if (isset($_SESSION['user_id'])) {
    echo '<form method="POST" action="comment_post.php" class="login-form" style="margin-top:1.5em;background:var(--primary);border-radius:8px;padding:1.2em;box-shadow:0 2px 8px rgba(0,0,0,0.12);">';
    echo '<input type="hidden" name="post_id" value="' . $postId . '">';
    echo '<textarea name="content" required placeholder="Add a comment..." style="width:100%;padding:0.8em;margin-bottom:0.8em;border-radius:4px;border:none;font-size:1em;"></textarea>';
    echo '<button type="submit" style="width:100%;padding:0.8em;font-size:1em;background:var(--accent);color:#fff;border:none;border-radius:4px;cursor:pointer;">Add Comment</button>';
    echo '</form>';
}
echo '</section>';
?>
<?php include '../includes/footer.php'; ?>
</body>
</html>
