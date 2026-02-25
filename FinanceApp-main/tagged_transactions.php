<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id']; // Get logged-in user ID

// Database connection
require 'db_connection.php';

// Get the tag name from the query parameter
if (!isset($_GET['tag_name']) || empty($_GET['tag_name'])) {
    echo "Invalid tag specified.";
    exit;
}
$tag_name = $_GET['tag_name'];

// Fetch transactions for the specific tag and user
$sql = "SELECT t.id, t.type, t.description, t.amount, t.date
        FROM transactions t
        INNER JOIN transaction_tags tt ON t.id = tt.transaction_id
        INNER JOIN tags tag ON tt.tag_id = tag.id
        WHERE tag.name = ? AND t.user_id = ?
        ORDER BY t.date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $tag_name, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$transactions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions for Tag: <?= htmlspecialchars($tag_name) ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Transactions for Tag: <?= htmlspecialchars($tag_name) ?></h1>
        <a href="monthly.php" class="nav-button">Back to Monthly View</a>
    </header>
    <main>
        <?php if (empty($transactions)): ?>
            <p>No transactions found for this tag.</p>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                <tr>
                    <td><?= $transaction['type'] ?></td>
                    <td><?= $transaction['description'] ?></td>
                    <td class="<?= $transaction['type'] == 'income' ? 'positive' : 'negative' ?>">
                        <?= $transaction['type'] == 'outcome' ? '-' : '' ?><?= $transaction['amount'] ?>€
                    </td>
                    <td><?= date('Y-m-d', strtotime($transaction['date'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </main>
</body>
</html>