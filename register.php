<?php
session_start();
include "db.php";

$message = "";
$success = false;
$theme = isset($_COOKIE['lume-theme']) ? $_COOKIE['lume-theme'] : 'dark';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_POST['register'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Invalid security request execution state.");
    }

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $language = $_POST['language'] ?? '';

    $allowed_languages = ['English', 'Spanish', 'French', 'German', 'Italian', 'Chinese'];

    if (strlen($username) < 3 || strlen($username) > 100) {
        $message = "Username must be between 3 and 100 characters.";
    } 
    elseif (!preg_match('/^[a-zA-Z0-9_ ]+$/', $username)) { 
        $message = "Username can only contain letters, numbers, underscores, and spaces.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $message = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password)) {
        $message = "Password must contain at least one uppercase letter, one lowercase letter, and one number.";
    } elseif (!in_array($language, $allowed_languages)) {
        $message = "Please select a valid language.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // Wrapped database operations in a try-catch to intercept duplicate constraint violations cleanly
        try {
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, language, theme) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $email, $hashed, $language, $theme);

            if ($stmt->execute()) {
                $success = true;
                $message = "Account created! You can now sign in.";
                $username = $email = $language = "";
            } else {
                $message = "Registration failed. Please try again.";
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            // MySQL error code 1062 represents a unique/duplicate key constraint violation
            if ($e->getCode() === 1062) {
                // Pinpoint if it was the email or username that caused the clash
                if (strpos($e->getMessage(), 'users.email') !== false) {
                    $message = "This email address is already registered. Please log in or use another.";
                } elseif (strpos($e->getMessage(), 'users.username') !== false) {
                    $message = "This username is already taken. Please choose another.";
                } else {
                    $message = "An account with these details already exists.";
                }
            } else {
                // General fallback container for unhandled SQL exceptions
                $message = "Database error encountered: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lume — Register</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card form-page-card chat-bubble-glow">
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
            <h1>Create Account</h1>
            <p>Join Lume to start chatting</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form id="register-form" action="register.php" method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-input" placeholder="Enter username" autocomplete="off" 
                       value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                <div class="error-msg" id="username-error"></div>
            </div>

            <div class="input-group">
                <label for="email">Email Address</label>
                <input type="text" id="email" name="email" class="form-input" placeholder="Enter email" autocomplete="off"
                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                <div class="error-msg" id="email-error"></div>
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="Create password">
                <div class="error-msg" id="password-error"></div>
            </div>

            <div class="input-group">
                <label for="language">Preferred Language</label>
                <select id="language" name="language" class="form-input class-dropdown">
                    <option value="">-- Select Language --</option>
                    <option value="English" <?php echo (isset($language) && $language === 'English') ? 'selected' : ''; ?>>English</option>
                    <option value="Spanish" <?php echo (isset($language) && $language === 'Spanish') ? 'selected' : ''; ?>>Spanish</option>
                    <option value="French" <?php echo (isset($language) && $language === 'French') ? 'selected' : ''; ?>>French</option>
                    <option value="German" <?php echo (isset($language) && $language === 'German') ? 'selected' : ''; ?>>German</option>
                    <option value="Italian" <?php echo (isset($language) && $language === 'Italian') ? 'selected' : ''; ?>>Italian</option>
                    <option value="Chinese" <?php echo (isset($language) && $language === 'Chinese') ? 'selected' : ''; ?>>Chinese</option>
                </select>
                <div class="error-msg" id="language-error"></div>
            </div>

            <button type="submit" name="register" class="btn-primary form-submit-btn">Register Account</button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="login.php">Log In</a>
        </div>
    </div>
</div>
<script src="js/theme_handler.js"></script>
<script src="js/register_validation.js"></script>
</body>
</html>