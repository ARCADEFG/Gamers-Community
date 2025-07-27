<?php
session_start();
require_once __DIR__ . '/../config/config.php';
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . WEB_BASE . "auth/login.php");
    exit;
}

// Check if this is a confirmed deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $pid = $_POST['post_id'] ?? 0;
    $community_id = $_POST['community_id'] ?? null;

    // Verify post ownership
    $stmt = $conn->prepare("SELECT user_id, community_id FROM posts WHERE id = ?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();
    $stmt->close();

    if (!$post) {
        $_SESSION['error'] = "Post not found";
        header("Location: " . WEB_BASE . "index.php");
        exit;
    }

    if ($post['user_id'] !== $_SESSION['user_id']) {
        $_SESSION['error'] = "You are not authorized to delete this post";
        header("Location: " . WEB_BASE . "index.php");
        exit;
    }

    // Delete the post
    $del = $conn->prepare("DELETE FROM posts WHERE id = ?");
    $del->bind_param("i", $pid);
    
    if ($del->execute()) {
        $_SESSION['success'] = "Post deleted successfully";
        
        // Delete associated media file if exists
        if (!empty($post['media_path'])) {
            $media_path = '../' . $post['media_path'];
            if (file_exists($media_path)) {
                unlink($media_path);
            }
        }
        
        // Redirect to appropriate page
        if ($post['community_id']) {
            header("Location: " . WEB_BASE . "communities/community_view.php?id=" . $post['community_id']);
        } else {
            header("Location: " . WEB_BASE . "index.php");
        }
        exit;
    } else {
        $_SESSION['error'] = "Failed to delete post";
        header("Location: " . WEB_BASE . ($post['community_id'] ? "communities/community_view.php?id=".$post['community_id'] : "index.php"));
        exit;
    }
}

// If GET request, show confirmation page
$pid = $_GET['id'] ?? 0;
$community_id = $_GET['community_id'] ?? null;

// Verify post exists and belongs to user
$stmt = $conn->prepare("SELECT p.*, u.username FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id = ? AND p.user_id = ?");
$stmt->bind_param("ii", $pid, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();
$stmt->close();

if (!$post) {
    $_SESSION['error'] = "Post not found or you're not authorized";
    header("Location: " . WEB_BASE . ($community_id ? "communities/community_view.php?id=".$community_id : "index.php"));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Delete</title>
    <link rel="stylesheet" href="<?= WEB_BASE ?>assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Orbitron:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .confirmation-dialog {
            background: #19192b;
            border-radius: 16px;
            padding: 2rem;
            max-width: 500px;
            margin: 2rem auto;
            text-align: center;
            box-shadow: 0 2px 18px rgba(0,0,0,0.19);
        }
        .confirmation-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }
        .btn-confirm {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-yes {
            background: #ff4d4d;
            color: white;
        }
        .btn-yes:hover {
            background: #ff3333;
        }
        .btn-no {
            background: #35355a;
            color: white;
        }
        .btn-no:hover {
            background: #4e54c8;
        }
        .post-preview {
            background: #23233a;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            text-align: left;
        }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<main class="main-content">
    <div class="confirmation-dialog">
        <h2><i class="fas fa-exclamation-triangle" style="color: #ff4d4d;"></i> Delete Post</h2>
        <p>Are you sure you want to delete this post? This action cannot be undone.</p>
        
        <div class="post-preview">
            <h3><?= htmlspecialchars($post['title']) ?></h3>
            <p><?= nl2br(htmlspecialchars(substr($post['content'], 0, 200))) ?><?= strlen($post['content']) > 200 ? '...' : '' ?></p>
            <?php if (!empty($post['media_path'])): ?>
                <p><i class="fas fa-paperclip"></i> Contains media</p>
            <?php endif; ?>
        </div>
        
        <form method="POST" class="confirmation-actions">
            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
            <?php if ($post['community_id']): ?>
                <input type="hidden" name="community_id" value="<?= $post['community_id'] ?>">
            <?php endif; ?>
            
            <button type="submit" name="confirm_delete" value="1" class="btn-confirm btn-yes">
                <i class="fas fa-trash-alt"></i> Yes, Delete
            </button>
            <a href="<?= $post['community_id'] ? WEB_BASE.'communities/community_view.php?id='.$post['community_id'] : WEB_BASE.'index.php' ?>" class="btn-confirm btn-no">
                <i class="fas fa-times"></i> No, Cancel
            </a>
        </form>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>