<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo "Please <a href='../auth/login.php'>login</a> to post.";
    exit;
}

$result = $conn->query("SELECT id, name FROM communities");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $uid  = $_SESSION['user_id'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $cid = $_POST['community_id'] !== "" ? $_POST['community_id'] : null;

    $stmt = $conn->prepare("INSERT INTO posts (user_id, title, content, community_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $uid, $title, $content, $cid);

    if ($stmt->execute()) {
        echo "✅ Post created.";
    } else {
        echo "❌ " . $stmt->error;
    }
    $stmt->close();
}
?>

<form method="POST">
    <input name="title" required placeholder="Post Title"><br>
    <textarea name="content" required placeholder="Content"></textarea><br>
    <select name="community_id">
        <option value="">-- Optional: Select Community --</option>
        <?php while ($row = $result->fetch_assoc()): ?>
            <option value="<?= $row['id'] ?>"><?= $row['name'] ?></option>
        <?php endwhile; ?>
    </select><br>
    <button type="submit">Post</button>
</form>
