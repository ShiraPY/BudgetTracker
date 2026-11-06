<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';   // creates $pdo

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money($v): string { return '₱' . number_format((float)$v, 2); }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { return; }   // only POST needs a token
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(400);
        exit('Invalid or expired form token. Go back, refresh the page and try again.');
    }
}

function set_flash(string $msg, string $type = 'success'): void { $_SESSION['flash'] = ['msg' => $msg, 'type' => $type]; }
function show_flash(): void {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash']; unset($_SESSION['flash']);
        echo '<div class="alert ' . e($f['type']) . '">' . e($f['msg']) . '</div>';
    }
}

function require_login(): void {
    if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
}
function current_user_name(): string { return $_SESSION['full_name'] ?? 'Student'; }

function get_totals(PDO $pdo, int $uid, ?string $start = null, ?string $end = null): array {
    $sql = "SELECT COALESCE(SUM(CASE WHEN type='income'  THEN amount END),0) AS income,
                   COALESCE(SUM(CASE WHEN type='expense' THEN amount END),0) AS expense
            FROM transactions WHERE user_id=?";
    $args = [$uid];
    if ($start !== null && $end !== null) { $sql .= ' AND transaction_date BETWEEN ? AND ?'; $args[] = $start; $args[] = $end; }
    $q = $pdo->prepare($sql); $q->execute($args);
    $r = $q->fetch();
    return ['income' => (float)$r['income'], 'expense' => (float)$r['expense']];
}

function add_notification(PDO $pdo, int $uid, string $msg): void {
    // avoid spamming the same unread alert
    $c = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND message=? AND is_read=0');
    $c->execute([$uid, $msg]);
    if ((int)$c->fetchColumn() === 0) {
        $q = $pdo->prepare('INSERT INTO notifications(user_id,message) VALUES(?,?)');
        $q->execute([$uid, $msg]);
    }
}


/* ---------- Admin support ---------- */
// Adds the "role" column to older databases and creates the default admin account if none exists.
function ensure_admin(PDO $pdo): void {
    try {
        if (!$pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('user','admin') NOT NULL DEFAULT 'user'");
        }
        if ((int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn() === 0) {
            $q = $pdo->prepare("INSERT INTO users(full_name,username,email,password,role) VALUES(?,?,?,?, 'admin')");
            $q->execute(['Administrator', 'admin', 'admin@budgettracker.local', password_hash('Admin@12345', PASSWORD_DEFAULT)]);
        }
    } catch (PDOException $e) { /* ignore; admin may already exist under another record */ }
}
ensure_admin($pdo);

function is_admin(PDO $pdo): bool {
    if (empty($_SESSION['user_id'])) { return false; }
    $q = $pdo->prepare('SELECT role FROM users WHERE id=?');
    $q->execute([$_SESSION['user_id']]);
    return $q->fetchColumn() === 'admin';
}

// Streams a full SQL backup (structure + data) as a download. Admin only.
function export_sql(PDO $pdo): void {
    if (!is_admin($pdo)) { http_response_code(403); exit('Admin access only.'); }
    $file = 'budget_tracker_' . date('Ymd_His') . '.sql';
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $file . '"');
    echo "-- BudgetTracker SQL export\n-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    echo "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\nUSE `" . DB_NAME . "`;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
    foreach (['users', 'transactions', 'budgets', 'notifications'] as $t) {
        $create = $pdo->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_NUM)[1];
        $create = preg_replace('/^CREATE TABLE /', 'CREATE TABLE IF NOT EXISTS ', $create);
        echo "-- Table: $t\n$create;\n\n";
        $st = $pdo->query("SELECT * FROM `$t`");
        $cols = null; $batch = [];
        while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
            if ($cols === null) { $cols = '`' . implode('`,`', array_keys($r)) . '`'; }
            $vals = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), array_values($r));
            $batch[] = '(' . implode(',', $vals) . ')';
            if (count($batch) >= 100) { echo "INSERT INTO `$t` ($cols) VALUES\n" . implode(",\n", $batch) . ";\n"; $batch = []; }
        }
        if ($batch) { echo "INSERT INTO `$t` ($cols) VALUES\n" . implode(",\n", $batch) . ";\n"; }
        echo "\n";
    }
    echo "SET FOREIGN_KEY_CHECKS=1;\n";
    exit;
}
