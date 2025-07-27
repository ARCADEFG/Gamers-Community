<?php
session_start();
include '../config/db.php';
if (!isset($_SESSION['user_id'])) exit('Login required');

$postId = $_POST['post_id'] ?? null;
$content = trim($_POST['content'] ?? '');
$uid = $_SESSION['user_id'];

// Validate post existence
if (!$postId || !is_numeric($postId)) {
    header("Location: view_post.php?id=" . urlencode($postId) . "&error=invalidpost");
    exit;
}
$postId = (int)$postId;

// Validate post existence
try {
    $postCheck = $conn->prepare("SELECT id FROM posts WHERE id = ?");
    if ($postCheck === false) {
        throw new mysqli_sql_exception('Failed to prepare statement for post check.');
    }
    $postCheck->bind_param("i", $postId);
    $postCheck->execute();
    $postCheck->store_result();
    if ($postCheck->num_rows === 0) {
        $postCheck->close();
        header("Location: all.php?error=postnotfound");
        exit;
    }
    $postCheck->close();

    // Validate comment
    $maxLength = 500;
    if ($content === '') {
        header("Location: view_post.php?id=$postId&error=emptycomment");
        exit;
    }
    if (mb_strlen($content) > $maxLength) {
        header("Location: view_post.php?id=$postId&error=commenttoolong");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
    if ($stmt === false) {
        throw new mysqli_sql_exception('Failed to prepare statement for comment insertion.');
    }
    $stmt->bind_param("iis", $postId, $uid, $content);
    if ($stmt->execute()) {
        $stmt->close();
        header("Location: view_post.php?id=$postId&msg=commentsuccess");
        exit;
    } else {
        // This else block will now likely only be reached for non-exception errors
        header("Location: view_post.php?id=$postId&error=commentfail");
        exit;
    }
} catch (mysqli_sql_exception $e) {
    error_log("Database error in comment_post.php: " . $e->getMessage());
    header("Location: view_post.php?id=$postId&error=db_error&message=" . urlencode("A database error occurred while adding your comment."));
    exit;
}
?>


