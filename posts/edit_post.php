<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$post_id = $_GET['id'] ?? null;

if (!$post_id) {
    echo "Post ID missing.";
    exit;
}

// Get the post
$stmt = $conn->prepare("SELECT * FROM posts WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $post_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if (!$post) {
    echo "Post not found or you're not allowed to edit it.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];

    $update = $conn->prepare("UPDATE posts SET title = ?, content = ? WHERE id = ?");
    $update->bind_param("ssi", $title, $content, $post_id);
    if ($update->execute()) {
        header("Location: view_post.php?id=$post_id");
    } else {
        echo "Update failed.";
    }
}
?>

<form method="post">
    <input type="text" name="title" value="<?= htmlspecialchars($post['title']) ?>"><br>
    <textarea name="content"><?= htmlspecialchars($post['content']) ?></textarea><br>
    <button type="submit">Update Post</button>
</form>
