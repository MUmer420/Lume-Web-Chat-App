<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    exit("unauthorized");
}

if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    exit("invalid_csrf");
}

$user_id    = $_SESSION['user_id'];
$message_id = intval($_POST['message_id'] ?? 0);
$mode       = $_POST['mode'] ?? ''; 

if ($message_id === 0 || !in_array($mode, ['self', 'everyone'])) {
    exit("invalid_request");
}

// Fetch message metadata to determine sender vs receiver context
$stmt = $conn->prepare("SELECT sender_id, receiver_id FROM messages WHERE id = ?");
$stmt->bind_param("i", $message_id);
$stmt->execute();
$msg = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$msg) {
    exit("not_found");
}

$isSender = ((int)$msg['sender_id'] === (int)$user_id);
$isReceiver = ((int)$msg['receiver_id'] === (int)$user_id);

if (!$isSender && !$isReceiver) {
    exit("forbidden"); // User has nothing to do with this message
}

if ($mode === 'everyone') {
    // Structural Guard: Only original sender can execute an 'everyone' drop!
    if (!$isSender) {
        exit("You can only delete your own messages for everyone.");
    }
    
    $stmtDel = $conn->prepare("
        UPDATE messages 
        SET original_message = 'This message was deleted.', 
            translated_message = 'This message was deleted.', 
            is_deleted_everyone = 1 
        WHERE id = ?
    ");
    $stmtDel->bind_param("i", $message_id);
} else {
    // Delete for Self mode allocation logic
    if ($isSender) {
        $stmtDel = $conn->prepare("UPDATE messages SET deleted_by_sender = 1 WHERE id = ?");
    } else {
        $stmtDel = $conn->prepare("UPDATE messages SET deleted_by_receiver = 1 WHERE id = ?");
    }
    $stmtDel->bind_param("i", $message_id);
}

if ($stmtDel->execute()) {
    echo "success";
} else {
    echo "error";
}
$stmtDel->close();
?>