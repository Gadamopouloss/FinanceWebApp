<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'db_connection.php';

$user_id = $_SESSION['user_id'];
$recipient_contact = $_POST['recipient_contact'] ?? null;
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : null;

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($recipient_contact) || $amount <= 0) {
        $error_message = "Invalid input. Please provide a valid recipient and amount.";
    } else {
        $stmtContact = $conn->prepare("SELECT id, total_money FROM users WHERE id IN (
            SELECT friend_id FROM account_management.friendships WHERE user_id = ? AND status = 'accepted'
        ) AND id = ?");
        $stmtContact->bind_param("ii", $user_id, $recipient_contact);
        $stmtContact->execute();
        $resultContact = $stmtContact->get_result();

        if ($resultContact->num_rows === 0) {
            $error_message = "Recipient not found in your contacts.";
        } else {
            $recipientData = $resultContact->fetch_assoc();
            $recipient_id = $recipientData['id'];
            $recipient_balance = floatval($recipientData['total_money']);
            $stmtContact->close();

            $stmtBalanceSender = $conn->prepare("SELECT total_money FROM users WHERE id = ?");
            $stmtBalanceSender->bind_param("i", $user_id);
            $stmtBalanceSender->execute();
            $resultBalanceSender = $stmtBalanceSender->get_result();
            $senderData = $resultBalanceSender->fetch_assoc();
            $sender_balance = floatval($senderData['total_money']);
            $stmtBalanceSender->close();

            if ($amount > $sender_balance) {
                $error_message = "Insufficient funds. Your balance is €$sender_balance.";
            } else {
                $new_sender_balance = $sender_balance - $amount;
                $new_recipient_balance = $recipient_balance + $amount;

                $conn->begin_transaction();
                try {
                    // Insert sender transaction
                    $stmtDeduct = $conn->prepare("INSERT INTO transactions (user_id, type, description, amount, date) VALUES (?, 'outcome', 'Transfer to contact', ?, NOW())");
                    $stmtDeduct->bind_param("id", $user_id, $amount);
                    $stmtDeduct->execute();
                    $sender_transaction_id = $conn->insert_id;

                    // Update sender balance
                    $stmtUpdateSender = $conn->prepare("UPDATE users SET total_money = ? WHERE id = ?");
                    $stmtUpdateSender->bind_param("di", $new_sender_balance, $user_id);
                    $stmtUpdateSender->execute();

                    // Insert recipient transaction
                    $stmtAdd = $conn->prepare("INSERT INTO transactions (user_id, type, description, amount, date) VALUES (?, 'income', 'Transfer from contact', ?, NOW())");
                    $stmtAdd->bind_param("id", $recipient_id, $amount);
                    $stmtAdd->execute();
                    $recipient_transaction_id = $conn->insert_id;

                    // Update recipient balance
                    $stmtUpdateRecipient = $conn->prepare("UPDATE users SET total_money = ? WHERE id = ?");
                    $stmtUpdateRecipient->bind_param("di", $new_recipient_balance, $recipient_id);
                    $stmtUpdateRecipient->execute();

                    // Insert transaction tags for sender transaction
                    $stmtTagSender = $conn->prepare("INSERT INTO transaction_tags (transaction_id, tag_id) VALUES (?, 8)");
                    $stmtTagSender->bind_param("i", $sender_transaction_id);
                    $stmtTagSender->execute();

                    // Insert transaction tags for recipient transaction
                    $stmtTagRecipient = $conn->prepare("INSERT INTO transaction_tags (transaction_id, tag_id) VALUES (?, 8)");
                    $stmtTagRecipient->bind_param("i", $recipient_transaction_id);
                    $stmtTagRecipient->execute();

                    $conn->commit();
                    $success_message = "Money transfer of €$amount to the recipient was successful!";
                } catch (Exception $e) {
                    $conn->rollback();
                    $error_message = "Transaction failed: " . $e->getMessage();
                }
                                    
            }
        }
    }
}

// Fetch contacts
$stmtContacts = $conn->prepare("
    SELECT DISTINCT u.id, u.username
    FROM users u
    INNER JOIN account_management.friendships f 
    ON (u.id = f.friend_id AND f.user_id = ?)
       OR (u.id = f.user_id AND f.friend_id = ?)
    WHERE f.status = 'accepted' AND u.id != ?
");
$stmtContacts->bind_param("iii", $user_id, $user_id, $user_id);
$stmtContacts->execute();
$contactsResult = $stmtContacts->get_result();
$contacts = $contactsResult->fetch_all(MYSQLI_ASSOC);
$stmtContacts->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Money</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        header {
            background: #4CAF50;
            color: white;
            padding: 1em;
            width: 100%;
            text-align: center;
        }

        main {
            margin: 2em;
            text-align: center;
        }

        .transfer-form {
            background: white;
            padding: 2em;
            border-radius: 8px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }

        .error-message {
            color: red;
            font-weight: bold;
            margin-bottom: 1em;
        }

        .success-message {
            color: green;
            font-weight: bold;
            margin-bottom: 1em;
        }
    </style>
</head>
<body>
    <header>
        <h1>Send Money to Contacts</h1>
        <a href="send_money.php" class="nav-button">Back</a>

    </header>
    <main>
        <?php if ($error_message): ?>
            <div class="error-message">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="success-message">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <div class="transfer-form">
            <form method="POST" action="">
                <label for="recipient_contact">Recipient:</label>
                <select name="recipient_contact" required>
                    <option value="">--Select a Contact--</option>
                    <?php foreach ($contacts as $contact): ?>
                        <option value="<?= htmlspecialchars($contact['id']) ?>">
                            <?= htmlspecialchars($contact['username']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <br><br>
                <label for="amount">Amount (€):</label>
                <input type="number" name="amount" step="0.01" required>
                <br><br>
                <button type="submit">Send Money</button>
            </form>
        </div>
    </main>
</body>
</html>
<?php
include('contact.php');

?>