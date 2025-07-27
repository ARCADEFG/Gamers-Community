<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo "Please <a href='../auth/login.php'>login</a> to continue.";
    exit;
}

$genres = ["RPG", "FPS", "MOBA", "MMO", "Strategy", "Simulation", "Casual", "Horror", "Adventure", "Sports"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $uid = $_SESSION['user_id'];
    $selected = $_POST['genres'] ?? [];

    // Remove old interests
    $conn->query("DELETE FROM user_interests WHERE user_id = $uid");

    $stmt = $conn->prepare("INSERT INTO user_interests (user_id, genre) VALUES (?, ?)");
    foreach ($selected as $genre) {
        $stmt->bind_param("is", $uid, $genre);
        $stmt->execute();
    }
    $stmt->close();

    echo "✅ Interests saved!";
}
?>

<h2>Select Your Favorite Game Genres</h2>
<form method="POST">
    <?php foreach ($genres as $genre): ?>
        <label><input type="checkbox" name="genres[]" value="<?= $genre ?>"> <?= $genre ?></label><br>
    <?php endforeach; ?>
    <button type="submit">Save Interests</button>
</form>
