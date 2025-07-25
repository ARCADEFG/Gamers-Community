<?php
// index.php

// Define base paths at the very top
define('FS_BASE', __DIR__); // Filesystem base
define('WEB_BASE', '/Gamers_Community/'); // Web-accessible base

// Set current page for active navigation highlighting
$current_page = 'index.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include FS_BASE . '/config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gamers Community</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Orbitron:wght@700;800&family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Your CSS - using WEB_BASE -->
    <link rel="stylesheet" href="<?= WEB_BASE ?>assets/css/style.css">
</head>
<body>

    <?php include FS_BASE . '/includes/header.php'; ?>

    <main class="main-content">
        <h2>Welcome to the Gamers Community</h2>
        <?php
        if (isset($_SESSION['user_id'])) {
            // Post creation form
            echo '<section class="form-section create-post-section" style="max-width:520px;margin:2.5em auto 2.5em auto;padding:2.2em 2.5em 2em 2.5em;background:#19192b;border-radius:16px;box-shadow:0 2px 18px rgba(0,0,0,0.19);">';
            echo '<h3 style="font-family:Orbitron,sans-serif;font-size:1.7em;margin-bottom:1.3em;letter-spacing:1px;font-weight:800;color:var(--primary);text-align:center;">'
                . '<i class="fa-solid fa-pen-to-square" style="margin-right:0.5em;color:#8f94fb;"></i>Create a Post</h3>';
            echo '<form method="POST" enctype="multipart/form-data" class="create-post-form" style="display:flex;flex-direction:column;gap:1.2em;">';
            echo '<div style="display:flex;flex-direction:column;gap:0.4em;">';
            echo '<label for="title" style="font-family:Montserrat,sans-serif;font-weight:600;color:#e0e0e0;font-size:1.09em;margin-bottom:0.1em;">'
                . '<i class="fa-solid fa-heading" style="margin-right:0.4em;color:#4e54c8;"></i>Title</label>';
            echo '<input id="title" name="title" required placeholder="Post Title" style="padding:0.7em 1em;border-radius:7px;border:1px solid #35355a;background:#23233a;color:#f4f4f4;font-size:1.09em;font-family:Montserrat,sans-serif;">';
            echo '</div>';
            echo '<div style="display:flex;flex-direction:column;gap:0.4em;">';
            echo '<label for="content" style="font-family:Montserrat,sans-serif;font-weight:600;color:#e0e0e0;font-size:1.09em;margin-bottom:0.1em;">'
                . '<i class="fa-solid fa-align-left" style="margin-right:0.4em;color:#4e54c8;"></i>Description</label>';
            echo '<textarea id="content" name="content" required placeholder="Content" rows="4" style="padding:0.7em 1em;border-radius:7px;border:1px solid #35355a;background:#23233a;color:#f4f4f4;font-size:1.09em;font-family:Montserrat,sans-serif;resize:vertical;"></textarea>';
            echo '</div>';
            echo '<div style="display:flex;flex-direction:column;gap:0.4em;">';
            echo '<label for="media" style="font-family:Montserrat,sans-serif;font-weight:600;color:#e0e0e0;font-size:1.09em;margin-bottom:0.1em;">'
                . '<i class="fa-solid fa-photo-film" style="margin-right:0.4em;color:#4e54c8;"></i>Media (optional)</label>';
            echo '<input id="media" type="file" name="media" accept="image/*,video/*" style="padding:0.5em 0.5em;border-radius:7px;background:#23233a;color:#f4f4f4;font-size:1.03em;font-family:Montserrat,sans-serif;">';
            echo '</div>';
            echo '<button type="submit" style="margin-top:0.8em;padding:0.85em 0;font-size:1.13em;font-family:Orbitron,sans-serif;font-weight:700;background:linear-gradient(90deg,#4e54c8,#8f94fb);color:#fff;border:none;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.13);cursor:pointer;transition:background 0.2s;letter-spacing:1px;">'
                . '<i class="fa-solid fa-paper-plane" style="margin-right:0.5em;"></i>Post</button>';
            echo '</form>';
            echo '</section>';

            // Handle post submission
            if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['title'], $_POST['content'])) {
                $uid = $_SESSION['user_id'];
                $title = $_POST['title'];
                $content = $_POST['content'];
                $stmt = $conn->prepare("INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $uid, $title, $content);
                if ($stmt->execute()) {
                    echo '<div class="success">&#x2705; Post created.</div>';
                } else {
                    echo '<div class="error-msg">&#x274c; ' . $stmt->error . '</div>';
                }
                $stmt->close();
            }

            // Show posts
            echo '<section class="posts-section">';
            echo '<h3>Recent Posts</h3>';
            $res = $conn->query("SELECT posts.id, posts.title, posts.content, posts.created_at, users.username, posts.media_path, posts.media_type FROM posts JOIN users ON users.id = posts.user_id ORDER BY posts.created_at DESC LIMIT 10");
            echo '<div class="posts-list">';
            while ($row = $res->fetch_assoc()) {
                echo '<article class="post-card" style="margin-bottom:2em; background:#23233a; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.18); overflow:hidden;">';
                echo '<header class="post-card-header" style="padding:1.2em 1.5em 0.5em 1.5em;">';
                echo '<h3 class="post-title" style="font-family:Orbitron,sans-serif;font-size:2em;margin:0 0 0.2em 0;letter-spacing:1px;font-weight:800;">'
                    . '<a href="' . WEB_BASE . 'posts/view_post.php?id=' . $row['id'] . '" style="color:var(--primary);text-decoration:none;">' . htmlspecialchars($row['title']) . '</a></h3>';
                echo '<span class="meta" style="color:#b0b0b0;font-size:0.95em;">by ' . htmlspecialchars($row['username']) . ' on ' . $row['created_at'] . '</span>';
                echo '</header>';
                // Always reserve a space for media, use root-relative path
                echo '<div style="text-align:center;padding:1em 0 0.5em 0;min-height:180px;display:flex;align-items:center;justify-content:center;background:#23233a;">';
                if ($row['media_path'] && $row['media_type'] === 'image') {
                    $mediaPath = WEB_BASE . ltrim($row['media_path'], '/');
                    echo '<img src="' . htmlspecialchars($mediaPath) . '" alt="Post Image" style="max-width:96%;max-height:340px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.15);object-fit:cover;">';
                } elseif ($row['media_path'] && $row['media_type'] === 'video') {
                    $mediaPath = WEB_BASE . ltrim($row['media_path'], '/');
                    echo '<video controls style="max-width:96%;max-height:340px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.15);background:#000;">';
                    echo '<source src="' . htmlspecialchars($mediaPath) . '" type="video/mp4">Your browser does not support the video tag.';
                    echo '</video>';
                } else {
                    // Placeholder for no media
                    echo '<div style="width:100%;height:160px;display:flex;align-items:center;justify-content:center;background:#23233a;">'
                        . '<i class="fa-regular fa-image" style="font-size:3em;color:#35355a;"></i></div>';
                }
                echo '</div>';
                echo '<div class="post-content" style="padding:1em 1.5em 1.5em 1.5em;font-size:1.15em;line-height:1.7;color:#f4f4f4;font-family:Montserrat,sans-serif;">' . nl2br(htmlspecialchars($row['content'])) . '</div>';
                echo '</article>';
            }
            echo '</div>';
            echo '</section>';
        } else {
            echo '<section class="form-section">';
            echo '<div class="auth-prompt">';
            echo '<p>Please <a href="' . WEB_BASE . 'auth/login.php">login</a> or <a href="' . WEB_BASE . 'auth/register.php">register</a> to join the community and post.</p>';
            echo '</div>';
            echo '</section>';
        }
        ?>
    </main>

    <?php include FS_BASE . '/includes/footer.php'; ?>

