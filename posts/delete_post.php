<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) exit('Please login.');

$pid = $_GET['id'] ?? 0;

$stmt = $conn->prepare("SELECT user_id FROM posts WHERE id=?");
$stmt->bind_param("i", $pid);
$stmt->execute();
$stmt->bind_result($uid);
if (!$stmt->fetch()) {
    $stmt->close();
    header("Location: all.php?error=notfound");
    exit;
}
$stmt->close();

if ($uid !== $_SESSION['user_id']) {
    header("Location: all.php?error=unauthorized");
    exit;
}

$del = $conn->prepare("DELETE FROM posts WHERE id=?");
$del->bind_param("i", $pid);
if ($del->execute()) {
    $del->close();
    header("Location: all.php?msg=deleted");
    exit;
} else {
    $del->close();
    header("Location: all.php?error=deletefail");
    exit;
}
?>
