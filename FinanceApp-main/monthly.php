<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id']; // Get logged-in user ID

// Database connection
require 'db_connection.php';


// Fetch monthly summaries for the logged-in user
$sql = "SELECT DATE_FORMAT(date, '%Y-%m') AS month, 
               SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS income,
               SUM(CASE WHEN type = 'outcome' THEN amount ELSE 0 END) AS outcome
        FROM transactions
        WHERE user_id = ?
        GROUP BY month
        ORDER BY month DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$months = [];
while ($row = $result->fetch_assoc()) {
    $row['total'] = $row['income'] - $row['outcome'];
    $months[] = $row;
}

// Get the sort parameter from the URL (default is 'date')
$valid_sort_columns = ['amount', 'date', 'description', 'type'];
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $valid_sort_columns) ? $_GET['sort'] : 'date';
$order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC'; // Default to descending

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions by Month</title>
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
        <h1>Transactions by Month</h1>
        <a href="index.php" class="nav-button">Back</a>
    </header>
    <main>

        <?php foreach ($months as $month): ?>
        <div class="month-summary">
            <h2>
                <?= $month['month'] ?>: 
                <span class="<?= $month['total'] >= 0 ? 'positive' : 'negative' ?>">
                    <?= $month['total'] >= 0 ? $month['total'] : '-' . abs($month['total']) ?>€
                </span>
            </h2>
            <button class="expand-button" onclick="toggleMonth('<?= $month['month'] ?>')">View Details</button>
            <div class="month-details" id="<?= $month['month'] ?>" style="display: block;">
                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Tags</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sqlDetails = "SELECT t.id, t.description, t.amount, t.type, t.date, 
                                              GROUP_CONCAT(DISTINCT tag.name SEPARATOR ', ') AS tags 
                                       FROM transactions t
                                       LEFT JOIN transaction_tags tt ON t.id = tt.transaction_id
                                       LEFT JOIN tags tag ON tt.tag_id = tag.id
                                       WHERE DATE_FORMAT(t.date, '%Y-%m') = ? AND t.user_id = ?
                                       GROUP BY t.id
                                       ORDER BY 
                                           CASE 
                                               WHEN '$sort' = 'amount' THEN 
                                                   CASE 
                                                       WHEN type = 'outcome' THEN -amount 
                                                       ELSE amount 
                                                   END 
                                               ELSE $sort 
                                           END $order"; // Sorting by the selected parameter and order
                        $stmtDetails = $conn->prepare($sqlDetails);
                        $stmtDetails->bind_param("si", $month['month'], $user_id);
                        $stmtDetails->execute();
                        $resultDetails = $stmtDetails->get_result();
                        while ($row = $resultDetails->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['description']) ?></td>
                            <td class="<?= $row['type'] == 'income' ? 'positive' : 'negative' ?>">
                                <?= $row['type'] == 'outcome' ? '-' : '' ?><?= number_format($row['amount'], 2) ?>€
                            </td>
                            <td><?= date('Y-m-d', strtotime($row['date'])) ?></td>
                            <td>
                                <?php
                                $tags = explode(',', $row['tags']);
                                foreach ($tags as $tag) {
                                    echo "<a href='tagged_transactions.php?tag_name=" . urlencode($tag) . "'>" . htmlspecialchars($tag) . "</a> ";
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>

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
    <script>
        function toggleMonth(month) {
            const details = document.getElementById(month);
            details.style.display = details.style.display === 'none' || !details.style.display ? 'block' : 'none';
        }
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
