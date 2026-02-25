<?php
session_start();

// Redirect if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'db_connection.php';

// Retrieve user information from the session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch tags for the dropdown
$stmt = $conn->prepare("SELECT id, name FROM tags");
$stmt->execute();
$result = $stmt->get_result();
$tags = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get today's date for the date picker restriction
$today_date = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Management</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 10px; 
            font-size: 1em; 
            background-color: #4CAF50;
        }

        .nav-button { 
            color: white; 
            text-decoration: none; 
            margin-left: 10px; 
            background-color: #388E3C; 
            padding: 5px 10px; 
            border-radius: 5px; 
            transition: background-color 0.3s ease;
        }

        .nav-button:hover {
            background-color: #2E7D32;
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #4CAF50;
            min-width: 160px;
            box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
            z-index: 1;
        }

        .dropdown-content a {
            color: white;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }

        .dropdown-content a:hover {
            background-color: #388E3C;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        #form-section {
            max-height: auto; /* Adjusts height of the form section */
            overflow-y: auto; /* Adds scroll if content overflows */
        }
    </style>
</head>
<body>

<header>
    <h1>Welcome, <?= htmlspecialchars($username) ?></h1>
    <nav>
    <a href="\account_management\main1\index.html"   class="nav-button">Home</a>

        <a href="total.php" class="nav-button">Total Balance</a>
        <div class="dropdown">
            <a href="#" class="nav-button">Features</a>
            <div class="dropdown-content">
                <a href="send_money.php">Transfer</a>
                <a href="monthly.php">Transactions by Month</a>
                <a href="yearly.php">Transactions by Year</a>
                <a href="charts.php">Charts</a>
                <a href="history.php">History</a>
                <a href="goal.php">Goal</a>
            </div>
        </div>
        <a href="logout.php" class="nav-button">Logout</a>
    </nav>
</header>

<main id="content">
    <section id="form-section">
        <h2>Add a Transaction</h2>
        <form id="transaction-form" method="POST" action="submit.php">
            <label for="type">Type:</label>
            <select name="type" id="type" required>
                <option value="income">Income</option>
                <option value="outcome">Outcome</option>
            </select><br><br>

            <label for="description">Description:</label>
            <input type="text" name="description" id="description" required><br><br>

            <label for="amount">Amount (€):</label>
            <input type="number" step="0.01" name="amount" id="amount" required><br><br>

            <label for="date">Date:</label>
            <input type="date" name="date" id="date" required max="<?= $today_date ?>"><br><br>

            <label for="tags">Tags:</label>
            <select name="tags[]" id="tags" multiple>
                <?php foreach ($tags as $tag): ?>
                    <option value="<?= $tag['id'] ?>"><?= htmlspecialchars($tag['name']) ?></option>
                <?php endforeach; ?>
            </select><br><br>

            <button type="submit">Submit</button>
        </form>
        <div id="message" style="margin-top: 1rem; font-weight: bold;"></div>
    </section>
</main>

<script>
    document.getElementById('transaction-form').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission behavior

        const formData = new FormData(this);
        const messageDiv = document.getElementById('message');

        fetch('submit.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                messageDiv.style.color = 'green';
                messageDiv.textContent = data.message;
                this.reset();
            } else {
                messageDiv.style.color = 'red';
                messageDiv.textContent = data.message;
            }
        })
        .catch(error => {
            messageDiv.style.color = 'red';
            messageDiv.textContent = 'An unexpected error occurred. Please try again.';
            console.error('Error:', error);
        });
    });
</script>
</body>
</html>
