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

// Fetch data for the transactions chart
$data = [];
if ($is_admin) {
    // Admins: Fetch total transaction amounts by type for all users
    $sql = "SELECT type, SUM(amount) as total_amount FROM transactions GROUP BY type";
    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        $data[] = [$row['type'], (float)$row['total_amount']];
    }
} else {
    // Regular users: Fetch total transaction amounts by type for the logged-in user
    $sql = "SELECT type, SUM(amount) as total_amount FROM transactions WHERE user_id = ? GROUP BY type";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $data[] = [$row['type'], (float)$row['total_amount']];
    }
}

// Fetch data for the tags chart
$tag_data = [];
if ($is_admin) {
    // Admins: Fetch total transaction amounts by tag for all users
    $sql = "SELECT t.name, SUM(tr.amount) as total_amount 
            FROM tags t 
            LEFT JOIN transaction_tags tt ON t.id = tt.tag_id 
            LEFT JOIN transactions tr ON tt.transaction_id = tr.id 
            GROUP BY t.name";
    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        $tag_data[] = [$row['name'], (float)$row['total_amount']];
    }
} else {
    // Regular users: Fetch total transaction amounts by tag for the logged-in user
    $sql = "SELECT t.name, SUM(tr.amount) as total_amount 
            FROM tags t 
            LEFT JOIN transaction_tags tt ON t.id = tt.tag_id 
            LEFT JOIN transactions tr ON tt.transaction_id = tr.id 
            WHERE tr.user_id = ? 
            GROUP BY t.name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $tag_data[] = [$row['name'], (float)$row['total_amount']];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pie Charts</title>
    <link rel="stylesheet" href="styles.css">

    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawCharts);

        function drawCharts() {
            // Draw transactions chart
            var transactionData = google.visualization.arrayToDataTable([
                ['Type', 'Total Amount'],
                <?php
                foreach ($data as $d) {
                    echo "['" . $d[0] . "', " . $d[1] . "],";
                }
                ?>
            ]);

            var transactionOptions = {
                title: 'Total Amount by Transaction Type'
            };

            var transactionChart = new google.visualization.PieChart(document.getElementById('transaction_piechart'));
            transactionChart.draw(transactionData, transactionOptions);

            // Draw tags chart
            var tagData = google.visualization.arrayToDataTable([
                ['Tag', 'Total Amount'],
                <?php
                foreach ($tag_data as $d) {
                    echo "['" . $d[0] . "', " . $d[1] . "],";
                }
                ?>
            ]);

            var tagOptions = {
                title: 'Total Amount by Tag'
            };

            var tagChart = new google.visualization.PieChart(document.getElementById('tag_piechart'));
            tagChart.draw(tagData, tagOptions);
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
        .nav-button{
            position: absolute; /* or relative, depending on your layout */ 
            top: 30px; /* Adjust this value to move the button down */ 
            transform: translateX(-50%);
            font-size: 1.2em;
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

        .chart-container {
            display: flex;
            justify-content: space-around;
            align-items: center;
            margin: 20px;
        }
        .chart {
            width: 45%;
            height: 500px;
        }
    </style>
</head>
<header>
    
    <a href="index.php" class="nav-button">Back</a>
</header>
<body>
    <div class="chart-container">
        <div id="transaction_piechart" class="chart"></div>
        <div id="tag_piechart" class="chart"></div>
    </div>
</body>
</html>