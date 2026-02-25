<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'db_connection.php';

$user_id = $_SESSION['user_id'];
$recipient_contact = $_POST['recipient_contact'] ?? null;
$contact_type = $_POST['contact_type'] ?? null;
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : null;

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($recipient_contact) || empty($amount) || $amount <= 0 || !in_array($contact_type, ['email', 'phone'])) {
        $error_message = "Invalid input. Please provide a valid recipient and amount.";
    } else {
        $column = ($contact_type === 'email') ? 'email' : 'MobilePhone';
        $stmtContact = $conn->prepare("SELECT id, total_money, email FROM users WHERE $column = ? AND id != ?");
        $stmtContact->bind_param("si", $recipient_contact, $user_id);

        $stmtContact->execute();
        $resultContact = $stmtContact->get_result();

        if ($resultContact->num_rows === 0) {
            $error_message = "Recipient not found. Please check the provided $contact_type.";
        } else {
            $recipientData = $resultContact->fetch_assoc();
            $recipient_id = $recipientData['id'];
            $recipient_email = $recipientData['email'];
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
                $error_message = "Insufficient funds. You cannot send €$amount as your available balance is €$sender_balance.";
            } else {
                $new_sender_balance = $sender_balance - $amount;
                $new_recipient_balance = $recipient_balance + $amount;

                $conn->begin_transaction();
                try {
                    // Insert sender transaction
                    $stmtDeduct = $conn->prepare("INSERT INTO transactions (user_id, type, description, amount, date) VALUES (?, 'outcome', 'Transfer to user', ?, NOW())");
                    $stmtDeduct->bind_param("id", $user_id, $amount);
                    $stmtDeduct->execute();
                    $sender_transaction_id = $conn->insert_id;

                    // Update sender balance
                    $stmtUpdateSender = $conn->prepare("UPDATE users SET total_money = ? WHERE id = ?");
                    $stmtUpdateSender->bind_param("di", $new_sender_balance, $user_id);
                    $stmtUpdateSender->execute();

                    // Insert recipient transaction
                    $stmtAdd = $conn->prepare("INSERT INTO transactions (user_id, type, description, amount, date) VALUES (?, 'income', 'Transfer from user', ?, NOW())");
                    $stmtAdd->bind_param("id", $recipient_id, $amount);
                    $stmtAdd->execute();
                    $recipient_transaction_id = $conn->insert_id;


                    // Insert transaction tags for sender transaction
                    $stmtTagSender = $conn->prepare("INSERT INTO transaction_tags (transaction_id, tag_id) VALUES (?, 8)");
                    $stmtTagSender->bind_param("i", $sender_transaction_id);
                    $stmtTagSender->execute();

                    // Insert transaction tags for recipient transaction
                    $stmtTagRecipient = $conn->prepare("INSERT INTO transaction_tags (transaction_id, tag_id) VALUES (?, 8)");
                    $stmtTagRecipient->bind_param("i", $recipient_transaction_id);
                    $stmtTagRecipient->execute();





                    // Update recipient balance
                    $stmtUpdateRecipient = $conn->prepare("UPDATE users SET total_money = ? WHERE id = ?");
                    $stmtUpdateRecipient->bind_param("di", $new_recipient_balance, $recipient_id);
                    $stmtUpdateRecipient->execute();


                    
                    // Send email notification to the recipient
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'Gadams0098@gmail.com'; 
                        $mail->Password = 'dtmb jdgt qiuv etez';       
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;

                        $mail->setFrom('your_email@example.com', 'Your Website');
                        $mail->addAddress($recipient_email);

                        $mail->isHTML(true);
                        $mail->Subject = 'Money Received Notification';
                        $mail->Body = "Dear user,<br><br>You have received €$amount from another user.<br>Your updated balance is: €$new_recipient_balance.<br><br>Thank you for using our service!";
                        $mail->AltBody = "Dear user, You have received €$amount from another user. Your updated balance is: €$new_recipient_balance. Thank you for using our service!";

                        $mail->send();
                        $success_message .= " Notification email sent to the recipient.";
                    } catch (Exception $e) {
                        $error_message .= " Email notification failed: " . $mail->ErrorInfo;
                    }

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

$conn->close();
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

        .method-selection {
            display: flex;
            justify-content: center;
            gap: 1em;
            margin-bottom: 2em;
        }

        .method {
            background: #4CAF50;
            color: white;
            padding: 1em 2em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .method:hover {
            transform: scale(1.1);
        }

        .transfer-form {
            display: none;
            background: white;
            padding: 2em;
            border-radius: 8px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }

        .close-button {
            background: #f44336;
            color: white;
            padding: 0.5em 1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 1em;
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
        <h1>Transfer Money</h1>
        <a href="index.php" class="nav-button">Back</a>
    </header>
    <main>

        
        <?php if ($success_message): ?>
            <div class="success-message"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>

        <!-- Method Selection -->
        <div class="method-selection">
            <button class="method" id="select-email">Send via Email</button>
            <button class="method" id="select-phone">Send via Phone</button>
            <a href="sendcontacts.php" class="method">Send via Contacs</a>

        </div>

<!-- Transfer Form for Email -->
<div class="transfer-form" id="transfer-form-email">
    <form id="emailForm" method="POST">
        <input type="hidden" name="contact_type" value="email">
        <label for="recipient_contact">Recipient (Email):</label>
        <input type="email" name="recipient_contact" required>
        <br><br>
        <label for="amount">Amount (€):</label>
        <input type="number" name="amount" step="0.01" required>
        <br><br>
        <button type="submit">Send Money</button>
    </form>
    <button class="close-button" onclick="closeForm('email')">Close</button>
</div>

<!-- Transfer Form for Phone -->
<div class="transfer-form" id="transfer-form-phone">
    <form id="phoneForm" method="POST">
        <input type="hidden" name="contact_type" value="phone">
        <label for="recipient_contact">Recipient (Phone):</label>
        <input type="tel" name="recipient_contact" required>
        <br><br>
        <label for="amount">Amount (€):</label>
        <input type="number" name="amount" step="0.01" required>
        <br><br>
        <button type="submit">Send Money</button>
    </form>
    <button class="close-button" onclick="closeForm('phone')">Close</button>
</div>

    </main>

    <script>
        const selectEmail = document.getElementById('select-email');
        const selectPhone = document.getElementById('select-phone');

        selectEmail.addEventListener('click', () => {
            hideAllForms();
            document.getElementById('transfer-form-email').style.display = 'block';
        });

        selectPhone.addEventListener('click', () => {
            hideAllForms();
            document.getElementById('transfer-form-phone').style.display = 'block';
        });

        function hideAllForms() {
            document.getElementById('transfer-form-email').style.display = 'none';
            document.getElementById('transfer-form-phone').style.display = 'none';
        }

        function closeForm(type) {
            document.getElementById(`transfer-form-${type}`).style.display = 'none';
        }
    </script>
</body>
</html>




<?php
include('contact.php');

?>