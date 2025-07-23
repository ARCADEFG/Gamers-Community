<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) exit('Please login.');

$pid = $_GET['id'] ?? 0;

$stmt = $conn->prepare("SELECT user_id FROM posts WHERE id=?");
$stmt->bind_param("i", $pid);
$stmt->execute();
$stmt->bind_result($uid);
if (!$stmt->fetch()) exit('Post not found.');
$stmt->close();

if ($uid !== $_SESSION['user_id']) exit('Unauthorized.');

$conn->query("DELETE FROM posts WHERE id=$pid");
echo "✅ Post deleted.";
?>
