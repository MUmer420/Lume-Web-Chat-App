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

if ($blocked_id === 0 || $blocker_id === $blocked_id) {
    exit("Invalid target context.");
}

// Confirm relationship state doesn't already exist
$stmtCheck = $conn->prepare("SELECT id FROM blocks WHERE blocker_id = ? AND blocked_id = ?");
$stmtCheck->bind_param("ii", $blocker_id, $blocked_id);
$stmtCheck->execute();
if ($stmtCheck->get_result()->num_rows > 0) {
    exit("User context already restricted.");
}
$stmtCheck->close();

// Insert Entry
$stmt = $conn->prepare("INSERT INTO blocks (blocker_id, blocked_id) VALUES (?, ?)");
$stmt->bind_param("ii", $blocker_id, $blocked_id);

if ($stmt->execute()) {
    echo "success";
} else {
    echo "Error processing blocking command execution.";
}
$stmt->close();
?>