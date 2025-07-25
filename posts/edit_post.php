
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    include '../includes/footer.php';
    echo '</body></html>';
    exit;
}

$post_id = $_GET['id'] ?? null;

if (!$post_id) {
    echo "<div class='error'>Post ID missing.</div>";
    include '../includes/footer.php';
    echo '</body></html>';
    exit;
}

// Get the post
$stmt = $conn->prepare("SELECT * FROM posts WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $post_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if (!$post) {
    echo "<div class='error'>Post not found or you're not allowed to edit it.</div>";
    include '../includes/footer.php';
    echo '</body></html>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];

    $update = $conn->prepare("UPDATE posts SET title = ?, content = ? WHERE id = ?");
    $update->bind_param("ssi", $title, $content, $post_id);
    if ($update->execute()) {
        header("Location: view_post.php?id=$post_id");
        exit;
    } else {
        echo "<div class='error'>Update failed.</div>";
    }
}
?>

<form method="post">
    <input type="text" name="title" value="<?= htmlspecialchars($post['title']) ?>"><br>
    <textarea name="content"><?= htmlspecialchars($post['content']) ?></textarea><br>
    <button type="submit">Update Post</button>
</form>
<?php include '../includes/footer.php'; ?>
</body>
</html>
