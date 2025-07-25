
<?php
session_start();
include '../config/db.php';
if (!isset($_SESSION['user_id'])) exit('Login required');

if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
    exit('Invalid post id');
}
$postId = (int)$_POST['post_id'];
$uid = $_SESSION['user_id'];

// Toggle like/unlike using prepared statements
$stmt = $conn->prepare("SELECT id FROM post_likes WHERE post_id=? AND user_id=?");
$stmt->bind_param("ii", $postId, $uid);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows) {
    $del = $conn->prepare("DELETE FROM post_likes WHERE post_id=? AND user_id=?");
    $del->bind_param("ii", $postId, $uid);
    $del->execute();
    $del->close();
} else {
    $ins = $conn->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
    $ins->bind_param("ii", $postId, $uid);
    $ins->execute();
    $ins->close();
}
$stmt->close();

header("Location: view_post.php?id=$postId");
exit;
?>
