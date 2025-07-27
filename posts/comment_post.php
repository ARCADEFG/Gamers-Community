<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php?error=notloggedin");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: view_post.php?id=" . ($_POST['post_id'] ?? ''));
    exit;
}

$post_id = (int)($_POST['post_id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];
$comment_text = trim($_POST['comment_text'] ?? '');

if (empty($comment_text)) {
    header("Location: view_post.php?id=$post_id&error=emptycomment");
    exit;
}

$stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $post_id, $user_id, $comment_text);
$stmt->execute();

// Redirect back to the post
header("Location: view_post.php?id=$post_id");
exit;
?>
