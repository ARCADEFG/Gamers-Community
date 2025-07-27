<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . WEB_BASE . "auth/login.php");
    exit;
}

$post_id = $_GET['id'] ?? null;
if (!$post_id) {
    $_SESSION['error'] = "Post ID missing";
    header("Location: " . WEB_BASE . "index.php");
    exit;
}

// Get post data including community_id
$stmt = $conn->prepare("SELECT p.*, u.username FROM posts p 
                       JOIN users u ON p.user_id = u.id 
                       WHERE p.id = ? AND p.user_id = ?");
$stmt->bind_param("ii", $post_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if (!$post) {
    $_SESSION['error'] = "Post not found or you're not authorized to edit it";
    header("Location: " . WEB_BASE . "index.php");
    exit;
}

$community_id = $post['community_id'] ?? null;
$is_community_post = !is_null($community_id);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $game_name = trim($_POST['game_name'] ?? '');
    $errors = [];
    
    if (empty($title)) $errors[] = 'Title is required';
    if (empty($content)) $errors[] = 'Content is required';
    if (empty($game_name)) $errors[] = 'Game name is required';
    
    $media_path = $post['media_path'];
    $media_type = $post['media_type'];
    
    // Handle media upload if new file was provided
    if (isset($_FILES['media']) && $_FILES['media']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['media']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg','image/png','image/gif','image/webp','video/mp4','video/webm','video/ogg'];
            $file_type = $_FILES['media']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                $ext = pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION);
                $media_type = strpos($file_type, 'image/') === 0 ? 'image' : 'video';
                
                // Set upload directory based on post type
                $user_dir = '../uploads/user_' . $_SESSION['user_id'] . '/';
                if ($is_community_post) {
                    $upload_dir = $user_dir . 'community_posts/';
                    $web_path = 'uploads/user_' . $_SESSION['user_id'] . '/community_posts/';
                } else {
                    $upload_dir = $user_dir . 'general_posts/';
                    $web_path = 'uploads/user_' . $_SESSION['user_id'] . '/general_posts/';
                }
                
                // Create directories if they don't exist
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $filename = uniqid('media_') . '.' . $ext;
                $target = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['media']['tmp_name'], $target)) {
                    // Delete old media file if it exists
                    if (!empty($post['media_path'])) {
                        $old_file = '../' . $post['media_path'];
                        if (file_exists($old_file)) {
                            unlink($old_file);
                        }
                    }
                    $media_path = $web_path . $filename;
                } else {
                    $errors[] = 'Failed to upload new media file';
                }
            } else {
                $errors[] = 'Invalid file type. Allowed: JPEG, PNG, GIF, WebP, MP4, WebM, OGG';
            }
        } else {
            $errors[] = 'File upload error: ' . $_FILES['media']['error'];
        }
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE posts SET title = ?, content = ?, game_name = ?, media_path = ?, media_type = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $title, $content, $game_name, $media_path, $media_type, $post_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Post updated successfully!";
            
            // Redirect to appropriate view based on post type
            if ($is_community_post) {
                header("Location: " . WEB_BASE . "communities/community_view.php?id=" . $community_id);
            } else {
                header("Location: " . WEB_BASE . "posts/view_post.php?id=" . $post_id);
            }
            exit;
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['post_errors'] = $errors;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit <?= $is_community_post ? 'Community' : '' ?> Post</title>
    <link rel="stylesheet" href="<?= WEB_BASE ?>assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Orbitron:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<main class="main-content" style="max-width:1000px;margin:2em auto;">
    <section class="form-section create-post-section" style="background:#19192b;border-radius:16px;box-shadow:0 2px 18px rgba(0,0,0,0.19);padding:2.5em 2.5em 2em 2.5em;">
        <h3 class="post-title" style="font-family:Orbitron,sans-serif;font-size:2em;margin-bottom:1.3em;letter-spacing:1px;font-weight:800;color:var(--primary);text-align:left;">
            <i class="fa-solid fa-pen-to-square" style="margin-right:0.5em;color:#8f94fb;"></i>
            Edit <?= $is_community_post ? 'Community' : '' ?> Post
        </h3>
        
        <?php if (isset($_SESSION['post_errors'])): ?>
            <?php foreach ($_SESSION['post_errors'] as $error): ?>
                <div class="error" style="color:#ff6b6b;margin-bottom:1em;font-family:Montserrat,sans-serif;">
                    <i class="fa-solid fa-circle-exclamation" style="margin-right:0.5em;"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endforeach; ?>
            <?php unset($_SESSION['post_errors']); ?>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="create-post-form" style="width:95%;">
            <?php if ($is_community_post): ?>
                <input type="hidden" name="community_id" value="<?= $community_id ?>">
            <?php endif; ?>
            
            <table style="width:100%;border-collapse:separate;border-spacing:0 1.1em;table-layout:fixed;">
                <tr>
                    <td style="width:160px;vertical-align:top;padding-right:1em;">
                        <label for="title" style="font-family:Montserrat,sans-serif;font-weight:600;color:#e0e0e0;font-size:1.13em;white-space:nowrap;">
                            <i class="fa-solid fa-heading" style="margin-right:0.4em;color:#4e54c8;"></i>Title
                        </label>
                    </td>
                    <td>
                        <input id="title" name="title" required placeholder="Post Title" 
                               value="<?= htmlspecialchars($post['title']) ?>"
                               style="width:100%;padding:0.9em 1.1em;border-radius:7px;border:1px solid #35355a;background:#23233a;color:#f4f4f4;font-size:1.13em;font-family:Montserrat,sans-serif;">
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align:top;padding-right:1em;">
                        <label for="game_name" style="font-family:Montserrat,sans-serif;font-weight:600;color:#e0e0e0;font-size:1.13em;white-space:nowrap;">
                            <i class="fa-solid fa-gamepad" style="margin-right:0.4em;color:#4e54c8;"></i>Game Name
                        </label>
                    </td>
                    <td>
                        <input id="game_name" name="game_name" required placeholder="Game Name" 
                               value="<?= htmlspecialchars($post['game_name']) ?>"
                               style="width:100%;padding:0.9em 1.1em;border-radius:7px;border:1px solid #35355a;background:#23233a;color:#f4f4f4;font-size:1.13em;font-family:Montserrat,sans-serif;">
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align:top;padding-right:1em;">
                        <label for="content" style="font-family:Montserrat,sans-serif;font-weight:600;color:#e0e0e0;font-size:1.13em;white-space:nowrap;">
                            <i class="fa-solid fa-align-left" style="margin-right:0.4em;color:#4e54c8;"></i>Description
                        </label>
                    </td>
                    <td>
                        <textarea id="content" name="content" required placeholder="Content" rows="4"
                                  style="width:100%;padding:0.9em 1.1em;border-radius:7px;border:1px solid #35355a;background:#23233a;color:#f4f4f4;font-size:1.13em;font-family:Montserrat,sans-serif;resize:vertical;"><?= htmlspecialchars($post['content']) ?></textarea>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align:top;padding-right:1em;">
                        <label style="font-family:Montserrat,sans-serif;font-weight:600;color:#e0e0e0;font-size:1.13em;white-space:nowrap;">
                            <i class="fa-solid fa-photo-film" style="margin-right:0.4em;color:#4e54c8;"></i>Current Media
                        </label>
                    </td>
                    <td>
                        <?php if (!empty($post['media_path'])): ?>
                            <div style="margin-bottom:1em;">
                                <?php if ($post['media_type'] === 'image'): ?>
                                    <img src="<?= WEB_BASE . htmlspecialchars($post['media_path']) ?>" style="max-width:200px;max-height:200px;border-radius:8px;">
                                <?php else: ?>
                                    <video controls style="max-width:200px;max-height:200px;border-radius:8px;">
                                        <source src="<?= WEB_BASE . htmlspecialchars($post['media_path']) ?>" type="video/mp4">
                                    </video>
                                <?php endif; ?>
                                <div style="margin-top:0.5em;">
                                    <label>
                                        <input type="checkbox" name="remove_media" value="1"> Remove current media
                                    </label>
                                </div>
                            </div>
                        <?php else: ?>
                            <p style="color:#888;">No media attached</p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align:top;padding-right:1em;">
                        <label for="media" style="font-family:Montserrat,sans-serif;font-weight:600;color:#e0e0e0;font-size:1.13em;white-space:nowrap;">
                            <i class="fa-solid fa-photo-film" style="margin-right:0.4em;color:#4e54c8;"></i>New Media
                        </label>
                    </td>
                    <td>
                        <input id="media" type="file" name="media" accept="image/*,video/*" 
                               style="width:100%;padding:0.7em 0.5em;border-radius:7px;background:#23233a;color:#f4f4f4;font-size:1.09em;font-family:Montserrat,sans-serif;">
                        <p style="color:#888;font-size:0.9em;margin-top:0.5em;">Allowed: JPEG, PNG, GIF, WebP, MP4, WebM, OGG</p>
                    </td>
                </tr>
            </table>
            
            <button type="submit" style="margin-top:1.5em;padding:1em 0;font-size:1.18em;font-family:Orbitron,sans-serif;font-weight:700;background:linear-gradient(90deg,#4e54c8,#8f94fb);color:#fff;border:none;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.13);cursor:pointer;transition:background 0.2s;letter-spacing:1px;width:100%;">
                <i class="fa-solid fa-paper-plane" style="margin-right:0.5em;"></i>Update Post
            </button>
        </form>
    </section>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>