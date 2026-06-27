<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';

$message = "";
$message_class = "";
$valid_token = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $current_time = date("Y-m-d H:i:s");
    
    // Verify token and expiration mapping
    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND token_expires_at > ?");
    $stmt->bind_param("ss", $token, $current_time);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $valid_token = true;
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $new_password = $_POST['password'];
            
            // Password Alphanumeric Complexity Verification Trap
            if (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
                $message = "Complexity Error: Password requires at least one capital letter and one number.";
                $message_class = "alert alert-danger";
            } else {
                // Hash securely using standard PHP production parameters
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password, clear tracking security tokens, and reset failed attempts
                $update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, token_expires_at = NULL, failed_attempts = 0, lockout_time = NULL WHERE reset_token = ?");
                $update->bind_param("ss", $hashed_password, $token);
                $update->execute();
                
                $message = "Success! Password updated cleanly. You can now log in.";
                $message_class = "alert alert-success";
                $valid_token = false; // Hide form after success
            }
        }
    } else {
        $message = "Security Link Expired or Invalid token parameters.";
        $message_class = "alert alert-danger";
    }
} else {
    $message = "No security routing token provided.";
    $message_class = "alert alert-danger";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lume - Update Password</title>
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

<div class="auth-wrapper form-page-card">
    
    <div class="auth-header-actions">
        <button type="button" class="theme-btn" id="theme-toggle" aria-label="Toggle theme">
            🌓
        </button>
    </div>

    <div class="auth-card">
        <div class="auth-logo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #fff; width: 60px; height: 60px; background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%); border-radius: 16px; padding: 12px; margin: 0 auto; display: block;">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0110 0v4"></path>
            </svg>
            <h1 style="text-align: center; margin-top: 15px;">New Password</h1>
            <p style="text-align: center;">Update your account credentials securely.</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="<?php echo $message_class; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($valid_token): ?>
        <form method="POST">
            <div class="input-group">
                <label for="password" style="margin-bottom: 8px;">Enter New Password</label>
                <input type="password" id="password" name="password" placeholder="Min. 1 upper case & 1 number" required>
            </div>
            <button type="submit" class="btn-primary">Update Password</button>
        </form>
        <?php endif; ?>

        <div class="auth-footer" style="text-align: center; margin-top: 20px;">
            <a href="login.php" style="color: #7c3aed; text-decoration: none; font-weight: 600;">← Return to Login Portal</a>
        </div>
        
    </div>
</div>

<script src="theme_handler.js"></script>

</body>
</html>