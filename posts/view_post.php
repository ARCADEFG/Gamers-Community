<?php
session_start();
include '../config/db.php';

if (!isset($_GET['id'])) {
    echo "❌ Post ID missing.";
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

// Count likes & check if user liked
$res = $conn->query("SELECT COUNT(*) likes, SUM(user_id={$_SESSION['user_id']}) isLiked FROM post_likes WHERE post_id = $postId");
$data = $res->fetch_assoc();
echo "<p>Likes: {$data['likes']}</p>";

if (isset($_SESSION['user_id'])) {
    $btn = $data['isLiked'] ? 'Unlike' : 'Like';
    echo "<form method='POST' action='like_post.php'>
    <input type='hidden' name='post_id' value='$postId'>
    <button type='submit'>$btn</button></form>";
}

// Load comments
$res = $conn->query("SELECT comments.content, users.username, comments.created_at FROM comments JOIN users ON users.id=comments.user_id WHERE comments.post_id=$postId ORDER BY comments.created_at ASC");
echo "<h3>Comments</h3>";
while ($row = $res->fetch_assoc()) {
    echo "<p><b>{$row['username']}</b> ({$row['created_at']}):<br>{$row['content']}</p>";
}

// Comment form
if (isset($_SESSION['user_id'])) {
    echo "<form method='POST' action='comment_post.php'>
      <input type='hidden' name='post_id' value='$postId'>
      <textarea name='content' required></textarea><br>
      <button type='submit'>Add Comment</button>
    </form>";
}

echo "<h2>$title</h2>";
echo "<p><i>by $author on $created</i></p>";
echo "<p>$content</p>";
?>
