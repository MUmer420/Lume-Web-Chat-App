<?php
$expected_key = getenv('SETUP_KEY');
$provided_key = $_GET['setup_key'] ?? '';

if (empty($expected_key) || $provided_key !== $expected_key) {
    http_response_code(403);
    die("<strong style='color:red;'>403 Forbidden:</strong> Setup is not accessible. Provide a valid setup_key parameter.");
}
set_time_limit(60);

$mysql_url = getenv('MYSQL_URL');

if (!empty($mysql_url)) {
    echo "Connecting via unified Railway URL...<br>";
    flush();

    $url = parse_url($mysql_url);
    $host     = $url['host'] . (isset($url['port']) ? ':' . $url['port'] : '');
    $username = $url['user'];
    $password = $url['pass'];
    $dbname   = ltrim($url['path'], '/');
} else {
    // Fallback to individual variables or local development defaults
    echo "Connecting via individual environment variables...<br>";
    flush();
    
    $host     = getenv('DB_HOST') ?: 'localhost';
    $username = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASS') ?: '';
    $dbname   = getenv('DB_NAME') ?: 'lume';
}

// Establish the MySQL Connection
$conn = @new mysqli($host, $username, $password);

if ($conn->connect_error) {
    die("<br><strong style='color:red;'>Connection Failed permanently:</strong> " . $conn->connect_error);
}

echo "<br><strong style='color:green;'>Connected successfully!</strong> Building application schema...<br><br>";
flush();

// 1. Establish database environment
$sql = "CREATE DATABASE IF NOT EXISTS `$dbname`";
if ($conn->query($sql)) {
    echo "Database '$dbname' verified/created successfully.<br>";
} else {
    die("Database Error: " . $conn->error);
}

$conn->select_db($dbname);

// 2. Build Users Table with Security Lockout Columns Pre-Baked
$users = "CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    language VARCHAR(50) NOT NULL,
    theme VARCHAR(10) NOT NULL DEFAULT 'dark',
    failed_attempts INT DEFAULT 0,
    lockout_time DATETIME NULL,
    reset_token VARCHAR(255) NULL, 
    token_expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($users)) {
    echo "Users Table Configured Successfully<br>";
} else {
    echo "Users Table Error: " . $conn->error . "<br>";
}

// 3. Build Messages Table with Translation History Fields
$messages = "CREATE TABLE IF NOT EXISTS messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    original_message TEXT NOT NULL,
    translated_message TEXT,
    original_language VARCHAR(20) DEFAULT 'english',
    translated_language VARCHAR(20) DEFAULT 'english',
    deleted_by_sender tinyint(1) DEFAULT 0,
    deleted_by_receiver tinyint(1) DEFAULT 0,
    is_deleted_everyone tinyint(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($messages)) {
    echo "Messages Table Configured Successfully<br>";
} else {
    echo "Messages Table Error: " . $conn->error . "<br>";
}

// 4. Build Blocks Relationship Table
$blocks = "CREATE TABLE IF NOT EXISTS blocks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    blocker_id INT NOT NULL,
    blocked_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_block (blocker_id, blocked_id),
    FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($blocks)) {
    echo "Blocks Table Configured Successfully<br>";
} else {
    echo "Blocks Table Error: " . $conn->error . "<br>";
}

// 5. Build High-Performance Polling Indexes
try {
    $index = "CREATE INDEX idx_chat_flow ON messages (sender_id, receiver_id, id)";
    $conn->query($index);
    echo "High-Performance Polling Indexes Configured Successfully<br>";
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() == 1061) {
        echo "Polling Indexes Verified (Already Exists)<br>";
    } else {
        echo "Index Configuration Alert: " . $e->getMessage() . "<br>";
    }
}

echo "<br><strong style='color:green;'>Setup Complete!</strong> You can now go to <a href='register.php'>register.php</a> to create accounts.";
$conn->close();
?>