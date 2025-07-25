<?php
session_start();
include '../config/db.php';
if (!isset($_SESSION['user_id'])) exit('Login required');

$postId = $_POST['post_id'];
$content = $_POST['content'];
$uid = $_SESSION['user_id'];

$stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $postId, $uid, $content);
$stmt->execute();

header("Location: view_post.php?id=$postId");
?>


