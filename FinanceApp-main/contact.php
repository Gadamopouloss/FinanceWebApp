<?php

require 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';
$notificationMessages = [];

// Handle Sending Friend Request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['friend_email'])) {
    $friend_email = $_POST['friend_email'];

    // Check if the friend email is the user's own email
    $stmtSelfCheck = $conn->prepare("SELECT id FROM users WHERE Email = ? AND id = ?");
    $stmtSelfCheck->bind_param("si", $friend_email, $user_id);
    $stmtSelfCheck->execute();
    $selfCheckResult = $stmtSelfCheck->get_result();

    if ($selfCheckResult->num_rows > 0) {
        $error_message = "You cannot send a friend request to yourself!";
    } else {
        // Check if a user with that email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE Email = ?");
        $stmt->bind_param("s", $friend_email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $friend = $result->fetch_assoc();
            $friend_id = $friend['id'];

            // Check if a friendship already exists
            $stmtCheck = $conn->prepare("SELECT id FROM friendships WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
            $stmtCheck->bind_param("iiii", $user_id, $friend_id, $friend_id, $user_id);
            $stmtCheck->execute();
            $checkResult = $stmtCheck->get_result();

            if ($checkResult->num_rows == 0) {
                // Insert a friend request into the friendships table
                $stmtInsert = $conn->prepare("INSERT INTO friendships (user_id, friend_id, status, created_at) VALUES (?, ?, 'pending', NOW())");
                $stmtInsert->bind_param("ii", $user_id, $friend_id);
                $stmtInsert->execute();

                $success_message = "Friend request sent successfully!";
            } else {
                $error_message = "You are already friends or have a pending request!";
            }
        } else {
            $error_message = "User not found!";
        }
    }
}

// Fetch Pending Friend Requests for the current user
$pendingRequestsStmt = $conn->prepare("
    SELECT friendships.id, users.username, friendships.status 
    FROM friendships 
    JOIN users ON friendships.user_id = users.id 
    WHERE friendships.friend_id = ? AND friendships.status = 'pending'
");
$pendingRequestsStmt->bind_param("i", $user_id);
$pendingRequestsStmt->execute();
$pendingRequestsResult = $pendingRequestsStmt->get_result();

// Accept or Decline Friend Request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_id']) && isset($_POST['action'])) {
    $request_id = $_POST['request_id'];
    $action = $_POST['action']; // 'accept' or 'decline'

    if ($action === 'accept') {
        // Update friendship status to accepted for the current user
        $stmtUpdate = $conn->prepare("UPDATE friendships SET status = 'accepted' WHERE id = ?");
        $stmtUpdate->bind_param("i", $request_id);
        $stmtUpdate->execute();

        // Fetch friend id from the request to add the reverse friendship
        $stmtFriend = $conn->prepare("SELECT user_id, friend_id FROM friendships WHERE id = ?");
        $stmtFriend->bind_param("i", $request_id);
        $stmtFriend->execute();
        $friendResult = $stmtFriend->get_result();
        $friendship = $friendResult->fetch_assoc();

        // Insert reverse friendship for the friend (add mutual friendship in both directions)
        if ($friendship['user_id'] != $user_id) {
            $stmtReverseInsert = $conn->prepare("INSERT INTO friendships (user_id, friend_id, status, created_at) VALUES (?, ?, 'accepted', NOW())");
            $stmtReverseInsert->bind_param("ii", $friendship['friend_id'], $friendship['user_id']);
            $stmtReverseInsert->execute();
        }

        $notificationMessages[] = "Friend request accepted!";
    } elseif ($action === 'decline') {
        $stmtDelete = $conn->prepare("DELETE FROM friendships WHERE id = ?");
        $stmtDelete->bind_param("i", $request_id);
        $stmtDelete->execute();
        $notificationMessages[] = "Friend request declined!";
    }
}

