<!DOCTYPE html>
<html lang="en">
<footer>
    <div class="footer-container">
        <div class="footer-logo">🎮 Gamers Community</div>
        <div class="footer-links">
            <a href="about.php">About</a>
            <a href="contact.php">Contact</a>
            <a href="terms.php">Terms of Service</a>
            <a href="privacy.php">Privacy Policy</a>
        </div>
        <div class="footer-credit">
            &copy; <?php echo date("Y"); ?> Gamers Community. All rights reserved.
        </div>
    </div>
</footer>
<script>
// Wait until all DOM content is loaded
document.addEventListener('DOMContentLoaded', function() {
  const header = document.querySelector('header');
  
  if (header) { // Extra safety check
    window.addEventListener('scroll', function() {
      header.classList.toggle('scrolled', window.scrollY > 100);
    }, { passive: true });
  }
});
</script>
</html>
