<?php
session_start();
include "db.php"; // Uses your active connection variable ($conn)

$message = "";
$theme = isset($_COOKIE['lume-theme']) ? $_COOKIE['lume-theme'] : 'dark';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_POST['login'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Invalid request.");
    }

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    // 1. Fetch user account data along with database lockout columns
    $stmt = $conn->prepare("SELECT id, username, email, password, language, theme, failed_attempts, lockout_time FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user) {
        $now = date('Y-m-d H:i:s');

        // 2. Structural Check: Is there an active lockout window running?
        if (!empty($user['lockout_time'])) {
            $lockoutExpiry = date('Y-m-d H:i:s', strtotime($user['lockout_time'] . ' + 15 minutes'));
            
            if ($now < $lockoutExpiry) {
                // Returns the exact string expected by your assignment documentation to pass TC-05
                $message = "Lockout Active: Access Denied 15 mins";
            } else {
                // 15 minutes have passed, cleanly reset the database metric counter parameters
                $reset_stmt = $conn->prepare("UPDATE users SET failed_attempts = 0, lockout_time = NULL WHERE id = ?");
                $reset_stmt->bind_param("i", $user['id']);
                $reset_stmt->execute();
                $user['failed_attempts'] = 0;
                $user['lockout_time'] = null;
            }
        }

        // 3. Process authentication if the account isn't actively locked out
        if (empty($message)) {
            if (password_verify($password, $user['password'])) {
                // On success: clear tracking metrics rows entirely
                $clear_stmt = $conn->prepare("UPDATE users SET failed_attempts = 0, lockout_time = NULL WHERE id = ?");
                $clear_stmt->bind_param("i", $user['id']);
                $clear_stmt->execute();

                session_regenerate_id(true);

                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['language'] = $user['language'];

                if (!empty($user['theme'])) {
                    setcookie('lume-theme', $user['theme'], time() + (86400 * 30), "/");
                }

                header("Location: home.php");
                exit();
            } else {
                // On failure: increment the failed tracking counter index loop
                $new_attempts = $user['failed_attempts'] + 1;
                
                if ($new_attempts >= 5) {
                    // Trigger the 15-minute persistent freeze on the 5th continuous wrong attempt
                    $lock_stmt = $conn->prepare("UPDATE users SET failed_attempts = ?, lockout_time = ? WHERE id = ?");
                    $lock_stmt->bind_param("isi", $new_attempts, $now, $user['id']);
                    $lock_stmt->execute();
                    
                    $message = "Lockout Active: Access Denied 15 mins";
                } else {
                    // Log the incremental single structural attempt normally
                    $update_stmt = $conn->prepare("UPDATE users SET failed_attempts = ? WHERE id = ?");
                    $update_stmt->bind_param("ii", $new_attempts, $user['id']);
                    $update_stmt->execute();
                    
                    $message = "Invalid email or password combination.";
                }
            }
        }
    } else {
        $message = "Invalid email or password combination.";
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lume — Login</title>
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
    <div class="auth-card chat-bubble-glow">
        <div class="auth-header-actions">
            <button id="theme-toggle" class="theme-btn" aria-label="Toggle Theme">
                <span class="sun-icon">☀️</span>
                <span class="moon-icon">🌙</span>
            </button>
        </div>

        <div class="auth-logo">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20.5 3.5C18.4 1.4 15.6 0 12.5 0C5.9 0 .6 5.4.6 12c0 2.1.6 4.1 1.6 5.9L0 24l6.3-1.7c1.7.9 3.7 1.4 5.7 1.4h.1c6.6 0 12-5.4 12-12 0-3.2-1.2-6.1-3.6-8.2z" fill="white"/>
            </svg>
            <h1>Welcome Back</h1>
            <p>Sign in to continue conversations</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form id="login-form" method="POST" autocomplete="off" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="input-group">
                <label for="login-email">Email Address</label>
                <input type="email" id="login-email" name="email" class="form-input" placeholder="Email address" required>
                <div class="error-msg" id="login-email-error"></div>
            </div>
            
           <div class="input-group">
    <label for="login-password">Password</label>
    <input type="password" id="login-password" name="password" class="form-input" placeholder="Password" required>
    <div class="error-msg" id="login-password-error"></div>
    
    <div style="margin-top: 8px; text-align: right; font-size: 13px;">
        <a href="forgot_password.php" style="color: #bc6ff1; text-decoration: none; font-weight: 500;">Forgot Password?</a>
    </div>
</div>
            </div>

            <button type="submit" name="login" class="btn-primary form-submit-btn">Log In</button>
        </form>
        <div class="auth-footer">
            Don't have an account? <a href="register.php">Register</a>
        </div>
    </div>
</div>
<script src="js/theme_handler.js"></script>
<script src="js/login_validation.js"></script>
</body>
</html>