<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo "Please <a href='../auth/login.php'>login</a>.";
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
