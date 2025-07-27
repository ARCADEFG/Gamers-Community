<?php
session_start();
include '../config/db.php';

// Define base paths
define('FS_BASE', dirname(__DIR__)); // Filesystem base
define('WEB_BASE', '/Gamers_Community/'); // Web base

if (!isset($_SESSION['user_id'])) {
    header("Location: " . WEB_BASE . "auth/login.php");
    exit;
}

$uid = $_SESSION['user_id'];

// Handle profile picture upload/delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $target_dir = FS_BASE . "/uploads/profile_pics/";
    
    // Handle file upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $tmp_name = $_FILES["profile_picture"]["tmp_name"];
        if (!empty($tmp_name) && is_uploaded_file($tmp_name)) {
            $check = @getimagesize($tmp_name);
            if ($check !== false) {
                $file_extension = strtolower(pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $new_filename = "user_" . $uid . "_" . time() . "." . $file_extension;
                    $target_file = $target_dir . $new_filename;
                    
                    // Delete old picture
                    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
                    $stmt->bind_param("i", $uid);
                    $stmt->execute();
                    $stmt->bind_result($old_picture);
                    $stmt->fetch();
                    $stmt->close();
                    
                    if ($old_picture && file_exists($target_dir . $old_picture)) {
                        @unlink($target_dir . $old_picture);
                    }
                    
                    if (move_uploaded_file($tmp_name, $target_file)) {
                        $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                        $stmt->bind_param("si", $new_filename, $uid);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        }
    }
    // Handle picture deletion
    elseif (isset($_POST['delete_picture'])) {
        $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $stmt->bind_result($old_picture);
        $stmt->fetch();
        $stmt->close();
        
        if ($old_picture && file_exists(FS_BASE . "/uploads/profile_pics/" . $old_picture)) {
            @unlink(FS_BASE . "/uploads/profile_pics/" . $old_picture);
        }
        
        $stmt = $conn->prepare("UPDATE users SET profile_picture = NULL WHERE id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $stmt->close();
    }
}

// Get user info
$stmt = $conn->prepare("SELECT username, email, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$stmt->bind_result($username, $email, $profile_picture);
$stmt->fetch();
$stmt->close();

// Set profile picture path
$has_profile_picture = !empty($profile_picture);
$profile_picture_path = $has_profile_picture 
    ? WEB_BASE . "uploads/profile_pics/" . $profile_picture
    : WEB_BASE . "assets/images/avatars/default.png";

// Check if header/footer files exist
$header_path = FS_BASE . '/includes/header.php';
$footer_path = FS_BASE . '/includes/footer.php';
$has_header = file_exists($header_path);
$has_footer = file_exists($footer_path);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Profile - Gamers Community</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Orbitron:wght@700;800&family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= WEB_BASE ?>assets/css/style.css">
    <style>
        :root {
            --primary: #6a11cb;
            --secondary: #2575fc;
            --accent: #ff4081;
            --light: #f4f4f4;
            --dark-bg: #121212;
            --card-bg: #2b2b2b;
            --input-bg: #3a3a3a;
            --text-muted: #aaa;
        }

        body {
            margin: 0;
            font-family: 'Montserrat', sans-serif;
            background-color: var(--dark-bg);
            color: white;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 15px;
        }

        /* Profile Header */
        .profile-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
            padding: 20px;
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        @media (min-width: 768px) {
            .profile-header {
                flex-direction: row;
                align-items: flex-start;
            }
        }

        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 0;
            margin-bottom: 15px;
            border: 3px solid var(--primary);
            box-shadow: 0 0 15px rgba(106, 17, 203, 0.5);
        }

        @media (min-width: 768px) {
            .profile-picture {
                width: 150px;
                height: 150px;
                margin-right: 20px;
                margin-bottom: 0;
            }
        }

        .profile-info {
            text-align: center;
            flex-grow: 1;
        }

        @media (min-width: 768px) {
            .profile-info {
                text-align: left;
            }
        }

        /* Edit Profile Form */
        .edit-profile-form {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .current-picture-container {
            margin-bottom: 20px;
            text-align: center;
        }

        .current-picture {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            margin-bottom: 10px;
            border: 2px solid var(--primary);
        }

        .no-picture-message {
            color: var(--text-muted);
            font-style: italic;
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 5px;
        }

        .picture-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin: 15px 0;
        }

        .file-input-container {
            position: relative;
            margin: 15px 0;
        }

        .file-input-label {
            display: block;
            padding: 12px 20px;
            background: var(--input-bg);
            color: white;
            border-radius: 25px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .file-input-label:hover {
            background: var(--primary);
        }

        .file-input {
            position: absolute;
            opacity: 0;
            width: 0.1px;
            height: 0.1px;
        }

        .file-name {
            margin-top: 5px;
            font-size: 0.85rem;
            color: var(--text-muted);
            text-align: center;
            word-break: break-word;
        }

        /* Buttons */
        .edit-profile-btn {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 0.9rem;
            width: 100%;
        }

        @media (min-width: 480px) {
            .edit-profile-btn {
                width: auto;
            }
        }

        .edit-profile-btn:hover {
            background: linear-gradient(to right, var(--secondary), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .delete-btn {
            background-color: var(--accent);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            width: 100%;
        }

        @media (min-width: 480px) {
            .delete-btn {
                width: auto;
            }
        }

        .delete-btn:hover {
            background-color: #e91e63;
            transform: translateY(-2px);
        }

        /* Profile Sections */
        .profile-section {
            margin-bottom: 25px;
            padding: 20px;
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .profile-section h3 {
            color: var(--accent);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 10px;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }

        /* Activity Items */
        .activity-item {
            margin-bottom: 25px;
            padding: 20px;
            background-color: var(--card-bg);
            border-radius: 10px;
            border-left: 4px solid var(--primary);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
            overflow: hidden;
        }

        .activity-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
            border-left-color: var(--accent);
        }

        .activity-item h4 {
            margin: 0 0 10px 0;
            color: var(--light);
            font-size: 1.2rem;
            font-weight: 600;
        }

        .activity-item p {
            margin: 8px 0;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.7;
        }

        .activity-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 10px;
        }

        .meta-separator {
            color: var(--text-muted);
            opacity: 0.5;
        }

        .activity-date {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-top: 15px;
        }

        .activity-date i {
            font-size: 0.9rem;
        }

        .activity-content {
            padding: 10px 0;
        }

        .comment-content {
            padding-left: 15px;
            border-left: 2px solid var(--secondary);
        }

        /* Community Badges */
        .community-badge {
            display: inline-block;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            margin-right: 8px;
            margin-bottom: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .community-badge:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        /* Responsive adjustments */
        @media (max-width: 767px) {
            .container {
                padding: 10px;
            }
            
            .profile-header,
            .profile-section {
                padding: 15px;
            }
            
            .profile-section h3 {
                font-size: 1.1rem;
            }
        }

        /* No activity message */
        .no-activity {
            text-align: center;
            padding: 30px;
            color: var(--text-muted);
            font-style: italic;
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            border: 1px dashed var(--text-muted);
        }

        .no-activity a {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .no-activity a:hover {
            background: var(--secondary);
        }

        /* Links in activity items */
        .activity-item a {
            color: var(--light);
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .activity-item a:hover {
            color: var(--accent);
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php if ($has_header) include $header_path; ?>

    <div class="container">
        <div class="profile-header">
            <img src="<?= htmlspecialchars($profile_picture_path) ?>" alt="Profile Picture" class="profile-picture" onerror="this.src='<?= WEB_BASE ?>assets/images/avatars/default.png'">
            <div class="profile-info">
                <h2><?= htmlspecialchars($username) ?></h2>
                <p>Email: <?= htmlspecialchars($email) ?></p>
                <button class="edit-profile-btn" onclick="toggleEditForm()">
                    <i class="fas fa-user-edit"></i> Edit Profile
                </button>
            </div>
        </div>

        <div class="edit-profile-form" id="editProfileForm">
            <h3><i class="fas fa-camera"></i> Edit Profile Picture</h3>
            <form action="" method="post" enctype="multipart/form-data">
                <?php if ($has_profile_picture): ?>
                    <div class="current-picture-container">
                        <h4>Current Picture:</h4>
                        <img src="<?= htmlspecialchars($profile_picture_path) ?>" class="current-picture" onerror="this.src='<?= WEB_BASE ?>assets/images/avatars/default.png'">
                    </div>
                <?php else: ?>
                    <div class="no-picture-message">
                        <i class="fas fa-user-circle"></i> Currently using default profile picture
                    </div>
                <?php endif; ?>
                
                <div class="picture-actions">
                    <button type="button" class="edit-profile-btn" onclick="document.getElementById('profile_picture').click()">
                        <i class="fas fa-upload"></i> <?= $has_profile_picture ? 'Change Picture' : 'Upload Picture' ?>
                    </button>
                    <?php if ($has_profile_picture): ?>
                        <button type="submit" name="delete_picture" class="delete-btn">
                            <i class="fas fa-trash-alt"></i> Remove Picture
                        </button>
                    <?php endif; ?>
                </div>
                
                <div class="file-input-container">
                    <label for="profile_picture" class="file-input-label">
                        <i class="fas fa-cloud-upload-alt"></i> Choose File
                    </label>
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="file-input" onchange="updateFileName(this)">
                    <div id="file-name" class="file-name">No file selected</div>
                </div>
                
                <button type="submit" class="edit-profile-btn">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>

        <section class="profile-section">
            <h3><i class="fas fa-users"></i> Your Communities</h3>
            <div class="communities-section">
                <?php
                $stmt = $conn->prepare("
                    SELECT c.id, c.name 
                    FROM communities c
                    JOIN community_members cm ON c.id = cm.community_id
                    WHERE cm.user_id = ?
                    ORDER BY c.name ASC
                ");
                $stmt->bind_param("i", $uid);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo '<a href="' . WEB_BASE . 'communities/community_view.php?id=' . $row['id'] . '" class="community-badge">' . htmlspecialchars($row['name']) . '</a>';
                    }
                } else {
                    echo "<p>You haven't joined any communities yet.</p>";
                }
                $stmt->close();
                ?>
            </div>
        </section>

        <section class="profile-section">
            <h3><i class="fas fa-comments"></i> Your Activity</h3>
            <div class="posts-section">
                <?php
                // Query for user's POSTS
                $posts_stmt = $conn->prepare("
                    SELECT p.id, p.title, p.content, p.created_at, 
                           COALESCE(c.name, 'General') as community_name, 
                           'post' as type
                    FROM posts p
                    LEFT JOIN communities c ON p.community_id = c.id
                    WHERE p.user_id = ?
                    ORDER BY p.created_at DESC
                ");
                $posts_stmt->bind_param("i", $uid);
                $posts_stmt->execute();
                $posts_result = $posts_stmt->get_result();
                
                // Query for user's COMMENTS
                $comments_stmt = $conn->prepare("
                    SELECT cm.id, cm.content, cm.created_at, 
                           p.title as post_title, p.id as post_id, 
                           COALESCE(c.name, 'General') as community_name,
                           'comment' as type
                    FROM comments cm
                    JOIN posts p ON cm.post_id = p.id
                    LEFT JOIN communities c ON p.community_id = c.id
                    WHERE cm.user_id = ?
                    ORDER BY cm.created_at DESC
                ");
                $comments_stmt->bind_param("i", $uid);
                $comments_stmt->execute();
                $comments_result = $comments_stmt->get_result();
                
                // Combine both results into one array
                $activities = [];
                while ($row = $posts_result->fetch_assoc()) {
                    $activities[] = $row;
                }
                while ($row = $comments_result->fetch_assoc()) {
                    $activities[] = $row;
                }
                
                // Sort combined array by date (newest first)
                usort($activities, function($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });
                
                if (count($activities) > 0) {
                    foreach ($activities as $activity) {
                        echo '<div class="activity-item ' . $activity['type'] . '">';
                        
                        if ($activity['type'] === 'post') {
                            // Display post
                            echo '<h4><i class="fas fa-pen"></i> <a href="' . WEB_BASE . 'posts/view_post.php?id=' . $activity['id'] . '">' . htmlspecialchars($activity['title']) . '</a></h4>';
                            echo '<p class="activity-meta"><i class="fas fa-layer-group"></i> ' . htmlspecialchars($activity['community_name']) . ' <span class="meta-separator">|</span> <i class="fas fa-edit"></i> Post</p>';
                            echo '<div class="activity-content">' . nl2br(htmlspecialchars($activity['content'])) . '</div>';
                        } else {
                            // Display comment
                            echo '<h4><i class="fas fa-comment"></i> Comment on: <a href="' . WEB_BASE . 'posts/view_post.php?id=' . $activity['post_id'] . '">' . htmlspecialchars($activity['post_title']) . '</a></h4>';
                            echo '<p class="activity-meta"><i class="fas fa-layer-group"></i> ' . htmlspecialchars($activity['community_name']) . ' <span class="meta-separator">|</span> <i class="fas fa-comment-dots"></i> Comment</p>';
                            echo '<div class="activity-content comment-content">' . nl2br(htmlspecialchars($activity['content'])) . '</div>';
                        }
                        
                        echo '<p class="activity-date"><i class="far fa-clock"></i> ' . date("F j, Y, g:i a", strtotime($activity['created_at'])) . '</p>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="no-activity">';
                    echo '<p>You haven\'t made any posts or comments yet.</p>';
                    echo '<a href="create_post.php" class="btn">Create your first post</a>';
                    echo '</div>';
                }
                
                $posts_stmt->close();
                $comments_stmt->close();
                ?>
            </div>
        </section>
    </div>

    <?php if ($has_footer) include $footer_path; ?>

    <script>
        function toggleEditForm() {
            const form = document.getElementById('editProfileForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
        
        function updateFileName(input) {
            const fileName = input.files[0] ? input.files[0].name : 'No file selected';
            document.getElementById('file-name').textContent = fileName;
        }
        
        // Fallback for broken profile pictures
        document.addEventListener('DOMContentLoaded', function() {
            const profilePics = document.querySelectorAll('.profile-picture, .current-picture');
            profilePics.forEach(img => {
                img.onerror = function() {
                    this.src = '<?= WEB_BASE ?>assets/images/avatars/default.png';
                };
            });
        });
    </script>
</body>
</html>