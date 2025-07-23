<?php
session_start();
include '../config/db.php';
if (!isset($_SESSION['user_id'])) exit('Login required');

$postId = $_POST['post_id'];
$uid = $_SESSION['user_id'];

// Toggle like/unlike
$res = $conn->query("SELECT id FROM post_likes WHERE post_id=$postId AND user_id=$uid");
if ($res->num_rows) {
    $conn->query("DELETE FROM post_likes WHERE post_id=$postId AND user_id=$uid");
} else {
    $conn->query("INSERT INTO post_likes (post_id, user_id) VALUES ($postId, $uid)");
}

header("Location: view_post.php?id=$postId");
?>