// Fetch notifications count for the user
$notificationCountStmt = $conn->prepare("
    SELECT COUNT(*) AS pending_count 
    FROM friendships 
    WHERE friend_id = ? AND status = 'pending'
");
$notificationCountStmt->bind_param("i", $user_id);
$notificationCountStmt->execute();
$notificationCountResult = $notificationCountStmt->get_result();
$notificationCount = $notificationCountResult->fetch_assoc()['pending_count'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Friendship Notifications</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        h1, h2 {
            text-align: center;
        }
        .notification {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin: 10px auto;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            width: 80%;
        }
        .success {
            background-color: #d4edda;
            color: #4CAF50;
            border: 1px solid #c3e6cb;
        }
        .pending-requests {
            margin: 20px auto;
            width: 80%;
            padding: 10px;
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        .add-contact-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);
        }

        .add-contact-popup {
            display: none;
            position: fixed;
            bottom: 70px;
            right: 90px; /* Adjust this value if necessary to position it left from the button */
            background: white;
            border: 1px solid #ccc;
            border-radius: 8px;
            width: 300px;
            padding: 15px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);
        }

        .notification-bell {
            position: fixed;
            bottom: 100px;
            right: 20px;
            cursor: pointer;
        }
        .notification-bell img {
            width: 50px;
            height: 50px;
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: red;
            color: white;
            font-size: 14px;
            font-weight: bold;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 4px rgba(0, 0, 0, 0.5);
        }
        .notification-popup {
            display: none;
            position: fixed;
            bottom: 160px;
            right: 20px;
            background: white;
            border: 1px solid #ccc;
            border-radius: 8px;
            width: 400px;
            padding: 15px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);
        }
        .notification-popup ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .notification-popup li {
            margin: 5px 0;
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        .notification-popup li:last-child {
            border-bottom: none;
        }
    </style>
    <script>
        // Show/hide the Add Contact popup
        function toggleAddContactPopup() {
            const popup = document.getElementById('addContactPopup');
            popup.style.display = popup.style.display === 'none' ? 'block' : 'none';
        }

        // Show/hide the notification popup
        function toggleNotificationPopup() {
            const popup = document.getElementById('notificationPopup');
            popup.style.display = popup.style.display === 'block' ? 'none' : 'block';
        }
    </script>
</head>
<body>

    <?php if ($success_message): ?>
        <div class="notification success">
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="notification">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <!-- Add Contact Button -->
    <button class="add-contact-btn" onclick="toggleAddContactPopup()">+</button>

    <!-- Add Contact Popup -->
    <div id="addContactPopup" class="add-contact-popup" style="display: none;">
        <h3>Add a Friend</h3>
        <form method="POST" action="">
            <input type="email" name="friend_email" placeholder="Enter friend's email" required>
            <button type="submit">Send Request</button>
        </form>
    </div>

    <!-- Notification Bell -->
    <div class="notification-bell" onclick="toggleNotificationPopup()">
        <img src="https://img.icons8.com/?size=100&id=83193&format=png&color=40C057" alt="Notification Bell">
        <div id="notificationBadge" class="notification-badge" style="display: <?= $notificationCount > 0 ? 'flex' : 'none' ?>;">
            <?= $notificationCount ?>
        </div>
    </div>

    <!-- Notification Popup -->
    <div id="notificationPopup" class="notification-popup">
        <h4>Notifications</h4>
        <ul id="notificationList">
            <?php if ($pendingRequestsResult->num_rows > 0): ?>
                <?php while ($row = $pendingRequestsResult->fetch_assoc()): ?>
                    <li>
                        <strong><?= htmlspecialchars($row['username']) ?></strong> sent you a friend request.
                        <form method="POST" action="" >
                            <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                            <button type="submit" name="action" value="accept">Accept</button>
                            <button type="submit" name="action" value="decline">Decline</button>
                        </form>
                    </li>
                <?php endwhile; ?>
            <?php else: ?>
                <li>No pending requests.</li>
            <?php endif; ?>
        </ul>
    </div>
</body>
</html>
