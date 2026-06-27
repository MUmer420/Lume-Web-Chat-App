<?php
// forgot_password.php - Place this inside your htdocs/lume/ directory
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php'; 

$message = "";
$message_class = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime("+15 minutes"));
        
        $update = $conn->prepare("UPDATE users SET reset_token = ?, token_expires_at = ? WHERE email = ?");
        $update->bind_param("sss", $token, $expires, $email);
        $update->execute();
        
        $resetLink = "http://localhost:8080/reset_password.php?token=" . $token;
        $message = "<strong>Simulation Mode:</strong> Link generated!<br><a href='$resetLink' style='color: inherit; text-decoration: underline;'>Click here to Reset Password</a>";
        $message_class = "alert alert-success";
    } else {
        $message = "Email address not found in our records.";
        $message_class = "alert alert-danger";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lume - Forgot Password</title>
    
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
                <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <h1 style="text-align: center; margin-top: 15px;">Reset Password</h1>
            <p style="text-align: center;">Enter your email to verify account recovery parameters.</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="<?php echo $message_class; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="forgot_password.php">
            <div class="input-group">
                <label for="email" style="margin-bottom: 8px;">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email address" required>
            </div>
            
            <button type="submit" class="btn-primary">Send Recovery Link</button>
        </form>

        <div class="auth-footer" style="text-align: center; margin-top: 20px;">
            <a href="login.php" style="color: #7c3aed; text-decoration: none; font-weight: 600;">← Back to Login Portal</a>
        </div>
        
    </div>
</div>

<script src="js/theme_handler.js"></script>

</body>
</html>