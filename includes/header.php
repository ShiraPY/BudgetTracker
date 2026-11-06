<?php
$unread = 0;
if (!empty($_SESSION['user_id'])) {
    $n = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0');
    $n->execute([$_SESSION['user_id']]);
    $unread = (int)$n->fetchColumn();
}
$nav = [
    'dashboard'     => ['⌂', 'Dashboard'],
    'expenses'      => ['▤', 'Expenses'],
    'income'        => ['＋', 'Income'],
    'budget'        => ['◉', 'Budget'],
    'reports'       => ['▥', 'Reports'],
    'calendar'      => ['□', 'Calendar'],
    'notifications' => ['🔔', 'Notifications'],
    'settings'      => ['⚙', 'Settings'],
];
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title ?? 'BudgetTracker') ?> | BudgetTracker</title>
<link rel="stylesheet" href="assets/style.css"></head>
<body>
<aside id="side"><h2>💳 BudgetTracker</h2><small>Plan • Track • Save</small>
<nav>
<?php foreach ($nav as $k => [$icon, $label]): ?>
<a href="<?= $k ?>.php" class="<?= ($page ?? '') === $k ? 'active' : '' ?>"><?= $icon ?> <?= $label ?><?= ($k === 'notifications' && $unread) ? ' <span class="badge">' . $unread . '</span>' : '' ?></a>
<?php endforeach; ?>
</nav>
<a class="logout" href="logout.php">↪ Logout</a></aside>
<main>
<header><button id="menu" type="button" onclick="document.getElementById('side').classList.toggle('open')">☰</button><span class="spacer"></span><span>👤 <b><?= e(current_user_name()) ?></b><?= (!empty($_SESSION['user_id']) && is_admin($pdo)) ? ' <span class="badge">Admin</span>' : '' ?></span></header>
<section class="page"><?php show_flash(); ?>
