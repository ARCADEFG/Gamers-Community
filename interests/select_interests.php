
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Interests</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo "<div class='error'>Please <a href='../auth/login.php'>login</a> to continue.</div>";
    include '../includes/footer.php';
    echo '</body></html>';
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

    echo "<div class='success'>&#x2705; Interests saved!</div>";
}
?>

<h2>Select Your Favorite Game Genres</h2>
<form method="POST">
    <?php foreach ($genres as $genre): ?>
        <label><input type="checkbox" name="genres[]" value="<?= $genre ?>"> <?= $genre ?></label><br>
    <?php endforeach; ?>
    <button type="submit">Save Interests</button>
</form>
<?php include '../includes/footer.php'; ?>
</body>
</html>
