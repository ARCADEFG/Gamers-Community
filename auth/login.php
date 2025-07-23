<?php
session_start();
session_destroy();
include '../config/db.php';
header("Location: ../index.php");
exit;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $username, $hashed);
        $stmt->fetch();
        if (password_verify($password, $hashed)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            echo "✅ Logged in successfully. <a href='../index.php'>Go to Home</a>";
        } else {
            echo "❌ Invalid password.";
        }
    } else {
        echo "❌ No user found.";
    }
    $stmt->close();
}
?>

<form method="POST">
    <input name="email" type="email" required placeholder="Email"><br>
    <input name="password" type="password" required placeholder="Password"><br>
    <button type="submit">Login</button>
</form>
