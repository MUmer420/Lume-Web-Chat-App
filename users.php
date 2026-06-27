<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user = $_SESSION['user_id'];
$theme = isset($_COOKIE['lume-theme']) ? $_COOKIE['lume-theme'] : 'dark';

// Filter visibility parameters based on blocking table records
$stmt = $conn->prepare("
    SELECT * FROM users 
    WHERE id != ? 
      AND id NOT IN (SELECT blocked_id FROM blocks WHERE blocker_id = ?)
      AND id NOT IN (SELECT blocker_id FROM blocks WHERE blocked_id = ?)
");
$stmt->bind_param("iii", $current_user, $current_user, $current_user);
$stmt->execute();
$result = $stmt->get_result();

$colors = ['', 'alt', 'alt2', 'alt3', 'alt4'];
$i = 0;
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lume — Chats</title>
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
<div class="users-wrapper">
    <div class="users-panel">

        <div class="panel-header">
            <h2>Lume</h2>
            
            <div class="panel-header-actions">
                <button type="button" id="theme-toggle" class="theme-btn" aria-label="Toggle Theme">
                    <span class="sun-icon">☀️</span>
                    <span class="moon-icon">🌙</span>
                </button>
                <a href="settings.php" title="Account Settings" style="color: currentColor;">
        <button class="icon-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
            </svg>
        </button>
    </a>

                <a href="blocked_users.php" title="Manage Blocked Users" style="color: currentColor;">
                    <button class="icon-btn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                            <line x1="12" y1="9" x2="12" y2="13"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                    </button>
                </a>

                <a href="logout.php">
                    <button class="icon-btn" title="Logout">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </button>
                </a>
            </div>
        </div>

        <div class="search-bar">
            <input type="text" placeholder="Search or start new chat" oninput="filterUsers(this.value)">
        </div>

        <div class="users-list" id="users-list">
            <?php while ($row = $result->fetch_assoc()):
                $colorClass = $colors[$i % count($colors)];
                $initial    = strtoupper(substr($row['username'], 0, 1));
                $i++;
            ?>
            <a class="user-item" href="chat.php?user=<?php echo (int)$row['id']; ?>" data-name="<?php echo htmlspecialchars(strtolower($row['username']), ENT_QUOTES, 'UTF-8'); ?>" onclick="event.preventDefault(); location.replace(this.href)">
                <div class="user-avatar <?php echo $colorClass; ?>"><?php echo $initial; ?></div>
                <div class="user-info">
                    <div class="user-info-name"><?php echo htmlspecialchars($row['username']); ?></div>
                    <div class="user-info-lang">🌐 <?php echo htmlspecialchars($row['language']); ?></div>
                </div>
                <svg class="user-item-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"/>
                </svg>
            </a>
            <?php endwhile; ?>
        </div>

    </div>
</div>

<script src="js/theme_handler.js"></script>
<script>
function filterUsers(query) {
    const items      = document.querySelectorAll('.user-item');
    const lowerQuery = query.toLowerCase().trim();
    items.forEach(item => {
        const name = item.dataset.name || '';
        item.style.display = name.includes(lowerQuery) ? 'flex' : 'none';
    });
}
</script>
</body>
</html>