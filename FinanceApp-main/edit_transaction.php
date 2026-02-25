<?php
session_start();

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Unauthorized access. Only admins can perform this action.";
    exit;
}

// Get the transaction ID from the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo "Invalid transaction ID.";
    exit;
}

$transaction_id = intval($_GET['id']);

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

// Fetch the transaction data along with the user_id
$sql = "SELECT t.id, t.type, t.description, t.amount, t.date, t.user_id, u.username 
        FROM transactions t
        INNER JOIN users u ON t.user_id = u.id
        WHERE t.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Transaction not found.";
    exit;
}

$transaction = $result->fetch_assoc();
$user_id = $transaction['user_id']; // Retrieve the user_id for the back button
$description = htmlspecialchars($transaction['description']);
$amount = number_format($transaction['amount'], 2, '.', ''); // Proper format for pre-filling
$date = date('Y-m-d', strtotime($transaction['date'])); // Proper format for date input
$type = $transaction['type'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_description = $_POST['description'];
    $new_amount = $_POST['amount'];
    $new_date = $_POST['date'];
    $new_type = $_POST['type'];

    // Validate input
    if (empty($new_description) || !is_numeric($new_amount) || empty($new_date) || !in_array($new_type, ['income', 'outcome'])) {
        $error = "All fields are required, and the amount must be a valid number.";
    } else {
        // Update the transaction in the database
        $update_sql = "UPDATE transactions 
                       SET description = ?, amount = ?, date = ?, type = ? 
                       WHERE id = ?";
        $stmt_update = $conn->prepare($update_sql);
        $stmt_update->bind_param("sdssi", $new_description, $new_amount, $new_date, $new_type, $transaction_id);

        if ($stmt_update->execute()) {
            $success = "Transaction updated successfully!";
            // Update the values to reflect the changes
            $description = htmlspecialchars($new_description);
            $amount = number_format($new_amount, 2, '.', '');
            $date = $new_date;
            $type = $new_type;
        } else {
            $error = "Failed to update the transaction.";
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
    <title>Edit Transaction</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Edit Transaction</h1>
        <a href="user.php?id=<?= $user_id ?>" class="nav-button">Back to User Transactions</a>
    </header>
    <main>
        <?php if (isset($error)): ?>
            <p style="color: red;"><?= $error ?></p>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <p style="color: green;"><?= $success ?></p>
        <?php endif; ?>
        <form method="POST">
            <label for="type">Type:</label>
            <select name="type" id="type" required>
                <option value="income" <?= $type === 'income' ? 'selected' : '' ?>>Income</option>
                <option value="outcome" <?= $type === 'outcome' ? 'selected' : '' ?>>Outcome</option>
            </select><br><br>

            <label for="description">Description:</label>
            <input type="text" name="description" id="description" value="<?= $description ?>" required><br><br>

            <label for="amount">Amount (€):</label>
            <input type="number" name="amount" id="amount" step="0.01" value="<?= $amount ?>" required><br><br>

            <label for="date">Date:</label>
            <input type="date" name="date" id="date" value="<?= $date ?>" required><br><br>

            <button type="submit">Update Transaction</button>
        </form>
    </main>
</body>
</html>
