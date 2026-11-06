<?php
// Database settings. XAMPP defaults: user "root", blank password.
// If MySQL runs on a different port (e.g. 3307), change DB_PORT.
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'budget_tracker');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('<h2>Database connection failed</h2><p>1) Start <b>MySQL</b> in the XAMPP Control Panel.<br>'
       . '2) Import <b>database.sql</b> in phpMyAdmin.<br>'
       . '3) Check the settings in <code>config/database.php</code>.</p><pre>'
       . htmlspecialchars($e->getMessage()) . '</pre>');
}
