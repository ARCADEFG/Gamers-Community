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
        <!-- Your page content here -->
        <h2>Welcome to the Gamers Community</h2>
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
