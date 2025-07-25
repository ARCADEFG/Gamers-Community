echo "<h2>Your Profile</h2>";
echo "<p>Username: $username</p>";
echo "<p>Email: $email</p>";
echo "<p>Phone: $phone</p>";
echo "<hr><h3>Your Posts</h3>";

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Profile</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo "<div class='error'>Please <a href='../auth/login.php'>login</a>.</div>";
    include '../includes/footer.php';
    echo '</body></html>';
    exit;
}

$uid = $_SESSION['user_id'];

// Get user info
$stmt = $conn->prepare("SELECT username, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$stmt->bind_result($username, $email, $phone);
$stmt->fetch();
$stmt->close();

echo "<h2>Your Profile</h2>";
echo "<p>Username: $username</p>";
echo "<p>Email: $email</p>";
echo "<p>Phone: $phone</p>";
echo "<hr><h3>Your Posts</h3>";

// Get user's posts
$stmt = $conn->prepare("SELECT title, content FROM posts WHERE user_id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    echo "<b>{$row['title']}</b><br><p>{$row['content']}</p><hr>";
}
$stmt->close();
?>
<?php include '../includes/footer.php'; ?>
</body>
</html>
