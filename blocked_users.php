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

$current_user = $_SESSION['user_id'];

// Query users who are currently blocked by the logged-in user
$stmt = $conn->prepare("
    SELECT u.id, u.username, u.language 
    FROM users u
    JOIN blocks b ON u.id = b.blocked_id
    WHERE b.blocker_id = ?
");
$stmt->bind_param("i", $current_user);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lume — Blocked Users</title>
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

        <div class="panel-header" style="display: flex; align-items: center; padding: 15px 20px; background: #6c5ce7; border-top-left-radius: 20px; border-top-right-radius: 20px;">
    
    <a href="users.php" class="chat-header-back" style="display: inline-flex; align-items: center; justify-content: center; margin-right: 15px; text-decoration: none; color: white; transition: opacity 0.2s ease;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"/>
        </svg>
    </a>

    <h2 style="margin: 0; color: white; font-size: 20px; font-weight: 600;">Blocked Registry</h2>
</div>

        <div class="users-list" style="margin-top: 20px;">
            <?php if ($result->num_rows === 0): ?>
                <div style="text-align: center; color: #888; padding: 40px 20px;">
                    No blocked profiles found.
                </div>
            <?php endif; ?>

            <?php while ($row = $result->fetch_assoc()): 
                $initial = strtoupper(substr($row['username'], 0, 1));
            ?>
            <div class="user-item" style="display: flex; align-items: center; justify-content: space-between; text-decoration: none; cursor: default;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div class="user-avatar alt"><?php echo $initial; ?></div>
                    <div class="user-info">
                        <div class="user-info-name" style="color: white;"><?php echo htmlspecialchars($row['username']); ?></div>
                        <div class="user-info-lang"> <?php echo htmlspecialchars($row['language']); ?></div>
                    </div>
                </div>
                
                <div class="action-container">
                    <button class="unblock-btn" data-id="<?php echo $row['id']; ?>" style="background-color: #2ecc71; color: white; border: none; padding: 6px 14px; border-radius: 4px; cursor: pointer; font-weight: bold;">
                        Unblock
                    </button>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

    </div>
</div>
<script src="js/theme-toggle.js"></script>
<script>
var csrf_token = "<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>";

document.querySelectorAll('.unblock-btn').forEach(button => {
    button.addEventListener('click', function() {
        var targetId        = this.getAttribute('data-id');
        var actionContainer = this.closest('.action-container');
        
        if (!confirm("Allow communication with this user again?")) return;

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "unblock_user.php", true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        
        xhr.onload = function() {
            if (xhr.status === 200 && xhr.responseText.trim() === "success") {
                // Instead of completely dropping the row out of sight, morph the UI state
                actionContainer.innerHTML = `
                    <a href="chat.php?user=${targetId}">
                        <button style="background-color: #9b59b6; color: white; border: none; padding: 6px 14px; border-radius: 4px; cursor: pointer; font-weight: bold;">
                            Go to Chat
                        </button>
                    </a>
                `;
            } else {
                alert(xhr.responseText);
            }
        };
        xhr.send("blocked_id=" + encodeURIComponent(targetId) + "&csrf_token=" + encodeURIComponent(csrf_token));
    });
});
</script>
</body>
</html>