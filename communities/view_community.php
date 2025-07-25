echo "<h2>Community: $name</h2><p>$desc</p><hr>";
echo "<h3>Posts</h3>";

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Community</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<?php
session_start();
include '../config/db.php';

if (!isset($_GET['id'])) {
    echo "<div class='error'>&#x274c; Community ID missing.</div>";
    include '../includes/footer.php';
    echo '</body></html>';
    exit;
}

$cid = $_GET['id'];

// Get community info
$stmt = $conn->prepare("SELECT name, description FROM communities WHERE id = ?");
$stmt->bind_param("i", $cid);
$stmt->execute();
$stmt->bind_result($name, $desc);
$stmt->fetch();
$stmt->close();

echo "<h2>Community: $name</h2><p>$desc</p><hr>";

// Show posts in this community
$stmt = $conn->prepare("SELECT posts.id, title, content, username FROM posts JOIN users ON users.id = posts.user_id WHERE community_id = ? ORDER BY posts.created_at DESC");
$stmt->bind_param("i", $cid);
$stmt->execute();
$result = $stmt->get_result();

echo "<h3>Posts</h3>";
while ($row = $result->fetch_assoc()) {
    echo "<b>{$row['title']}</b> by {$row['username']}<br>";
    echo "<p>{$row['content']}</p><hr>";
}
$stmt->close();
?>
<?php include '../includes/footer.php'; ?>
</body>
</html>
