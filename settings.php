<?php
session_start();
include "db.php";

// Redirect if the user is not actively authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user = $_SESSION['user_id'];
$message = "";
$success = false;
$theme = isset($_COOKIE['lume-theme']) ? $_COOKIE['lume-theme'] : 'dark';

// Generate a security token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 1. Fetch current user data to pre-populate form options
$stmt = $conn->prepare("SELECT username, email, language FROM users WHERE id = ?");
$stmt->bind_param("i", $current_user);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 2. Handle profile update submission
if (isset($_POST['update_profile'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Security violation: Invalid transaction processing state.");
    }

    $new_username = trim($_POST['username'] ?? '');
    $new_language = $_POST['language'] ?? '';
    $allowed_languages = ['English', 'Spanish', 'French', 'German', 'Italian', 'Chinese'];

    // Validation checks
    if (strlen($new_username) < 3 || strlen($new_username) > 100) {
        $message = "Username must be between 3 and 100 characters.";
    } elseif (!preg_match('/^[a-zA-Z0-9_ ]+$/', $new_username)) {
        $message = "Username can only contain alphanumeric characters, underscores, and spaces.";
    } elseif (!in_array($new_language, $allowed_languages)) {
        $message = "Please select a valid supported translation language.";
    } else {
        // Update database record
        $update_stmt = $conn->prepare("UPDATE users SET username = ?, language = ? WHERE id = ?");
        $update_stmt->bind_param("ssi", $new_username, $new_language, $current_user);

        if ($update_stmt->execute()) {
            $success = true;
            $message = "Settings updated successfully!";
            
            // Sync current active session credentials immediately
            $_SESSION['username'] = $new_username;
            $_SESSION['language'] = $new_language;
            
            // Refresh local variable state for display
            $user_data['username'] = $new_username;
            $user_data['language'] = $new_language;
        } else {
            $message = "Error mapping records: This username might already be claimed.";
        }
        $update_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lume — Settings</title>
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
<div class="auth-wrapper">
    <div class="auth-card form-page-card chat-bubble-glow">
        
        <div class="auth-header-actions" style="justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <button class="chat-header-back" onclick="window.location.href='users.php'" aria-label="Go Back" style="color: var(--text);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
            
            <button type="button" id="theme-toggle" class="theme-btn" aria-label="Toggle Theme">
                <span class="sun-icon">☀️</span>
                <span class="moon-icon">🌙</span>
            </button>
        </div>

        <div class="auth-logo">
            <div class="home-avatar">⚙️</div>
            <h1>Account Settings</h1>
            <p>Modify your user profile preferences</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form id="settings-form" method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-input" placeholder="Update username" autocomplete="off"
                       value="<?php echo htmlspecialchars($user_data['username']); ?>">
                <div class="error-msg" id="username-error"></div>
            </div>

            <div class="input-group">
                <label for="email">Account Email (Immutable)</label>
                <input type="text" id="email" class="form-input" style="opacity: 0.6; cursor: not-allowed;" readonly
                       value="<?php echo htmlspecialchars($user_data['email']); ?>">
            </div>

            <div class="input-group">
                <label for="language">Preferred Chat Language</label>
                <select id="language" name="language" class="form-input class-dropdown">
                    <option value="English" <?php echo ($user_data['language'] === 'English') ? 'selected' : ''; ?>>English</option>
                    <option value="Spanish" <?php echo ($user_data['language'] === 'Spanish') ? 'selected' : ''; ?>>Spanish</option>
                    <option value="French" <?php echo ($user_data['language'] === 'French') ? 'selected' : ''; ?>>French</option>
                    <option value="German" <?php echo ($user_data['language'] === 'German') ? 'selected' : ''; ?>>German</option>
                    <option value="Italian" <?php echo ($user_data['language'] === 'Italian') ? 'selected' : ''; ?>>Italian</option>
                    <option value="Chinese" <?php echo ($user_data['language'] === 'Chinese') ? 'selected' : ''; ?>>Chinese</option>
                </select>
                <div class="error-msg" id="language-error"></div>
            </div>

            <button type="submit" name="update_profile" class="btn-primary form-submit-btn">Save Changes</button>
        </form>

    </div>
</div>

<script src="js/theme_handler.js"></script>
<script src="js/settings_validation.js"></script>
</body>
</html>