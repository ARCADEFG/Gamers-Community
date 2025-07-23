<?php
include '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone    = $_POST['phone'];
    $verify   = bin2hex(random_bytes(16));

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, phone, verification_code) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $email, $password, $phone, $verify);

    if ($stmt->execute()) {
        echo "✅ Registration successful. Check your email for verification (simulate for now).";
        // Simulate redirect: header("Location: login.php");
    } else {
        echo "❌ Error: " . $stmt->error;
    }
    $stmt->close();
}
?>

<form method="POST">
    <input name="username" required placeholder="Username"><br>
    <input name="email" type="email" required placeholder="Email"><br>
    <input name="password" type="password" required placeholder="Password"><br>
    <input name="phone" placeholder="Phone"><br>
    <button type="submit">Register</button>
</form>
