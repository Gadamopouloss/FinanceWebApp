<?php
session_start();

// Redirect if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['role'] === 'admin');

// Database connection
require 'db_connection.php';


// Get the sort parameter from the URL (default is 'date')
$valid_sort_columns = ['amount', 'date', 'type'];
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $valid_sort_columns) ? $_GET['sort'] : 'date';
$order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC'; // Default to descending

// Fetch all transactions for admins or only the user's transactions for regular users
if ($is_admin) {
    $sql = "SELECT t.id, t.type, t.description, t.amount, t.date, u.username, 
                   GROUP_CONCAT(tag.name SEPARATOR ', ') AS tags
            FROM transactions t
            LEFT JOIN transaction_tags tt ON t.id = tt.transaction_id
            LEFT JOIN tags tag ON tt.tag_id = tag.id
            LEFT JOIN users u ON t.user_id = u.id
            GROUP BY t.id
            ORDER BY $sort $order";
    $result = $conn->query($sql);

    // Fetch all users for the admin
    $sqlUsers = "SELECT id, username FROM users";
    $resultUsers = $conn->query($sqlUsers);
} else {
    $sql = "SELECT t.id, t.type, t.description, t.amount, t.date, 
                   GROUP_CONCAT(tag.name SEPARATOR ', ') AS tags
            FROM transactions t
            LEFT JOIN transaction_tags tt ON t.id = tt.transaction_id
            LEFT JOIN tags tag ON tt.tag_id = tag.id
            WHERE t.user_id = ?
            GROUP BY t.id
            ORDER BY $sort $order";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History</title>
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
        <h1>Transaction History</h1>
        <a href="index.php" class="nav-button">Back</a>

    </header>
    <main>
        <!-- Transaction Table -->
        <table>
            <thead>
                <tr>
                    <?php if ($is_admin): ?>
                        <th>User</th>
                    <?php endif; ?>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Amount (€)</th>
                    <th>Date</th>
                    <th>Tags</th>
                    <th>Edit</th>
                </tr>
            </thead>
            <tbody id="transaction-table">
                <?php if ($result->num_rows === 0): ?>
                    <tr>
                        <td colspan="7">No transactions found.</td>
                    </tr>
                <?php else: ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <?php if ($is_admin): ?>
                                <td><?= htmlspecialchars($row['username']) ?></td>
                            <?php endif; ?>
                            <td><?= ucfirst($row['type']) ?></td>
                            <td><?= htmlspecialchars($row['description']) ?></td>
                            <td class="<?= $row['type'] === 'income' ? 'positive' : 'negative' ?>">
                                <?= $row['type'] === 'outcome' ? '-' : '' ?><?= number_format($row['amount'], 2) ?>€
                            </td>
                            <td><?= date('Y-m-d', strtotime($row['date'])) ?></td>
                            <td><?= htmlspecialchars($row['tags']) ?></td>
                            <td>
                                <?php if ($is_admin): ?>
                                    <a href="edit_transaction.php?id=<?= $row['id'] ?>" class="edit-button">Edit</a>
                                <?php else: ?>
                                    <a href="edit_description.php?id=<?= $row['id'] ?>" class="edit-button">Edit</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($is_admin): ?>
            <h3>Users</h3>
            <ul id="user-list">
                <?php while ($user = $resultUsers->fetch_assoc()): ?>
                    <li>
                        <a href="user.php?id=<?= $user['id'] ?>" class="username-link">
                            <?= htmlspecialchars($user['username']) ?>
                        </a>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php endif; ?>

        <!-- Sorting Form -->
        <form method="GET" action="" class="sort-form">
            <label for="sort">Sort by:</label>
            <select name="sort" id="sort">
                <option value="date" <?= $sort === 'date' ? 'selected' : '' ?>>Date</option>
                <option value="amount" <?= $sort === 'amount' ? 'selected' : '' ?>>Amount</option>
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
if (!$is_admin) {
    $stmt->close();
}
$conn->close();
?>