<script>
// Better dropdown behavior
document.addEventListener('DOMContentLoaded', function() {
  const dropdowns = document.querySelectorAll('.user-dropdown');
  
  dropdowns.forEach(dropdown => {
    const toggle = dropdown.querySelector('.user-menu-toggle');
    const menu = dropdown.querySelector('.dropdown-menu');
    
    // Toggle on click
    toggle.addEventListener('click', (e) => {
      e.stopPropagation();
      menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    });
    
    // Close when clicking outside
    document.addEventListener('click', () => {
      menu.style.display = 'none';
    });
  });
});
</script>

</body>
</html><?php
// index.php

// Define base paths at the very top
define('FS_BASE', __DIR__); // Filesystem base
define('WEB_BASE', '/Gamers_Community/'); // Web-accessible base

// Set current page for active navigation highlighting
$current_page = 'index.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include FS_BASE . '/config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gamers Community</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Orbitron:wght@700;800&family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Your CSS - using WEB_BASE -->
    <link rel="stylesheet" href="<?= WEB_BASE ?>assets/css/style.css">
</head>
<body>

    <?php include FS_BASE . '/includes/header.php'; ?>

    <main class="main-content">
        <h2>Welcome to the Gamers Community</h2>
        <?php
        if (isset($_SESSION['user_id'])) {
            // Post creation form
            echo '<section class="form-section create-post-section" style="max-width:520px;margin:2.5em auto 2.5em auto;padding:2.2em 2.5em 2em 2.5em;background:#19192b;border-radius:16px;box-shadow:0 2px 18px rgba(0,0,0,0.19);">';
            echo '<h3 style="font-family:Orbitron,sans-serif;font-size:1.7em;margin-bottom:1.3em;letter-spacing:1px;font-weight:800;color:var(--primary);text-align:center;">'
                . '<i class="fa-solid fa-pen-to-square" style="margin-right:0.5em;color:#8f94fb;"></i>Create a Post</h3>';
            echo '<form method="POST" enctype="multipart/form-data" class="create-post-form" style="display:flex;flex-direction:column;gap:1.2em;">';
            echo '<div style="display:flex;flex-direction:column;gap:0.4em;">';
            echo '<label for="title" style="font-family:Montserrat,sans-serif;font-weight:600;color:#e0e0e0;font-size:1.09em;margin-bottom:0.1em;">'
                . '<i class="fa-solid fa-heading" style="margin-right:0.4em;color:#4e54c8;"></i>Title</label>';
            echo '<input id="title" name="title" required placeholder="Post Title" style="padding:0.7em 1em;border-radius:7px;border:1px solid #35355a;background:#23233a;color:#f4f4f4;font-size:1.09em;font-family:Montserrat,sans-serif;">';
            echo '</div>';
            echo '<div style="display:flex;flex-direction:column;gap:0.4em;">';
            echo '<label for="content" style="font-family:Montserrat,sans-serif;font-weight:600;color:#e0e0e0;font-size:1.09em;margin-bottom:0.1em;">'
                . '<i class="fa-solid fa-align-left" style="margin-right:0.4em;color:#4e54c8;"></i>Description</label>';
            echo '<textarea id="content" name="content" required placeholder="Content" rows="4" style="padding:0.7em 1em;border-radius:7px;border:1px solid #35355a;background:#23233a;color:#f4f4f4;font-size:1.09em;font-family:Montserrat,sans-serif;resize:vertical;"></textarea>';
            echo '</div>';
            echo '<div style="display:flex;flex-direction:column;gap:0.4em;">';
            echo '<label for="media" style="font-family:Montserrat,sans-serif;font-weight:600;color:#e0e0e0;font-size:1.09em;margin-bottom:0.1em;">'
                . '<i class="fa-solid fa-photo-film" style="margin-right:0.4em;color:#4e54c8;"></i>Media (optional)</label>';
            echo '<input id="media" type="file" name="media" accept="image/*,video/*" style="padding:0.5em 0.5em;border-radius:7px;background:#23233a;color:#f4f4f4;font-size:1.03em;font-family:Montserrat,sans-serif;">';
            echo '</div>';
            echo '<button type="submit" style="margin-top:0.8em;padding:0.85em 0;font-size:1.13em;font-family:Orbitron,sans-serif;font-weight:700;background:linear-gradient(90deg,#4e54c8,#8f94fb);color:#fff;border:none;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.13);cursor:pointer;transition:background 0.2s;letter-spacing:1px;">'
                . '<i class="fa-solid fa-paper-plane" style="margin-right:0.5em;"></i>Post</button>';
            echo '</form>';
            echo '</section>';

            // Handle post submission
            if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['title'], $_POST['content'])) {
                $uid = $_SESSION['user_id'];
                $title = $_POST['title'];
                $content = $_POST['content'];
                $stmt = $conn->prepare("INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $uid, $title, $content);
                if ($stmt->execute()) {
                    echo '<div class="success">&#x2705; Post created.</div>';
                } else {
                    echo '<div class="error-msg">&#x274c; ' . $stmt->error . '</div>';
                }
                $stmt->close();
            }

            // Show posts
            echo '<section class="posts-section">';
            echo '<h3>Recent Posts</h3>';
            $res = $conn->query("SELECT posts.id, posts.title, posts.content, posts.created_at, users.username, posts.media_path, posts.media_type FROM posts JOIN users ON users.id = posts.user_id ORDER BY posts.created_at DESC LIMIT 10");
            echo '<div class="posts-list">';
            while ($row = $res->fetch_assoc()) {
                echo '<article class="post-card" style="margin-bottom:2em; background:#23233a; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.18); overflow:hidden;">';
                echo '<header class="post-card-header" style="padding:1.2em 1.5em 0.5em 1.5em;">';
                echo '<h3 class="post-title" style="font-family:Orbitron,sans-serif;font-size:2em;margin:0 0 0.2em 0;letter-spacing:1px;font-weight:800;">'
                    . '<a href="' . WEB_BASE . 'posts/view_post.php?id=' . $row['id'] . '" style="color:var(--primary);text-decoration:none;">' . htmlspecialchars($row['title']) . '</a></h3>';
                echo '<span class="meta" style="color:#b0b0b0;font-size:0.95em;">by ' . htmlspecialchars($row['username']) . ' on ' . $row['created_at'] . '</span>';
                echo '</header>';
                // Always reserve a space for media, use root-relative path
                echo '<div style="text-align:center;padding:1em 0 0.5em 0;min-height:180px;display:flex;align-items:center;justify-content:center;background:#23233a;">';
                if ($row['media_path'] && $row['media_type'] === 'image') {
                    $mediaPath = WEB_BASE . ltrim($row['media_path'], '/');
                    echo '<img src="' . htmlspecialchars($mediaPath) . '" alt="Post Image" style="max-width:96%;max-height:340px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.15);object-fit:cover;">';
                } elseif ($row['media_path'] && $row['media_type'] === 'video') {
                    $mediaPath = WEB_BASE . ltrim($row['media_path'], '/');
                    echo '<video controls style="max-width:96%;max-height:340px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.15);background:#000;">';
                    echo '<source src="' . htmlspecialchars($mediaPath) . '" type="video/mp4">Your browser does not support the video tag.';
                    echo '</video>';
                } else {
                    // Placeholder for no media
                    echo '<div style="width:100%;height:160px;display:flex;align-items:center;justify-content:center;background:#23233a;">'
                        . '<i class="fa-regular fa-image" style="font-size:3em;color:#35355a;"></i></div>';
                }
                echo '</div>';
                echo '<div class="post-content" style="padding:1em 1.5em 1.5em 1.5em;font-size:1.15em;line-height:1.7;color:#f4f4f4;font-family:Montserrat,sans-serif;">' . nl2br(htmlspecialchars($row['content'])) . '</div>';
                echo '</article>';
            }
            echo '</div>';
            echo '</section>';
        } else {
            echo '<section class="form-section">';
            echo '<div class="auth-prompt">';
            echo '<p>Please <a href="' . WEB_BASE . 'auth/login.php">login</a> or <a href="' . WEB_BASE . 'auth/register.php">register</a> to join the community and post.</p>';
            echo '</div>';
            echo '</section>';
        }
        ?>
    </main>

    <?php include FS_BASE . '/includes/footer.php'; ?>

<script>
// Better dropdown behavior
document.addEventListener('DOMContentLoaded', function() {
  const dropdowns = document.querySelectorAll('.user-dropdown');
  
  dropdowns.forEach(dropdown => {
    const toggle = dropdown.querySelector('.user-menu-toggle');
    const menu = dropdown.querySelector('.dropdown-menu');
    
    // Toggle on click
    toggle.addEventListener('click', (e) => {
      e.stopPropagation();
      menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    });
    
    // Close when clicking outside
    document.addEventListener('click', () => {
      menu.style.display = 'none';
    });
  });
});
</script>

</body>
</html>
