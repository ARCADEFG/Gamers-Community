
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Orbitron:wght@700;800&display=swap" rel="stylesheet">
</head>
<body>
<?php include '../includes/header.php'; ?>
<main class="main-content" style="max-width:1000px;margin:2em auto;">
<section class="form-section create-post-section" style="background:#19192b;border-radius:16px;box-shadow:0 2px 18px rgba(0,0,0,0.19);padding:2.5em 2.5em 2em 2.5em;">
    <h3 class="post-title" style="font-family:Orbitron,sans-serif;font-size:2em;margin-bottom:1.3em;letter-spacing:1px;font-weight:800;color:var(--primary);text-align:left;">
        <i class="fa-solid fa-pen-to-square" style="margin-right:0.5em;color:#8f94fb;"></i>Edit Post
    </h3>
    <?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
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
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $game_name = trim($_POST['game_name'] ?? '');
        $errors = [];
        if ($title === '') $errors[] = 'Title is required.';
        if ($content === '') $errors[] = 'Content is required.';
        if ($game_name === '') $errors[] = 'Game name is required.';
        if (empty($errors)) {
            $update = $conn->prepare("UPDATE posts SET title = ?, content = ?, game_name = ? WHERE id = ?");
            $update->bind_param("sssi", $title, $content, $game_name, $post_id);
            if ($update->execute()) {
                header("Location: view_post.php?id=$post_id");
                exit;
            } else {
                echo "<div class='error'>Update failed: " . htmlspecialchars($update->error) . "</div>";
            }
        } else {
            foreach ($errors as $err) {
                echo "<div class='error'>" . htmlspecialchars($err) . "</div>";
            }
        }
    }
    ?>
    <form method="post" enctype="multipart/form-data" class="create-post-form" style="width:95%;">
        <table style="width:100%;border-collapse:separate;border-spacing:0 1.1em;table-layout:fixed;">
            <tr>
                <td style="width:160px;vertical-align:top;padding-right:1em;"><label for="title" style="font-family:Montserrat,sans-serif;font-weight:600;color:#e0e0e0;font-size:1.13em;white-space:nowrap;"><i class="fa-solid fa-heading" style="margin-right:0.4em;color:#4e54c8;"></i>Title</label></td>
                <td><input id="title" name="title" required placeholder="Post Title" value="<?= htmlspecialchars($post['title']) ?>" style="width:100%;padding:0.9em 1.1em;border-radius:7px;border:1px solid #35355a;background:#23233a;color:#f4f4f4;font-size:1.13em;font-family:Montserrat,sans-serif;"></td>
            </tr>
            <tr>
                <td style="vertical-align:top;padding-right:1em;"><label for="game_name" style="font-family:Montserrat,sans-serif;font-weight:600;color:#e0e0e0;font-size:1.13em;white-space:nowrap;"><i class="fa-solid fa-gamepad" style="margin-right:0.4em;color:#4e54c8;"></i>Game Name</label></td>
                <td><input id="game_name" name="game_name" required placeholder="Game Name" value="<?= htmlspecialchars($post['game_name']) ?>" style="width:100%;padding:0.9em 1.1em;border-radius:7px;border:1px solid #35355a;background:#23233a;color:#f4f4f4;font-size:1.13em;font-family:Montserrat,sans-serif;"></td>
            </tr>
            <tr>
                <td style="vertical-align:top;padding-right:1em;"><label for="content" style="font-family:Montserrat,sans-serif;font-weight:600;color:#e0e0e0;font-size:1.13em;white-space:nowrap;"><i class="fa-solid fa-align-left" style="margin-right:0.4em;color:#4e54c8;"></i>Description</label></td>
                <td><textarea id="content" name="content" required placeholder="Content" rows="4" style="width:100%;padding:0.9em 1.1em;border-radius:7px;border:1px solid #35355a;background:#23233a;color:#f4f4f4;font-size:1.13em;font-family:Montserrat,sans-serif;resize:vertical;"><?= htmlspecialchars($post['content']) ?></textarea></td>
            </tr>
            <tr>
                <td style="vertical-align:top;padding-right:1em;"><label for="media" style="font-family:Montserrat,sans-serif;font-weight:600;color:#e0e0e0;font-size:1.13em;white-space:nowrap;"><i class="fa-solid fa-photo-film" style="margin-right:0.4em;color:#4e54c8;"></i>Media (optional)</label></td>
                <td><input id="media" type="file" name="media" accept="image/*,video/*" style="width:100%;padding:0.7em 0.5em;border-radius:7px;background:#23233a;color:#f4f4f4;font-size:1.09em;font-family:Montserrat,sans-serif;"></td>
            </tr>
        </table>
        <button type="submit" style="margin-top:1.5em;padding:1em 0;font-size:1.18em;font-family:Orbitron,sans-serif;font-weight:700;background:linear-gradient(90deg,#4e54c8,#8f94fb);color:#fff;border:none;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.13);cursor:pointer;transition:background 0.2s;letter-spacing:1px;width:100%;"><i class="fa-solid fa-pen-to-square" style="margin-right:0.5em;"></i>Update Post</button>
    </form>
</section>
</main>
<?php include '../includes/footer.php'; ?>
</body>
</html>
