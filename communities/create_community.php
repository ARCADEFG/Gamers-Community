<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo "Please <a href='../auth/login.php'>login</a> to create a community.";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $uid  = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO communities (name, description, created_by) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $name, $desc, $uid);

    if ($stmt->execute()) {
        echo "✅ Community created!";
    } else {
        echo "❌ Error: " . $stmt->error;
    }
    $stmt->close();
}
?>

<form method="POST">
    <input name="name" required placeholder="Community Name"><br>
    <textarea name="description" placeholder="Description"></textarea><br>
    <button type="submit">Create</button>
</form>
