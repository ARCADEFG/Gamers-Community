
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo "<div class='error'>Please <a href='../auth/login.php'>login</a> to post.</div>";
    include '../includes/footer.php';
    echo '</body></html>';
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
        echo "<div class='success'>&#x2705; Post created.</div>";
    } else {
        echo "<div class='error'>&#x274c; " . $stmt->error . "</div>";
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
<?php include '../includes/footer.php'; ?>
</body>
</html>
