<?php
session_start();

require 'db_connection.php';



$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Retrieve user data
    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($id, $hashed_password, $role);

    if ($stmt->fetch() && password_verify($password, $hashed_password)) {
        // Set session variables
        $_SESSION['user_id'] = $id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;

        // Redirect to the homepage
        header("Location: index.php");
        exit;
    } else {
        $message = 'Invalid username or password.';
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Login</h1>
        <a href="\account_management\main1\index.html" class="nav-button">Home</a>

    </header>
    <main>
        <form method="POST">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required><br><br>

            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required><br><br>

            <button type="submit">Login</button>
            <p>Don't have an account? <a href="register.php">Register here</a>.</p>

        </form>
        <?php if ($message): ?>
            <p style="color: red;"><?= $message ?></p>
        <?php endif; ?>
    </main>
</body>
</html>
