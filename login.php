<?php
session_start();
include "db.php";

$message = "";
$theme = isset($_COOKIE['lume-theme']) ? $_COOKIE['lume-theme'] : 'dark';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_POST['login'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Invalid request.");
    }

    // FIX (Rate Limiting): Block brute-force after 10 failed attempts in 15 minutes
    $now = time();
    if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = [];

    // Remove attempts older than 15 minutes
    $_SESSION['login_attempts'] = array_filter($_SESSION['login_attempts'], fn($t) => ($now - $t) < 900);

    if (count($_SESSION['login_attempts']) >= 10) {
        $message = "Too many failed attempts. Please wait 15 minutes and try again.";
    } else {
        $email    = trim($_POST['email']);
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            //  Regenerate session ID on login so an attacker who planted a session ID can't hijack the logged-in session
            session_regenerate_id(true);

            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['language'] = $user['language'];
            unset($_SESSION['login_attempts']); // clear on success

            if (!empty($user['theme'])) {
                setcookie('lume-theme', $user['theme'], time() + (86400 * 30), "/");
            }

            header("Location: home.php");
            exit();
        } else {
            $_SESSION['login_attempts'][] = $now; // record failed attempt
            $message = "Invalid email or password combination.";
        }
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
