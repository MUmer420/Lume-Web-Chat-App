<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    exit("Unauthorized execution path.");
}

if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    exit("Invalid security token.");
}

$blocker_id = $_SESSION['user_id'];
$blocked_id = intval($_POST['blocked_id'] ?? 0);

if ($blocked_id === 0) {
    exit("Invalid target parameters.");
}

// Delete the specific blocking rule link relationship row
$stmt = $conn->prepare("DELETE FROM blocks WHERE blocker_id = ? AND blocked_id = ?");
$stmt->bind_param("ii", $blocker_id, $blocked_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo "success";
    } else {
        echo "No active restriction records found for this user context.";
    }
} else {
    echo "Database error handling entry drop execution.";
}

$stmt->close();
?>