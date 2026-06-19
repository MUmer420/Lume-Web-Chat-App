<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$initial = strtoupper(substr($_SESSION['username'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lume — Home</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="home-wrapper">
    <div class="home-card">
        <div class="home-avatar"><?php echo $initial; ?></div>
        <h2><?php echo htmlspecialchars($_SESSION['username']); ?></h2>
        <span class="lang-badge"> <?php echo htmlspecialchars($_SESSION['language']); ?></span>

        <div class="home-actions">
            <a href="users.php">
                <button class="btn-primary">Start Chatting</button>
            </a>
            <a href="logout.php">
                <button class="btn-secondary">Logout</button>
            </a>
        </div>
    </div>
</div>
</body>
</html>