<?php
require 'db_connection.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email format.';
    }
    // Validate phone number format (just checking for digits and length)
    elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $message = 'Phone number must be 10 digits long.';
    } else {
        // Check if username is already taken
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = 'Username is already taken.';
        } else {
            // Insert new user with email, phone number, and total_money = 0.00
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, MobilePhone, total_money) VALUES (?, ?, ?, ?, ?)");
            $total_money = 0.00; // Default value for total_money
            $stmt->bind_param("ssssd", $username, $hashed_password, $email, $phone, $total_money);

            if ($stmt->execute()) {
                $message = 'Registration successful! Redirecting to login page...';
                echo "<script>
                        setTimeout(function() {
                            window.location.href = 'login.php';
                        }, 2000); // 2-second delay before redirecting
                      </script>";
            } else {
                $message = 'Registration failed. Please try again.';
            }
            
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .message-container {
            text-align: center;
            margin-top: 10px;
        }
        .message {
            display: inline-block;
            padding: 10px;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <header>
            <h1>Register</h1>
            <a href="\account_management\main1\index.html" class="nav-button">Home</a>
        </header>
        <form method="POST">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required>

            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required>

            <label for="phone">Phone:</label>
            <input type="text" name="phone" id="phone" required>

            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>

            <button type="submit">Register</button>

            <p>Already have an account? <a href="login.php">Login here</a>.</p>
        </form>

        <?php if ($message): ?>
            <div class="message-container">
                <p class="message"><?= $message ?></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
