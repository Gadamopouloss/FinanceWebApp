<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo "Unauthorized access.";
    exit;
}

// Get the transaction ID from the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo "Invalid transaction ID.";
    exit;
}

$transaction_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'account_management';
$port = '3306';

$conn = new mysqli($host, $user, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch the transaction to ensure it belongs to the logged-in user
$sql = "SELECT id, description FROM transactions WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $transaction_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Transaction not found or access denied.";
    exit;
}

$transaction = $result->fetch_assoc();
$description = htmlspecialchars($transaction['description']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_description = $_POST['description'];

    // Validate input
    if (empty($new_description)) {
        $error = "Description cannot be empty.";
    } else {
        // Update the description in the database
        $update_sql = "UPDATE transactions SET description = ? WHERE id = ?";
        $stmt_update = $conn->prepare($update_sql);
        $stmt_update->bind_param("si", $new_description, $transaction_id);

        if ($stmt_update->execute()) {
            $success = "Description updated successfully!";
            $description = htmlspecialchars($new_description);
        } else {
            $error = "Failed to update the description.";
        }

        $stmt_update->close();
    }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Description</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Edit Transaction Description</h1>
        <a href="history.php" class="nav-button">Back to History</a>
    </header>
    <main>
        <?php if (isset($error)): ?>
            <p style="color: red;"><?= $error ?></p>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <p style="color: green;"><?= $success ?></p>
        <?php endif; ?>
        <form method="POST">
            <label for="description">Description:</label>
            <input type="text" name="description" id="description" value="<?= $description ?>" required><br><br>

            <button type="submit">Update Description</button>
        </form>
    </main>
</body>
</html>
