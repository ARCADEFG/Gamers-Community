<?php // index.php ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gamers Community</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Orbitron:wght@700;800&family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Your CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>


    <main class="main-content">
        <h2>Welcome to the Gamers Community</h2>
        <?php
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        include 'config/db.php';
        if (isset($_SESSION['user_id'])) {
            // Post creation form
            echo '<section class="form-section create-post-section">';
            echo '<h3>Create a Post</h3>';
            echo '<form method="POST" class="login-form">';
            echo '<input name="title" required placeholder="Post Title">';
            echo '<textarea name="content" required placeholder="Content"></textarea>';
            echo '<button type="submit">Post</button>';
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
            $res = $conn->query("SELECT posts.id, posts.title, posts.content, posts.created_at, users.username FROM posts JOIN users ON users.id = posts.user_id ORDER BY posts.created_at DESC LIMIT 10");
            echo '<div class="posts-list">';
            while ($row = $res->fetch_assoc()) {
                echo '<article class="post-card">';
                echo '<header class="post-card-header">';
                echo '<h4 class="post-title"><a href="posts/view_post.php?id=' . $row['id'] . '">' . htmlspecialchars($row['title']) . '</a></h4>';
                echo '<span class="meta">by ' . htmlspecialchars($row['username']) . ' on ' . $row['created_at'] . '</span>';
                echo '</header>';
                echo '<div class="post-content">' . nl2br(htmlspecialchars($row['content'])) . '</div>';
                echo '</article>';
            }
            echo '</div>';
            echo '</section>';
        } else {
            echo '<section class="form-section">';
            echo '<div class="auth-prompt">';
            echo '<p>Please <a href="auth/login.php">login</a> or <a href="auth/register.php">register</a> to join the community and post.</p>';
            echo '</div>';
            echo '</section>';
        }
        ?>
    </main>

    <?php include 'includes/footer.php'; ?>

</body>
</html>
