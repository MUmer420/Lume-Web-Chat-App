<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$theme = isset($_COOKIE['lume-theme']) ? $_COOKIE['lume-theme'] : 'dark';

$sender_id   = $_SESSION['user_id'];
$receiver_id = intval($_GET['user'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $receiver_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: users.php");
    exit();
}

$initial = strtoupper(substr($user['username'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat — <?php echo htmlspecialchars($user['username']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <script>
        (function () {
            const match = document.cookie.match(new RegExp('(^| )lume-theme=([^;]+)'));
            const savedTheme = match ? match[2] : 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
</head>
<body>

<div class="chat-wrapper">

    <div class="chat-header">
        <button class="chat-header-back" onclick="location.replace('users.php')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </button>
        <div class="chat-header-avatar"><?php echo $initial; ?></div>
        <div class="chat-header-info">
            <div class="chat-header-name"><?php echo htmlspecialchars($user['username']); ?></div>
            <div class="chat-header-status" id="status-line">online</div>
        </div>
        <button type="button" id="theme-toggle" class="theme-btn" aria-label="Toggle Theme">
        <span class="sun-icon">☀️</span>
        <span class="moon-icon">🌙</span>
        </button>
        
        <div class="chat-header-actions" style="margin-left: auto;">
            <button id="block-btn" data-receiver="<?php echo $receiver_id; ?>" style="background-color: #ff4d4d; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: bold;">
                Block User
            </button>
        </div>
    </div>

    <div id="chat-box" class="chat-box"></div>

    <div class="message-form-wrapper">
        <form id="message-form" class="message-form">
            <div class="message-input-wrap">
                <input type="text" id="message" placeholder="Type a message" autocomplete="off">
            </div>
            <button type="submit" class="send-btn" aria-label="Send">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"/>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
            </button>
        </form>
    </div>

</div>

<script>
    var receiver_id = <?php echo $receiver_id; ?>;
    var csrf_token  = "<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>";
</script>
<script src="js/theme_handler.js"></script>
<script src="js/chat.js"></script>

</body>
</html>