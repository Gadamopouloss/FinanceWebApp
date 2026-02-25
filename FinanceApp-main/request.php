<?php
require 'db_connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $request_id = intval($_POST['request_id']);

    if ($action === 'accept') {
        $stmt = $conn->prepare("UPDATE friendships SET status = 'accepted' WHERE id = ?");
    } elseif ($action === 'decline') {
        $stmt = $conn->prepare("UPDATE friendships SET status = 'declined' WHERE id = ?");
    }
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    header("Location: friends.php");
    exit;
}
?>
