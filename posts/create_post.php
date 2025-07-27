
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Orbitron:wght@700;800&display=swap" rel="stylesheet">
</head>
<body>
<?php include '../includes/header.php'; ?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../config/db.php';
if (!isset($_SESSION['user_id'])) {
    echo "<div class='error'>Please <a href='../auth/login.php'>login</a> to post.</div>";
    include '../includes/footer.php';
    echo '</body></html>';
    exit;
}
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $uid  = $_SESSION['user_id'];
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $game_name = trim($_POST['game_name'] ?? '');
    $errors = [];
    if ($title === '') $errors[] = 'Title is required.';
    if ($content === '') $errors[] = 'Content is required.';
    if ($game_name === '') $errors[] = 'Game name is required.';
    $media_path = null;
    $media_type = null;
    if (isset($_FILES['media']) && $_FILES['media']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['media']['error'] === UPLOAD_ERR_OK) {
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
                    $errors[] = 'Failed to upload file.';
                }
            } else {
                $errors[] = 'Invalid file type.';
            }
        } else {
            $errors[] = 'File upload error.';
        }
    }
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("INSERT INTO posts (user_id, title, content, game_name, media_path, media_type) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $uid, $title, $content, $game_name, $media_path, $media_type);
            if ($stmt->execute()) {
                echo "<div class='success' style='background:linear-gradient(90deg,#4e54c8,#8f94fb);color:#fff;padding:1em 1.5em;border-radius:8px;display:flex;align-items:center;gap:0.7em;font-size:1.15em;font-family:Montserrat,sans-serif;margin-bottom:1.2em;box-shadow:0 2px 8px rgba(0,0,0,0.13);'><i class='fa-solid fa-circle-check' style='color:#00e676;font-size:1.5em;'></i>Post created successfully!</div>";
            } else {
                // This else block will now likely only be reached if execute() returns false for non-exception reasons
                echo "<div class='error'>&#x274c; Failed to create post. Please try again.</div>";
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            error_log("Database error during post creation: " . $e->getMessage());
            echo "<div class='error'>&#x274c; A database error occurred while creating your post. Please try again later.</div>";
        }
    } else {
        foreach ($errors as $err) {
            echo "<div class='error'>&#x274c; " . htmlspecialchars($err) . "</div>";
        }
    }
}
?>
<main class="main-content" style="max-width:900px;margin:2em auto;">
<section class="form-section create-post-section" style="background:#19192b;border-radius:16px;box-shadow:0 2px 18px rgba(0,0,0,0.19);padding:2.5em 2.5em 2em 2.5em;">
    <h3 class="post-title" style="font-family:Orbitron,sans-serif;font-size:2em;margin-bottom:1.3em;letter-spacing:1px;font-weight:800;color:var(--primary);text-align:left;">
        <i class="fa-solid fa-pen-to-square" style="margin-right:0.5em;color:#8f94fb;"></i>Create a Post
    </h3>
    <?php
    ?>
    <form method="POST" enctype="multipart/form-data" class="create-post-form" style="width:100%;">
        <table style="width:100%;border-collapse:separate;border-spacing:0 1.1em;">
            <tr>
                <td style="width:120px;"><label for="title" style="font-family:Montserrat,sans-serif;font-weight:600;color:#e0e0e0;font-size:1.13em;"><i class="fa-solid fa-heading" style="margin-right:0.4em;color:#4e54c8;"></i>Title</label></td>
                <td><input id="title" name="title" required placeholder="Post Title" style="width:90%;padding:0.9em 1.1em;border-radius:7px;border:1px solid #35355a;background:#23233a;color:#f4f4f4;font-size:1.13em;font-family:Montserrat,sans-serif;"></td>
            </tr>
            <tr>
                <td><label for="game_name" style="font-family:Montserrat,sans-serif;font-weight:600;color:#e0e0e0;font-size:1.13em;"><i class="fa-solid fa-gamepad" style="margin-right:0.4em;color:#4e54c8;"></i>Game Name</label></td>
                <td><input id="game_name" name="game_name" required placeholder="Game Name" style="width:90%;padding:0.9em 1.1em;border-radius:7px;border:1px solid #35355a;background:#23233a;color:#f4f4f4;font-size:1.13em;font-family:Montserrat,sans-serif;"></td>
            </tr>
            <tr>
                <td style="vertical-align:top;"><label for="content" style="font-family:Montserrat,sans-serif;font-weight:600;color:#e0e0e0;font-size:1.13em;"><i class="fa-solid fa-align-left" style="margin-right:0.4em;color:#4e54c8;"></i>Description</label></td>
                <td><textarea id="content" name="content" required placeholder="Content" rows="4" style="width:90%;padding:0.9em 1.1em;border-radius:7px;border:1px solid #35355a;background:#23233a;color:#f4f4f4;font-size:1.13em;font-family:Montserrat,sans-serif;resize:vertical;"></textarea></td>
            </tr>
            <tr>
                <td><label for="media" style="font-family:Montserrat,sans-serif;font-weight:600;color:#e0e0e0;font-size:1.13em;"><i class="fa-solid fa-photo-film" style="margin-right:0.4em;color:#4e54c8;"></i>Media (optional)</label></td>
                <td><input id="media" type="file" name="media" accept="image/*,video/*" style="width:90%;padding:0.7em 0.5em;border-radius:7px;background:#23233a;color:#f4f4f4;font-size:1.09em;font-family:Montserrat,sans-serif;"></td>
            </tr>
        </table>
        <button type="submit" style="margin-top:1.5em;padding:1em 0;font-size:1.18em;font-family:Orbitron,sans-serif;font-weight:700;background:linear-gradient(90deg,#4e54c8,#8f94fb);color:#fff;border:none;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.13);cursor:pointer;transition:background 0.2s;letter-spacing:1px;width:100%;"><i class="fa-solid fa-paper-plane" style="margin-right:0.5em;"></i>Post</button>
    </form>
</section>
</main>
<?php include '../includes/footer.php'; ?>
</body>
</html>
