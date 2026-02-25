<?php
session_start();
require 'db_connection.php';

// Redirect if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'You are not logged in.']);
    exit;
}

// Retrieve user ID from session
$user_id = $_SESSION['user_id'];

// Get input data
$type = $_POST['type'];
$description = $_POST['description'];
$amount = floatval($_POST['amount']);
$date = $_POST['date'];
$tags = $_POST['tags'] ?? [];

// Validate input
if (empty($type) || empty($description) || $amount <= 0 || empty($date)) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required and must be valid.']);
    exit;
}

// Retrieve the user's current total_money from the users table
$stmt = $conn->prepare("SELECT total_money FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

if (!$user_data) {
    echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    exit;
}

$current_balance = floatval($user_data['total_money']);
$stmt->close();

// Check if outcome exceeds total money
if ($type === 'outcome' && $amount > $current_balance) {
    echo json_encode(['status' => 'error', 'message' => 'You do not have enough money for this transaction.']);
    exit;
}

// Calculate the new balance
$new_balance = $type === 'income' ? $current_balance + $amount : $current_balance - $amount;

// Insert transaction into the transactions table
$stmt = $conn->prepare("INSERT INTO transactions (user_id, type, description, amount, date) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("issds", $user_id, $type, $description, $amount, $date);

if ($stmt->execute()) {
    $transaction_id = $stmt->insert_id;

    // Update the user's total_money in the users table
    $update_stmt = $conn->prepare("UPDATE users SET total_money = ? WHERE id = ?");
    $update_stmt->bind_param("di", $new_balance, $user_id);
    $update_stmt->execute();
    $update_stmt->close();

    // Insert tags if provided
    if (!empty($tags)) {
        $tag_stmt = $conn->prepare("INSERT INTO transaction_tags (transaction_id, tag_id) VALUES (?, ?)");
        foreach ($tags as $tag_id) {
            $tag_stmt->bind_param("ii", $transaction_id, $tag_id);
            $tag_stmt->execute();
        }
        $tag_stmt->close();
    }

    echo json_encode(['status' => 'success', 'message' => 'Transaction added successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to add transaction.']);
}

$stmt->close();
$conn->close();
?>
