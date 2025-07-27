<?php
// includes/header.php

if (!defined('WEB_BASE')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

// Don't start session here - it should already be started in index.php
function isActive($pageName) {
    return (basename($_SERVER['PHP_SELF']) == $pageName) ? 'active disabled' : '';
}


// Initialize profile picture path
$profile_picture_path = WEB_BASE . 'assets/images/avatars/default.png';

// Only try to get profile picture if user is logged in AND connection exists
if (isset($_SESSION['user_id']) && isset($GLOBALS['conn'])) {
    try {
        $stmt = $GLOBALS['conn']->prepare("SELECT profile_picture FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $stmt->bind_result($profile_picture);
        $stmt->fetch();
        $stmt->close();
        
        if (!empty($profile_picture)) {
            $profile_picture_path = WEB_BASE . "uploads/profile_pics/" . $profile_picture;
        }
    } catch (Exception $e) {
        // Silently fall back to default picture on error
        error_log("Profile picture error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Gamers Nexus'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Orbitron:wght@700;800&family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?= WEB_BASE ?>assets/css/style.css">
</head>
<body>
<header>
  <div class="header-content">
    <div class="header-main">
      <h1>GAMERS NEXUS</h1>
      <div class="tagline">! LEVEL UP YOUR GAMING DISCUSSIONS AND POST IT HERE !</div>
    </div>
    <nav>
      <a href="<?= WEB_BASE ?>index.php" class="<?= isActive('index.php') ?>">
        <i class="fas fa-home"></i> Home
      </a>
      
      <a href="<?= WEB_BASE ?>communities/view_community.php" class="<?= isActive('view_community.php') ?>">
        <i class="fas fa-users"></i> Community
      </a>
      
      <?php if (!isset($_SESSION['user_id'])): ?>
        <a href="<?= WEB_BASE ?>auth/login.php" class="<?= isActive('login.php') ?>">
          <i class="fas fa-sign-in-alt"></i> Login
        </a>
      <?php else: ?>
        <div class="user-dropdown">
          <button class="user-menu-toggle">
            <img src="<?= $profile_picture_path ?>" 
                 alt="Profile" class="user-avatar"
                 onerror="this.src='<?= WEB_BASE ?>assets/images/avatars/default.png'">
            <span class="username-text"><?= htmlspecialchars($_SESSION['username']) ?></span>
            <i class="fas fa-caret-down"></i>
          </button>
          <div class="dropdown-menu">
            <a href="<?= WEB_BASE ?>users/profile.php"><i class="fas fa-user"></i> View Profile</a>
            <a href="<?= WEB_BASE ?>community/joined.php"><i class="fas fa-users"></i> My Communities</a>
            <div class="dropdown-divider"></div>
            <form method="POST" action="<?= WEB_BASE ?>auth/logout.php" class="logout-form">
              <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
              <button type="submit" class="logout-button">
                <i class="fas fa-sign-out-alt"></i> Logout
              </button>
            </form>
          </div>
        </div>
      <?php endif; ?>
    </nav>
  </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const header = document.querySelector('header');
  const scrollThreshold = 100;
  
  function updateHeader() {
    header.classList.toggle('scrolled', window.scrollY > scrollThreshold);
  }
  
  // Add error handling for profile pictures
  document.querySelectorAll('.user-avatar').forEach(img => {
    img.onerror = function() {
      this.src = '<?= WEB_BASE ?>assets/images/avatars/default.png';
    };
  });
});
</script>
</body>
</html>