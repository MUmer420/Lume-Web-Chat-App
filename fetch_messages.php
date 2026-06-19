<?php
session_start();
include "db.php";

$me    = $_SESSION['user_id'] ?? 0;
$other = intval($_GET['user']    ?? 0);
$last_id = intval($_GET['last_id'] ?? 0);

if ($me === 0 || $other === 0) {
    exit(json_encode(['messages' => [], 'deleted_ids' => [], 'last_id' => $last_id]));
}

// 1. Fetch new or initial history messages
if ($last_id > 0) {
    $stmt = $conn->prepare("
        SELECT id, sender_id, original_message, translated_message, deleted_by_sender, deleted_by_receiver, is_deleted_everyone
        FROM messages
        WHERE id > ?
          AND ((sender_id = ? AND receiver_id = ?)
           OR  (sender_id = ? AND receiver_id = ?))
        ORDER BY created_at ASC
    ");
    $stmt->bind_param("iiiii", $last_id, $me, $other, $other, $me);
} else {
    $stmt = $conn->prepare("
        SELECT id, sender_id, original_message, translated_message, deleted_by_sender, deleted_by_receiver, is_deleted_everyone
        FROM (
            SELECT id, sender_id, original_message, translated_message, deleted_by_sender, deleted_by_receiver, is_deleted_everyone, created_at
            FROM messages
            WHERE (sender_id = ? AND receiver_id = ?)
               OR (sender_id = ? AND receiver_id = ?)
            ORDER BY created_at DESC
            LIMIT 100
        ) AS recent
        ORDER BY created_at ASC
    ");
    $stmt->bind_param("iiii", $me, $other, $other, $me);
}

$stmt->execute();
$result = $stmt->get_result();

$max_id   = $last_id;
$messages = [];

while ($row = $result->fetch_assoc()) {
    $isMe = ((int)$row['sender_id'] === (int)$me);
    $max_id = max($max_id, (int)$row['id']);

    if ($isMe && (int)$row['deleted_by_sender'] === 1) continue;
    if (!$isMe && (int)$row['deleted_by_receiver'] === 1) continue;

    $isDeletedEveryone = ((int)$row['is_deleted_everyone'] === 1);

    if ($isDeletedEveryone) {
        $text = 'This message was deleted.';
    } elseif ($isMe) {
        $text = $row['original_message'];
    } else {
        $text = !empty($row['translated_message']) ? $row['translated_message'] : $row['original_message'];
    }

    $messages[] = [
        'id'         => (int)$row['id'],
        'is_mine'    => $isMe,
        'text'       => $text,
        'is_deleted' => $isDeletedEveryone,
    ];
}
$stmt->close();

// 2. EXTRA SYNC STEP: Grab all globally deleted message IDs in this conversation context
$deleted_ids = [];
$syncStmt = $conn->prepare("
    SELECT id FROM messages 
    WHERE is_deleted_everyone = 1 
      AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
");
$syncStmt->bind_param("iiii", $me, $other, $other, $me);
$syncStmt->execute();
$syncResult = $syncStmt->get_result();
while ($syncRow = $syncResult->fetch_assoc()) {
    $deleted_ids[] = (int)$syncRow['id'];
}
$syncStmt->close();

header('Content-Type: application/json');
echo json_encode([
    'messages'    => $messages, 
    'deleted_ids' => $deleted_ids, 
    'last_id'     => $max_id
]);
?>