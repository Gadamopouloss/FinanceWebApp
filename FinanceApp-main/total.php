<?php
session_start();

// Redirect if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

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


// Get the sort parameter from the URL (default is 'date')
$valid_sort_columns = ['amount', 'date', 'description', 'type'];
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $valid_sort_columns) ? $_GET['sort'] : 'date';
$order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC'; // Default to descending

// Fetch the last 10 transactions for the logged-in user with sorting
$sqlLast10 = "SELECT * FROM transactions 
              WHERE user_id = ? 
              ORDER BY 
                  CASE 
                      WHEN '$sort' = 'amount' THEN 
                          CASE 
                              WHEN type = 'outcome' THEN -amount 
                              ELSE amount 
                          END 
                      ELSE $sort 
                  END $order
              LIMIT 10";
$stmtLast10 = $conn->prepare($sqlLast10);
$stmtLast10->bind_param("i", $user_id);
$stmtLast10->execute();
$resultLast10 = $stmtLast10->get_result();
$transactions = $resultLast10->fetch_all(MYSQLI_ASSOC);

// Fetch total balance for the logged-in user
$sqlBalance = "SELECT SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) AS balance 
               FROM transactions 
               WHERE user_id = ?";
$stmtBalance = $conn->prepare($sqlBalance);
$stmtBalance->bind_param("i", $user_id);
$stmtBalance->execute();
$resultBalance = $stmtBalance->get_result();
$balance = $resultBalance->fetch_assoc()['balance'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Total Balance</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .sort-button {
            padding: 0.3rem 0.5rem;
            margin: 0.3rem;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8rem;
        }

        .sort-button:hover {
            background-color: #45a049;
        }

        .sort-form select {
            padding: 0.2rem;
            margin-right: 0.5rem;
            font-size: 0.8rem;
        }

        .sort-form {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            margin-top: 1rem;
        }

        .sort-form label {
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <header>
        <h1>Total Balance</h1>
        <a href="index.php" class="nav-button">Back</a>

    </header>
    <main>
        <h2>
            Total Balance: 
            <span class="<?= $balance >= 0 ? 'positive' : 'negative' ?>">
            <?= $balance ?>€
            </span>
        </h2>
        <h3>Last 10 Transactions</h3>
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Amount (€)</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                <tr>
                    <td><?= ucfirst($transaction['type']) ?></td>
                    <td><?= htmlspecialchars($transaction['description']) ?></td>
                    <td class="<?= $transaction['type'] === 'income' ? 'positive' : 'negative' ?>">
                        <?= $transaction['type'] === 'outcome' ? '-' : '' ?><?= number_format($transaction['amount'], 2) ?>€
                    </td>
                    <td><?= date('Y-m-d', strtotime($transaction['date'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Sorting Form -->
        <form method="GET" action="" class="sort-form">
            <label for="sort">Sort by:</label>
            <select name="sort" id="sort">
                <option value="date" <?= $sort === 'date' ? 'selected' : '' ?>>Date</option>
                <option value="amount" <?= $sort === 'amount' ? 'selected' : '' ?>>Amount</option>
                <option value="description" <?= $sort === 'description' ? 'selected' : '' ?>>Description</option>
                <option value="type" <?= $sort === 'type' ? 'selected' : '' ?>>Type</option>
            </select>
            <label for="order">Order:</label>
            <select name="order" id="order">
                <option value="asc" <?= $order === 'ASC' ? 'selected' : '' ?>>Ascending</option>
                <option value="desc" <?= $order === 'DESC' ? 'selected' : '' ?>>Descending</option>
            </select>
            <button type="submit" class="sort-button">Sort</button>
        </form>
    </main>
    
</body>
</html>
<?php
$stmtLast10->close();
$stmtBalance->close();
$conn->close();
?>
