<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    exit("unauthorized");
}

if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    exit("invalid_csrf");
}

$sender_id   = $_SESSION['user_id'];
$receiver_id = intval($_POST['receiver_id'] ?? 0);
$message     = trim($_POST['message'] ?? '');

if ($receiver_id === 0 || $message === '') {
    exit("invalid_payload");
}

// Block check: reject if either party has blocked the other
$blockStmt = $conn->prepare("
    SELECT id FROM blocks 
    WHERE (blocker_id = ? AND blocked_id = ?) 
       OR (blocker_id = ? AND blocked_id = ?)
    LIMIT 1
");
$blockStmt->bind_param("iiii", $sender_id, $receiver_id, $receiver_id, $sender_id);
$blockStmt->execute();
$blockStmt->store_result();
if ($blockStmt->num_rows > 0) {
    $blockStmt->close();
    exit("blocked");
}
$blockStmt->close();

$stmt = $conn->prepare("SELECT id, language FROM users WHERE id = ? OR id = ?");
$stmt->bind_param("ii", $sender_id, $receiver_id);
$stmt->execute();
$res = $stmt->get_result();

$languages = [];
while ($row = $res->fetch_assoc()) {
    $languages[$row['id']] = strtolower(trim($row['language']));
}
$stmt->close();

$sender_lang   = $languages[$sender_id] ?? 'english';
$receiver_lang = $languages[$receiver_id] ?? 'english';

$langMap = [
    'english' => 'en',
    'spanish' => 'es',
    'french'  => 'fr',
    'german'  => 'de',
    'italian' => 'it',
    'chinese' => 'zh'
];

$source_code = $langMap[$sender_lang] ?? 'en';
$target_code = $langMap[$receiver_lang] ?? 'en';

$translated_message = $message; // Default fallback

if ($source_code !== $target_code) {
    $ch = curl_init();
    
    // Read the dynamic shared variable link from Railway or fallback to local Docker service naming
    $translator_host = getenv('TRANSLATOR_URL') ?: 'translator:5000';
    
    // Ensure the proper http protocol is prefixed onto the network string
    $translator_url = (strpos($translator_host, 'http') === 0) ? $translator_host : "http://" . $translator_host;
    $translator_url = rtrim($translator_url, '/') . "/translate";

    curl_setopt($ch, CURLOPT_URL, $translator_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'q'      => $message,
        'source' => $source_code,
        'target' => $target_code,
        'format' => 'text'
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Set a reasonable timeout to allow the cloud translation engine to respond
    curl_setopt($ch, CURLOPT_TIMEOUT, 15); 
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

    $response = curl_exec($ch);
    
    if (!curl_errno($ch)) {
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code === 200) {
            $responseData = json_decode($response, true);
            if (isset($responseData['translatedText'])) {
                $translated_message = $responseData['translatedText'];
            } else {
                $translated_message = "DEBUG: JSON Missing Key. Response: " . substr($response, 0, 50);
            }
        } else {
            $translated_message = "DEBUG: HTTP Error Code " . $http_code . " Response: " . substr($response, 0, 50);
        }
    } else {
        $translated_message = "DEBUG: cURL Error: " . curl_error($ch);
    }
    curl_close($ch);
} // <--- Added the missing closing brace here

$stmt = $conn->prepare("
    INSERT INTO messages (
        sender_id, 
        receiver_id, 
        original_message, 
        translated_message, 
        original_language, 
        translated_language,
        deleted_by_sender,
        deleted_by_receiver,
        is_deleted_everyone
    ) VALUES (?, ?, ?, ?, ?, ?, 0, 0, 0)
");

if (!$stmt) {
    exit("Database prepare failed: " . $conn->error);
}

$stmt->bind_param("iissss", $sender_id, $receiver_id, $message, $translated_message, $sender_lang, $receiver_lang);

if ($stmt->execute()) {
    echo "success";
} else {
    echo "Execution failed: " . $stmt->error;
}

$stmt->close();
?>