
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Community</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo "<div class='error'>Please <a href='../auth/login.php'>login</a> to create a community.</div>";
    include '../includes/footer.php';
    echo '</body></html>';
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $uid  = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO communities (name, description, created_by) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $name, $desc, $uid);

    if ($stmt->execute()) {
        echo "<div class='success'>&#x2705; Community created!</div>";
    } else {
        echo "<div class='error'>&#x274c; Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}
?>

<form method="POST">
    <input name="name" required placeholder="Community Name"><br>
    <textarea name="description" placeholder="Description"></textarea><br>
    <button type="submit">Create</button>
</form>
<?php include '../includes/footer.php'; ?>
</body>
</html>
