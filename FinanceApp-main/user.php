<?php
session_start();

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Unauthorized access. Only admins can view this page.";
    exit;
}

// Get the user ID from the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo "Invalid user ID.";
    exit;
}

$user_id = intval($_GET['id']);

// Database connection
require 'db_connection.php';


// Fetch the username of the specified user
$sql_user = "SELECT username FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($result_user->num_rows === 0) {
    echo "User not found.";
    exit;
}

$user = $result_user->fetch_assoc();
$username = htmlspecialchars($user['username']);

// Fetch transactions for the specified user
$sql_transactions = "SELECT id, type, description, amount, date 
                     FROM transactions 
                     WHERE user_id = ? 
                     ORDER BY date DESC";
$stmt_transactions = $conn->prepare($sql_transactions);
$stmt_transactions->bind_param("i", $user_id);
$stmt_transactions->execute();
$result_transactions = $stmt_transactions->get_result();

$transactions = [];
while ($row = $result_transactions->fetch_assoc()) {
    $transactions[] = $row;
}

$stmt_user->close();
$stmt_transactions->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions for <?= $username ?></title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <header>
        <h1>Transactions for <?= $username ?></h1>
        <a href="history.php" class="nav-button">Back to Admin History</a>
    </header>
    <main>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Amount (€)</th>
                    <th>Date</th>
                    <th>Edit</th>

                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="5">No transactions found for this user.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?= $transaction['id'] ?></td>
                            <td><?= ucfirst($transaction['type']) ?></td>
                            <td><?= htmlspecialchars($transaction['description']) ?></td>
                            <td class="<?= $transaction['type'] === 'income' ? 'positive' : 'negative' ?>">
                                <?= $transaction['type'] === 'outcome' ? '-' : '' ?><?= number_format($transaction['amount'], 2) ?>€
                            </td>
                            <td><?= date('Y-m-d', strtotime($transaction['date'])) ?></td>
                            <td>
                                <a href="edit_transaction.php?id=<?= $transaction['id'] ?>" class="edit-button">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>

        </table>
    </main>
</body>

</html>