<?php 
// includes/header.php
function isActive($pageName) {
    return (basename($_SERVER['PHP_SELF']) == $pageName) ? 'active disabled' : '';
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
      
      <a href="<?= WEB_BASE ?>events.php" class="<?= isActive('events.php') ?>">
        <i class="fas fa-calendar-alt"></i> Events
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
            <img src="<?= WEB_BASE ?>assets/images/avatars/<?= $_SESSION['avatar'] ?? 'default.png' ?>" 
                 alt="Profile" class="user-avatar">
            <span class="username-text"><?= htmlspecialchars($_SESSION['username']) ?></span>
            <i class="fas fa-caret-down"></i>
          </button>
          <div class="dropdown-menu">
            <a href="<?= WEB_BASE ?>profile/view.php"><i class="fas fa-user"></i> View Profile</a>
            <a href="<?= WEB_BASE ?>community/joined.php"><i class="fas fa-users"></i> My Communities</a>
            <a href="<?= WEB_BASE ?>settings/index.php"><i class="fas fa-cog"></i> Settings</a>
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
  
  // Initialize and throttle scroll events
  updateHeader();
  window.addEventListener('scroll', function() {
    window.requestAnimationFrame(updateHeader);
  }, { passive: true });
});
</script>
</body>
</html>
