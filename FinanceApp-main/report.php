<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$is_admin = ($_SESSION['role'] === 'admin');
$user_id = $_SESSION['user_id'];

// Database connection
require 'db_connection.php';

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

// If an admin requests transactions for a specific user
if ($is_admin && isset($_GET['user_id'])) {
    $specific_user_id = intval($_GET['user_id']);

    $sql_user_transactions = "SELECT id, type, description, amount, date 
                               FROM transactions 
                               WHERE user_id = ? 
                               ORDER BY date DESC";
    $stmt_user_transactions = $conn->prepare($sql_user_transactions);
    $stmt_user_transactions->bind_param("i", $specific_user_id);
    $stmt_user_transactions->execute();
    $result_user_transactions = $stmt_user_transactions->get_result();

    $user_transactions = [];
    while ($row = $result_user_transactions->fetch_assoc()) {
        $user_transactions[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($user_transactions);

    $stmt_user_transactions->close();
    $conn->close();
    exit;
}

// Fetch transactions for the current month (default functionality)
$current_month = date('Y-m');
$sql_month = "SELECT id, type, description, amount, date 
              FROM transactions 
              WHERE DATE_FORMAT(date, '%Y-%m') = ? AND user_id = ?";
$stmt_month = $conn->prepare($sql_month);
$stmt_month->bind_param("si", $current_month, $user_id);
$stmt_month->execute();
$result_month = $stmt_month->get_result();

$transactions = [];
while ($row = $result_month->fetch_assoc()) {
    $transactions[] = $row;
}

// Fetch monthly income and outcome for the past year (default functionality)
$current_year = date('Y');
$sql_year = "SELECT DATE_FORMAT(date, '%Y-%m') as month, type, SUM(amount) as total 
             FROM transactions 
             WHERE YEAR(date) = ? AND user_id = ? 
             GROUP BY month, type";
$stmt_year = $conn->prepare($sql_year);
$stmt_year->bind_param("si", $current_year, $user_id);
$stmt_year->execute();
$result_year = $stmt_year->get_result();

$monthly_totals = [];
while ($row = $result_year->fetch_assoc()) {
    $monthly_totals[$row['month']][$row['type']] = $row['total'];
}

// Calculate the current account balance (default functionality)
$sql_balance = "SELECT type, SUM(amount) as total 
                FROM transactions 
                WHERE user_id = ? 
                GROUP BY type";
$stmt_balance = $conn->prepare($sql_balance);
$stmt_balance->bind_param("i", $user_id);
$stmt_balance->execute();
$result_balance = $stmt_balance->get_result();

$balance = 0;
while ($row = $result_balance->fetch_assoc()) {
    if ($row['type'] == 'income') {
        $balance += $row['total'];
    } else {
        $balance -= $row['total'];
    }
}

// Prepare the response
$response = [
    "transactions_this_month" => $transactions,
    "monthly_totals_last_year" => $monthly_totals,
    "current_balance" => $balance
];

header('Content-Type: application/json');
echo json_encode($response);

$stmt_month->close();
$stmt_year->close();
$stmt_balance->close();
$conn->close();


?>
