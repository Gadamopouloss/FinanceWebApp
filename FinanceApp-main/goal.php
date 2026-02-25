<?php
session_start();

// Redirect if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Database connection
require 'db_connection.php';

// Handle form submission for setting a goal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_goal'])) {
    $goal_name = $_POST['goal_name'];
    $goal_amount = $_POST['goal_amount'];
    
    // Insert goal into database
    $sql = "INSERT INTO goals (user_id, goal_name, goal_amount, goal_to_go) VALUES (?, ?, ?, 0)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isd", $user_id, $goal_name, $goal_amount);
    
    if ($stmt->execute()) {
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    } else {
        $message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

// Handle form submission for adding money to goal_to_go
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_money'])) {
    $add_amount = $_POST['add_amount'];
    $goal_id = $_POST['goal_id'];

    // Fetch current user's total_money to check if they have enough funds
    $user_sql = "SELECT total_money FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user = $user_result->fetch_assoc();
    $user_stmt->close();

    // Check if the user has enough money
    if ($user['total_money'] < $add_amount) {
        $message = "Error: You don't have enough money to add to the goal.";
    } else {
        // Fetch current goal information to calculate remaining amount
        $goal_sql = "SELECT goal_amount, goal_to_go FROM goals WHERE user_id = ? AND goal_id = ?";
        $goal_stmt = $conn->prepare($goal_sql);
        $goal_stmt->bind_param("ii", $user_id, $goal_id);
        $goal_stmt->execute();
        $goal_result = $goal_stmt->get_result();
        $goal = $goal_result->fetch_assoc();
        $goal_stmt->close();

        // Calculate the remaining amount to the goal
        $remaining_amount = $goal['goal_amount'] - $goal['goal_to_go'];

        // Check if adding the money exceeds the goal amount
        if ($add_amount > $remaining_amount) {
            $message = "Error: The amount to add exceeds the remaining amount to reach your goal.";
        } else {
            // Update goal_to_go in database
            $sql = "UPDATE goals SET goal_to_go = goal_to_go + ? WHERE user_id = ? AND goal_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("dii", $add_amount, $user_id, $goal_id);

            if ($stmt->execute()) {
                // Insert transaction into transactions table
                $transaction_sql = "INSERT INTO transactions (type, description, amount, date, user_id) VALUES ('outcome', 'transfer to savings', ?, NOW(), ?)";
                $transaction_stmt = $conn->prepare($transaction_sql);
                $transaction_stmt->bind_param("di", $add_amount, $user_id);

                if ($transaction_stmt->execute()) {
                    $transaction_id = $transaction_stmt->insert_id;

                    // Insert transaction_tag into transaction_tags table with tag_id = 7
                    $transaction_tag_sql = "INSERT INTO transaction_tags (transaction_id, tag_id) VALUES (?, 7)";
                    $transaction_tag_stmt = $conn->prepare($transaction_tag_sql);
                    $transaction_tag_stmt->bind_param("i", $transaction_id);
                    $transaction_tag_stmt->execute();
                    $transaction_tag_stmt->close();
                }

                // Update total_money after adding money to savings goal
                $update_money_sql = "UPDATE users SET total_money = total_money - ? WHERE id = ?";
                $update_money_stmt = $conn->prepare($update_money_sql);
                $update_money_stmt->bind_param("di", $add_amount, $user_id);
                $update_money_stmt->execute();
                $update_money_stmt->close();

                $transaction_stmt->close();

                header("Location: ".$_SERVER['PHP_SELF']);
                exit;
            } else {
                $message = "Error: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}

// Handle withdrawal of money from savings goal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw_money'])) {
    $withdraw_amount = $_POST['withdraw_amount'];
    $goal_id = $_POST['goal_id'];

    // Fetch current user's total_money to check if they have enough funds
    $user_sql = "SELECT total_money FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user = $user_result->fetch_assoc();
    $user_stmt->close();

    // Check if the user has enough money
    if ($user['total_money'] < $withdraw_amount) {
        $message = "Error: You don't have enough money to withdraw.";
    } else {
        // Fetch current goal information
        $goal_sql = "SELECT goal_amount, goal_to_go FROM goals WHERE user_id = ? AND goal_id = ?";
        $goal_stmt = $conn->prepare($goal_sql);
        $goal_stmt->bind_param("ii", $user_id, $goal_id);
        $goal_stmt->execute();
        $goal_result = $goal_stmt->get_result();
        $goal = $goal_result->fetch_assoc();
        $goal_stmt->close();

        // Check if the withdrawal amount is less than or equal to the amount available
        if ($withdraw_amount > $goal['goal_to_go']) {
            $message = "Error: The amount to withdraw exceeds the available savings.";
        } else {
            // Update goal_to_go after withdrawal
            $sql = "UPDATE goals SET goal_to_go = goal_to_go - ? WHERE user_id = ? AND goal_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("dii", $withdraw_amount, $user_id, $goal_id);

            if ($stmt->execute()) {
                // Insert transaction for withdrawal into transactions table
                $transaction_sql = "INSERT INTO transactions (type, description, amount, date, user_id) VALUES ('income', 'withdrawal from savings', ?, NOW(), ?)";
                $transaction_stmt = $conn->prepare($transaction_sql);
                $transaction_stmt->bind_param("di", $withdraw_amount, $user_id);

                if ($transaction_stmt->execute()) {
                    $transaction_id = $transaction_stmt->insert_id;

                    // Insert transaction_tag into transaction_tags table with tag_id = 8 (withdrawal tag)
                    $transaction_tag_sql = "INSERT INTO transaction_tags (transaction_id, tag_id) VALUES (?, 8)";
                    $transaction_tag_stmt = $conn->prepare($transaction_tag_sql);
                    $transaction_tag_stmt->bind_param("i", $transaction_id);
                    $transaction_tag_stmt->execute();
                    $transaction_tag_stmt->close();
                }

                // Update total_money after withdrawal from savings goal
                $update_money_sql = "UPDATE users SET total_money = total_money + ? WHERE id = ?";
                $update_money_stmt = $conn->prepare($update_money_sql);
                $update_money_stmt->bind_param("di", $withdraw_amount, $user_id);
                $update_money_stmt->execute();
                $update_money_stmt->close();

                $transaction_stmt->close();

                header("Location: ".$_SERVER['PHP_SELF']);
                exit;
            } else {
                $message = "Error: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}

// Fetch all goals for the current user
$sql = "SELECT goal_id, goal_name, goal_amount, goal_to_go FROM goals WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$goals = [];
while ($row = $result->fetch_assoc()) {
    $goals[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="styles.css">

    <title>Goal Management</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawCharts);

        function drawCharts() {
            <?php foreach ($goals as $index => $goal): ?>
            var data<?= $index ?> = google.visualization.arrayToDataTable([
                ['Type', 'Amount'],
                ['Remaining Amount to Goal', <?= $goal['goal_amount'] - $goal['goal_to_go'] ?>],
                ['Achieved', <?= $goal['goal_to_go'] ?>]
            ]);

            var options<?= $index ?> = {
                title: '<?= htmlspecialchars($goal['goal_name']) ?>: <?= number_format($goal['goal_amount'], 2) ?>€',
                colors: ['#FFEB3B', '#2196F3'], // Yellow and blue colors for the chart
                width: '100%',
                height: '100%'
            };

            var chart<?= $index ?> = new google.visualization.PieChart(document.getElementById('goal_piechart<?= $index ?>'));
            chart<?= $index ?>.draw(data<?= $index ?>, options<?= $index ?>);
            <?php endforeach; ?>
        }
    </script>
    <style>
        header {
            background-color: #4CAF50;
            color: white;
            padding: 1rem;
            text-align: center;
            height: 60px;          
}

        .container {
            display: flex;
            flex-direction: row;
            align-items: center;
            margin-bottom: 50px;
        }

        .chart {
            width: 500px;
            height: 400px;
        }
        .form-button-2 {
            text-decoration: none;
            padding: 10px 20px;
            background-color:rgb(255, 255, 255); /* Green color for the buttons */
            color: #4CAF50;
            border-radius: 5px;
            font-size: 16px;
            margin: 10px;
        }

         .form-button {
            text-decoration: none;
            padding: 10px 20px;
            background-color: #4CAF50; /* Green color for the buttons */
            color: #fff;
            border-radius: 5px;
            font-size: 16px;
            margin: 10px;
        }
        .nav-button:hover, .form-button:hover {
            background-color: #388E3C; /* Darker green on hover */
        }
        .form-container {
            display: none;
            flex-direction: column;
            align-items: center;
        }
        .form-container.active {
            display: flex;
            position: absolute;
            right: 270px;
            top: 80px;
        }
    </style>
</head>
<body>
    <header>
         <a href="index.php" class="nav-button">Back</a>

        <button class="form-button-2" onclick="toggleForm()">Add New Goal</button>
    </header>
    <div class="container">
        <div class="form-container" id="goalForm">
            <form method="POST" action="">
                <label for="goal_name">Goal Title:</label>
                <input type="text" id="goal_name" name="goal_name" required>
                <label for="goal_amount">Goal Amount (€):</label>
                <input type="number" id="goal_amount" name="goal_amount" required>
                <button type="submit" name="new_goal" class="ng">Set Goal</button>
            </form>
        </div>

        <?php foreach ($goals as $index => $goal): ?>
        <div class="chart-container">
            <div id="goal_piechart<?= $index ?>" class="chart"></div>
            <button class="form-button" onclick="toggleMoneyForm(<?= $index ?>)">Add Money</button>
            <div class="form-container" id="moneyForm<?= $index ?>">
                <form method="POST" action="">
                    <label for="add_amount">Amount to Add (€):</label>
                    <input type="number" id="add_amount" name="add_amount" required>
                    <input type="hidden" name="goal_id" value="<?= $goal['goal_id'] ?>">
                    <button type="submit" name="add_money">Add Money</button>
                </form>
            </div>

            <!-- Withdraw money form -->
            <button class="form-button" onclick="toggleWithdrawForm(<?= $index ?>)">Withdraw Money</button>
            <div class="form-container" id="withdrawForm<?= $index ?>">
                <form method="POST" action="">
                    <label for="withdraw_amount">Amount to Withdraw (€):</label>
                    <input type="number" id="withdraw_amount" name="withdraw_amount" required>
                    <input type="hidden" name="goal_id" value="<?= $goal['goal_id'] ?>">
                    <button type="submit" name="withdraw_money">Withdraw</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
        function toggleForm() {
            document.getElementById('goalForm').classList.toggle('active');
        }
        
        function toggleMoneyForm(index) {
            document.getElementById('moneyForm' + index).classList.toggle('active');
        }

        function toggleWithdrawForm(index) {
            document.getElementById('withdrawForm' + index).classList.toggle('active');
        }
    </script>
</body>
</html>
