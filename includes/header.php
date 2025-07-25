<?php // includes/header.php ?>
<header>
  <div class="header-content">
    <h1>GAMERS NEXUS</h1>
    <div class="tagline">! LEVEL UP YOUR GAMMING DISCUSSIONS AND POST IT HERE !</div>
    <nav>
      <a href="/index.php"><i class="fas fa-home"></i> Home</a>
      <a href="/posts/all.php"><i class="fas fa-list"></i> Posts</a>
      <?php if (session_status() === PHP_SESSION_NONE) { session_start(); } if (isset($_SESSION['user_id'])): ?>
        <a href="/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
      <?php else: ?>
        <a href="/auth/register.php"><i class="fas fa-user-plus"></i> Register</a>
        <a href="/auth/login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
