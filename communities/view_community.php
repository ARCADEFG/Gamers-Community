<?php
session_start();
include '../config/db.php';

// Define base paths
define('FS_BASE', dirname(__DIR__)); // Filesystem base
define('WEB_BASE', '/Gamers_Community/'); // Web base

// Check if header/footer files exist
$header_path = FS_BASE . '/includes/header.php';
$footer_path = FS_BASE . '/includes/footer.php';
$has_header = file_exists($header_path);
$has_footer = file_exists($footer_path);

// Handle join/leave community action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['community_id'])) {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'You need to be logged in to join communities']);
        exit;
    }

    $community_id = (int)$_POST['community_id'];
    $user_id = (int)$_SESSION['user_id'];
    $action = $_POST['action'];

    try {
        if ($action === 'join') {
            // Check if already a member
            $check_stmt = $conn->prepare("SELECT id FROM community_members WHERE community_id = ? AND user_id = ?");
            $check_stmt->bind_param("ii", $community_id, $user_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows === 0) {
                $insert_stmt = $conn->prepare("INSERT INTO community_members (community_id, user_id) VALUES (?, ?)");
                $insert_stmt->bind_param("ii", $community_id, $user_id);
                $insert_stmt->execute();
            }
        } elseif ($action === 'leave') {
            $delete_stmt = $conn->prepare("DELETE FROM community_members WHERE community_id = ? AND user_id = ?");
            $delete_stmt->bind_param("ii", $community_id, $user_id);
            $delete_stmt->execute();
        }

        // Get updated member count
        $count_stmt = $conn->prepare("SELECT COUNT(id) as count FROM community_members WHERE community_id = ?");
        $count_stmt->bind_param("i", $community_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count = $count_result->fetch_assoc()['count'];

        echo json_encode([
            'status' => 'success', 
            'count' => $count, 
            'action' => $action,
            'new_button_text' => $action === 'join' ? 'Joined' : 'Join Community'
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// Get all communities from database with user's join status
$communities = [];
$user_id = $_SESSION['user_id'] ?? null;

try {
    $query = "SELECT 
                c.id, 
                c.name, 
                c.description, 
                c.created_at, 
                u.username as creator_name, 
                COUNT(cm.id) as member_count, 
                SUM(CASE WHEN cm.user_id = ? THEN 1 ELSE 0 END) as is_member
              FROM communities c 
              LEFT JOIN users u ON c.created_by = u.id 
              LEFT JOIN community_members cm ON c.id = cm.community_id 
              GROUP BY c.id 
              ORDER BY c.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $communities = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexus | Browse Communities</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;800&family=Montserrat:wght@400;500;600;700&family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= WEB_BASE ?>assets/css/style.css">
    <style>
        :root {
            --primary: #6a11cb;
            --secondary: #2575fc;
            --accent: #ff4081;
            --light: #f4f4f4;
            --dark: #121212;
            --darker: #0a1121;
        }

        .community-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        @media (max-width: 900px) {
            .community-container {
                grid-template-columns: 1fr;
            }
        }

        .community-list {
            display: grid;
            gap: 1.5rem;
        }

        .community-card {
            background: rgba(30, 30, 46, 0.8);
            border-radius: 12px;
            padding: 1.5rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(106, 17, 203, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .community-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(106, 17, 203, 0.3);
        }

        .community-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid rgba(106, 17, 203, 0.3);
        }

        .community-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.5rem;
            color: var(--accent);
            margin: 0;
        }

        .community-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
            color: #bbb;
        }

        .community-description {
            color: #ddd;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .community-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-join {
            padding: 0.6rem 1.2rem;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 120px;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .btn-joined {
            background: linear-gradient(45deg, #4CAF50, #8BC34A);
        }

        .btn-join:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(106, 17, 203, 0.4);
        }

        .create-community-sidebar {
            background: rgba(30, 30, 46, 0.8);
            border-radius: 12px;
            padding: 1.5rem;
            height: fit-content;
            position: sticky;
            top: 1rem;
        }

        .sidebar-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.5rem;
            color: var(--accent);
            margin-bottom: 1rem;
        }

        .sidebar-description {
            color: #bbb;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .btn-create {
            display: block;
            width: 100%;
            padding: 0.8rem;
            text-align: center;
            background: linear-gradient(45deg, var(--accent), #ff6b9e);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-create:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 64, 129, 0.4);
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #bbb;
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(106, 17, 203, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(106, 17, 203, 0); }
            100% { box-shadow: 0 0 0 0 rgba(106, 17, 203, 0); }
        }
    </style>
</head>
<body>
    <?php if ($has_header) include $header_path; ?>

    <div class="community-container">
        <div class="community-list">
    <?php if (empty($communities)): ?>
        <div class="empty-state">
            <h3>No communities found</h3>
            <p>Be the first to create a community!</p>
        </div>
    <?php else: ?>
        <?php foreach ($communities as $community): ?>
            <div class="community-card" onclick="window.location.href='<?= WEB_BASE ?>communities/community_view.php?id=<?= $community['id'] ?>'">
                <div class="community-header">
                    <h3 class="community-title"><?= htmlspecialchars($community['name']) ?></h3>
                    <div class="community-meta">
                        <span><i class="fas fa-users"></i> <span class="member-count"><?= $community['member_count'] ?></span></span>
                        <span><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($community['created_at'])) ?></span>
                    </div>
                </div>
                <p class="community-description"><?= htmlspecialchars($community['description']) ?></p>
                <div class="community-actions">
                    <span>Created by <?= htmlspecialchars($community['creator_name']) ?></span>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <button class="btn-join <?= $community['is_member'] ? 'btn-joined' : '' ?> pulse"
                                data-community-id="<?= $community['id'] ?>"
                                data-action="<?= $community['is_member'] ? 'leave' : 'join' ?>"
                                onclick="event.stopPropagation()">
                            <i class="fas <?= $community['is_member'] ? 'fa-check' : 'fa-plus' ?>"></i>
                            <?= $community['is_member'] ? 'Joined' : 'Join Community' ?>
                        </button>
                    <?php else: ?>
                        <button class="btn-join pulse" onclick="event.stopPropagation(); showLoginPrompt();">
                            <i class="fas fa-plus"></i> Join Community
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

        <div class="create-community-sidebar">
            <h3 class="sidebar-title">Create Your Own</h3>
            <p class="sidebar-description">
                Build a space for gamers who share your passion. Create a community around your favorite games, playstyles, or interests.
            </p>
            <a href="<?= isset($_SESSION['user_id']) ? WEB_BASE . 'communities/create_community.php' : '#' ?>" 
               class="btn-create pulse" 
               id="createCommunityBtn">
               <i class="fas fa-plus"></i> Create Community
            </a>
        </div>
    </div>

    <?php if ($has_footer) include $footer_path; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Create community button logic
            const createBtn = document.getElementById('createCommunityBtn');
            
            <?php if (!isset($_SESSION['user_id'])): ?>
                createBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    showLoginPrompt();
                });
            <?php endif; ?>

            // Join community buttons
            const joinButtons = document.querySelectorAll('.btn-join[data-community-id]');
            
            joinButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const communityId = this.dataset.communityId;
                    const action = this.dataset.action;
                    
                    if (!communityId) return;
                    
                    // Show loading state
                    const originalHTML = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    this.disabled = true;
                    
                    // Send AJAX request
                    fetch('<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=${action}&community_id=${communityId}`
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            // Update button state
                            const newAction = action === 'join' ? 'leave' : 'join';
                            this.dataset.action = newAction;
                            this.innerHTML = `<i class="fas ${newAction === 'join' ? 'fa-plus' : 'fa-check'}"></i> ${data.new_button_text}`;
                            this.classList.toggle('btn-joined');
                            
                            // Update member count
                            const memberCount = this.closest('.community-card').querySelector('.member-count');
                            if (memberCount) {
                                memberCount.textContent = data.count;
                            }
                        } else {
                            alert(data.message || 'An error occurred');
                            this.innerHTML = originalHTML;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while processing your request');
                        this.innerHTML = originalHTML;
                    })
                    .finally(() => {
                        this.disabled = false;
                    });
                });
            });

            // Animate community cards on load
            const cards = document.querySelectorAll('.community-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });
        });
        
        function showLoginPrompt() {
            if (confirm('You need to be logged in to perform this action. Would you like to login or register?')) {
                window.location.href = '<?= WEB_BASE ?>auth/login.php?return=' + encodeURIComponent(window.location.pathname);
            }
        }
    </script>
</body>
</html>