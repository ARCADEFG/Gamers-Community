<?php
session_start();
include '../config/db.php';

if (!isset($_GET['id'])) {
    echo "❌ Community ID missing.";
    exit;
}

$cid = $_GET['id'];

// Get community info
$stmt = $conn->prepare("SELECT name, description FROM communities WHERE id = ?");
$stmt->bind_param("i", $cid);
$stmt->execute();
$stmt->bind_result($name, $desc);
$stmt->fetch();
$stmt->close();

echo "<h2>Community: $name</h2><p>$desc</p><hr>";

// Show posts in this community
$stmt = $conn->prepare("SELECT posts.id, title, content, username FROM posts JOIN users ON users.id = posts.user_id WHERE community_id = ? ORDER BY posts.created_at DESC");
$stmt->bind_param("i", $cid);
$stmt->execute();
$result = $stmt->get_result();

echo "<h3>Posts</h3>";
while ($row = $result->fetch_assoc()) {
    echo "<b>{$row['title']}</b> by {$row['username']}<br>";
    echo "<p>{$row['content']}</p><hr>";
}
$stmt->close();
?>
