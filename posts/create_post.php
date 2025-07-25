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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $uid  = $_SESSION['user_id'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $cid = $_POST['community_id'] !== "" ? $_POST['community_id'] : null;

    $media_path = null;
    $media_type = null;
    if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg','image/png','image/gif','image/webp','video/mp4','video/webm','video/ogg'];
        $file_type = $_FILES['media']['type'];
        if (in_array($file_type, $allowed_types)) {
            $ext = pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION);
            $media_type = strpos($file_type, 'image/') === 0 ? 'image' : 'video';
            $upload_dir = '../uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $filename = uniqid('media_') . '.' . $ext;
            $target = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['media']['tmp_name'], $target)) {
                $media_path = 'uploads/' . $filename;
            } else {
                echo "<div class='error'>&#x274c; Failed to upload file.</div>";
            }
        } else {
            echo "<div class='error'>&#x274c; Invalid file type.</div>";
        }
    }

    $stmt = $conn->prepare("INSERT INTO posts (user_id, title, content, community_id, media_path, media_type) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississ", $uid, $title, $content, $cid, $media_path, $media_type);

    if ($stmt->execute()) {
        echo "<div class='success'>&#x2705; Post created.</div>";
    } else {
        echo "<div class='error'>&#x274c; " . $stmt->error . "</div>";
    }
    $stmt->close();
}

$result = $conn->query("SELECT id, name FROM communities");
?>

<form method="POST" enctype="multipart/form-data">
    <input name="title" required placeholder="Post Title"><br>
    <textarea name="content" required placeholder="Content"></textarea><br>
    <select name="community_id">
        <option value="">-- Optional: Select Community --</option>
        <?php while ($row = $result->fetch_assoc()): ?>
            <option value="<?= $row['id'] ?>"><?= $row['name'] ?></option>
        <?php endwhile; ?>
    </select><br>
    <label>Image/Video: <input type="file" name="media" accept="image/*,video/*"></label><br>
    <button type="submit">Post</button>
</form>
<?php include '../includes/footer.php'; ?>
</body>
</html>
