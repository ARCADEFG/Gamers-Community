<?php
session_start();
require_once __DIR__ . '/../config/config.php';
include '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . WEB_BASE . "auth/login.php");
    exit;
}

$uid = $_SESSION['user_id'];
$community_id = isset($_GET['community_id']) ? (int)$_GET['community_id'] : null;
$is_member = false;

// If this is a community post, verify the user is a member
if ($community_id) {
    $stmt = $conn->prepare("SELECT 1 FROM community_members WHERE community_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $community_id, $uid);
    $stmt->execute();
    $is_member = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    
    if (!$is_member) {
        $_SESSION['error'] = "You must be a member of this community to post";
        header("Location: " . WEB_BASE . "communities/community_view.php?id=" . $community_id);
        exit;
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
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
                
                // Set up user-specific directory structure
                $user_dir = '../uploads/user_' . $uid . '/';
                if ($community_id) {
                    $upload_dir = $user_dir . 'community_posts/';
                    $web_path = 'uploads/user_' . $uid . '/community_posts/';
                } else {
                    $upload_dir = $user_dir . 'general_posts/';
                    $web_path = 'uploads/user_' . $uid . '/general_posts/';
                }
                
                // Create directories if they don't exist
                if (!is_dir($user_dir)) {
                    if (!mkdir($user_dir, 0777, true)) {
                        $errors[] = 'Failed to create user directory.';
                    }
                }
                
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0777, true)) {
                        $errors[] = 'Failed to create posts directory.';
                    }
                }
                
                if (empty($errors)) {
                    $filename = uniqid('media_') . '.' . $ext;
                    $target = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['media']['tmp_name'], $target)) {
                        $media_path = $web_path . $filename;
                    } else {
                        $errors[] = 'Failed to upload file.';
                    }
                }
            } else {
                $errors[] = 'Invalid file type. Allowed: JPEG, PNG, GIF, WebP, MP4, WebM, OGG';
            }
        } else {
            $errors[] = 'File upload error: ' . $_FILES['media']['error'];
        }
    }
    
    if (empty($errors)) {
        if ($community_id) {
            // Community post
            $stmt = $conn->prepare("INSERT INTO posts (user_id, title, content, game_name, media_path, media_type, community_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssi", $uid, $title, $content, $game_name, $media_path, $media_type, $community_id);
        } else {
            // General post
            $stmt = $conn->prepare("INSERT INTO posts (user_id, title, content, game_name, media_path, media_type) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $uid, $title, $content, $game_name, $media_path, $media_type);
        }
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Post created successfully!";
            
            // Redirect to appropriate page after successful post
            if ($community_id) {
                header("Location: " . WEB_BASE . "communities/community_view.php?id=" . $community_id);
            } else {
                header("Location: " . WEB_BASE . "index.php");
            }
            exit;
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
    
    // Store errors in session to display after redirect
    if (!empty($errors)) {
        $_SESSION['post_errors'] = $errors;
        $_SESSION['post_data'] = [
            'title' => $title,
            'content' => $content,
            'game_name' => $game_name
        ];
        
        if ($community_id) {
            header("Location: " . WEB_BASE . "posts/create_post.php?community_id=" . $community_id);
        } else {
            header("Location: " . WEB_BASE . "posts/create_post.php");
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $community_id ? 'Create Community Post' : 'Create Post' ?></title>
    <link rel="stylesheet" href="<?= WEB_BASE ?>assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Orbitron:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<main class="main-content" style="max-width:900px;margin:2em auto;">
    <section class="form-section create-post-section" style="background:#19192b;border-radius:16px;box-shadow:0 2px 18px rgba(0,0,0,0.19);padding:2.5em 2.5em 2em 2.5em;">
        <h3 class="post-title" style="font-family:Orbitron,sans-serif;font-size:2em;margin-bottom:1.3em;letter-spacing:1px;font-weight:800;color:var(--primary);text-align:left;">
            <i class="fa-solid fa-pen-to-square" style="margin-right:0.5em;color:#8f94fb;"></i>
            <?= $community_id ? 'Create Community Post' : 'Create Post' ?>
        </h3>
        
        <?php
        // Display success message if redirected after successful post
        if (isset($_SESSION['success'])) {
            echo "<div class='success' style='background:linear-gradient(90deg,#4e54c8,#8f94fb);color:#fff;padding:1em 1.5em;border-radius:8px;display:flex;align-items:center;gap:0.7em;font-size:1.15em;font-family:Montserrat,sans-serif;margin-bottom:1.2em;box-shadow:0 2px 8px rgba(0,0,0,0.13);'>
                    <i class='fa-solid fa-circle-check' style='color:#00e676;font-size:1.5em;'></i>
                    " . htmlspecialchars($_SESSION['success']) . "
                  </div>";
            unset($_SESSION['success']);
        }
        
        // Display errors if any
        if (isset($_SESSION['post_errors'])) {
            foreach ($_SESSION['post_errors'] as $err) {
                echo "<div class='error' style='color:#ff6b6b;margin-bottom:1em;font-family:Montserrat,sans-serif;'>
                        <i class='fa-solid fa-circle-exclamation' style='margin-right:0.5em;'></i>
                        " . htmlspecialchars($err) . "
                      </div>";
            }
            unset($_SESSION['post_errors']);
        }
        ?>
        
        <form method="POST" enctype="multipart/form-data" class="create-post-form" style="width:100%;">
            <?php if ($community_id): ?>
                <input type="hidden" name="community_id" value="<?= $community_id ?>">
            <?php endif; ?>
            
            <table style="width:100%;border-collapse:separate;border-spacing:0 1.1em;">
                <tr>
                    <td style="width:120px;">
                        <label for="title" style="font-family:Montserrat,sans-serif;font-weight:600;color:#e0e0e0;font-size:1.13em;">
                            <i class="fa-solid fa-heading" style="margin-right:0.4em;color:#4e54c8;"></i>Title
                        </label>
                    </td>
                    <td>
                        <input id="title" name="title" required placeholder="Post Title" 
                               value="<?= isset($_SESSION['post_data']['title']) ? htmlspecialchars($_SESSION['post_data']['title']) : '' ?>"
                               style="width:90%;padding:0.9em 1.1em;border-radius:7px;border:1px solid #35355a;background:#23233a;color:#f4f4f4;font-size:1.13em;font-family:Montserrat,sans-serif;">
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="game_name" style="font-family:Montserrat,sans-serif;font-weight:600;color:#e0e0e0;font-size:1.13em;">
                            <i class="fa-solid fa-gamepad" style="margin-right:0.4em;color:#4e54c8;"></i>Game Name
                        </label>
                    </td>
                    <td>
                        <input id="game_name" name="game_name" required placeholder="Game Name" 
                               value="<?= isset($_SESSION['post_data']['game_name']) ? htmlspecialchars($_SESSION['post_data']['game_name']) : '' ?>"
                               style="width:90%;padding:0.9em 1.1em;border-radius:7px;border:1px solid #35355a;background:#23233a;color:#f4f4f4;font-size:1.13em;font-family:Montserrat,sans-serif;">
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align:top;">
                        <label for="content" style="font-family:Montserrat,sans-serif;font-weight:600;color:#e0e0e0;font-size:1.13em;">
                            <i class="fa-solid fa-align-left" style="margin-right:0.4em;color:#4e54c8;"></i>Description
                        </label>
                    </td>
                    <td>
                        <textarea id="content" name="content" required placeholder="Content" rows="4"
                                  style="width:90%;padding:0.9em 1.1em;border-radius:7px;border:1px solid #35355a;background:#23233a;color:#f4f4f4;font-size:1.13em;font-family:Montserrat,sans-serif;resize:vertical;"><?= isset($_SESSION['post_data']['content']) ? htmlspecialchars($_SESSION['post_data']['content']) : '' ?></textarea>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="media" style="font-family:Montserrat,sans-serif;font-weight:600;color:#e0e0e0;font-size:1.13em;">
                            <i class="fa-solid fa-photo-film" style="margin-right:0.4em;color:#4e54c8;"></i>Media (optional)
                        </label>
                    </td>
                    <td>
                        <input id="media" type="file" name="media" accept="image/*,video/*" 
                               style="width:90%;padding:0.7em 0.5em;border-radius:7px;background:#23233a;color:#f4f4f4;font-size:1.09em;font-family:Montserrat,sans-serif;">
                        <p style="color:#888;font-size:0.9em;margin-top:0.5em;">Allowed: JPEG, PNG, GIF, WebP, MP4, WebM, OGG</p>
                    </td>
                </tr>
            </table>
            
            <button type="submit" style="margin-top:1.5em;padding:1em 0;font-size:1.18em;font-family:Orbitron,sans-serif;font-weight:700;background:linear-gradient(90deg,#4e54c8,#8f94fb);color:#fff;border:none;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.13);cursor:pointer;transition:background 0.2s;letter-spacing:1px;width:100%;">
                <i class="fa-solid fa-paper-plane" style="margin-right:0.5em;"></i>Post
            </button>
        </form>
    </section>
</main>

<?php 
// Clear any stored post data from session
if (isset($_SESSION['post_data'])) {
    unset($_SESSION['post_data']);
}

include '../includes/footer.php'; 
?>
</body>
</html>