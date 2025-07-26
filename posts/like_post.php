
<?php
session_start();
include '../config/db.php';

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!isset($_SESSION['user_id'])) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Login required', 'redirect' => '../auth/login.php']);
        exit;
    } else {
        header('Location: ../auth/login.php?error=loginrequired');
        exit;
    }
}

if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
        exit;
    } else {
        header('Location: all.php?error=invalidpostid');
        exit;
    }
}

$postId = (int)$_POST['post_id'];
$uid = $_SESSION['user_id'];

$isLiked = false;
// Check if already liked
$stmt = $conn->prepare("SELECT id FROM post_likes WHERE post_id=? AND user_id=?");
$stmt->bind_param("ii", $postId, $uid);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    // Already liked, so unlike it
    $del = $conn->prepare("DELETE FROM post_likes WHERE post_id=? AND user_id=?");
    $del->bind_param("ii", $postId, $uid);
    $del->execute();
    $del->close();
    $isLiked = false;
} else {
    // Not liked, so like it
    $ins = $conn->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
    $ins->bind_param("ii", $postId, $uid);
    $ins->execute();
    $ins->close();
    $isLiked = true;
}
$stmt->close();

// Get updated like count
$likeCountRes = $conn->query("SELECT COUNT(*) as like_count FROM post_likes WHERE post_id = $postId");
$likeCountData = $likeCountRes->fetch_assoc();
$newLikeCount = $likeCountData['like_count'];

if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'like_count' => $newLikeCount,
        'is_liked' => $isLiked
    ]);
    exit;
} else {
    header("Location: view_post.php?id=$postId");
    exit;
}
?>
