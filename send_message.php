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

// 3. Trigger LibreTranslate API call with absolute safety fallbacks
if ($source_code !== $target_code) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://translator:5000/translate");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'q'      => $message,
        'source' => $source_code,
        'target' => $target_code,
        'format' => 'text'
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    //  Set a tight timeout so your chat app never freezes up waiting
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); 
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

    $response = curl_exec($ch);
    
    // Check if the translator container successfully answered
    if (!curl_errno($ch)) {
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        error_log("LibreTranslate HTTP: " . $http_code . " Response: " . $response);
        if ($http_code === 200) {
            $responseData = json_decode($response, true);
            if (isset($responseData['translatedText'])) {
                $translated_message = $responseData['translatedText'];
            }
        }
    } else {
        // Log the error internally on the server side for debugging later
        error_log("LibreTranslate cURL error: " . curl_error($ch));
    $translated_message = $message; // fallback
    }
    curl_close($ch);
}

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