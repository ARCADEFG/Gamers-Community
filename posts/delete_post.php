<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) exit('Please login.');

$pid = $_GET['id'] ?? 0;

try {
    $stmt = $conn->prepare("SELECT user_id FROM posts WHERE id=?");
    if ($stmt === false) {
        throw new mysqli_sql_exception('Failed to prepare statement for user ID check.');
    }
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $stmt->bind_result($uid);

    if (!$stmt->fetch()) {
        $stmt->close();
        header("Location: all.php?error=postnotfound");
        exit;
    }
    $stmt->close();

    if ($uid !== $_SESSION['user_id']) {
        header("Location: all.php?error=unauthorized");
        exit;
    }

    $del = $conn->prepare("DELETE FROM posts WHERE id=?");
    if ($del === false) {
        throw new mysqli_sql_exception('Failed to prepare statement for deletion.');
    }
    $del->bind_param("i", $pid);
    if ($del->execute()) {
        $del->close();
        header("Location: all.php?msg=deleted");
        exit;
    } else {
        // This else block will now likely only be reached for non-exception errors
        header("Location: all.php?error=deletefail");
        exit;
    }
} catch (mysqli_sql_exception $e) {
    error_log("Database error in delete_post.php: " . $e->getMessage());
    header("Location: all.php?error=db_error&message=" . urlencode("A database error occurred during deletion."));
    exit;
}
?>
