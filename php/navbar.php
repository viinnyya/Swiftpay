<?php
// Shared navbar for all pages.
$userId = $_SESSION['user_id'] ?? null;
$userLabel = 'Guest';
if ($userId) {
    $user = getUserById($userId);
    $userLabel = htmlspecialchars($user['Username']);
}
$base = '';
if (strpos($_SERVER['SCRIPT_NAME'], '/php/') !== false) {
    $base = '../';
}
?>
<div class="navbar" role="navigation" aria-label="Main navigation">
    <div class="navbar-brand">SwiftPay</div>
    <ul class="navbar-nav">
        <li><a href="<?= $base ?>dashboard.php">Dashboard</a></li>
        <li><a href="<?= $base ?>php/transfer.php">Transfer</a></li>
        <li><a href="<?= $base ?>php/bills.php">Bills</a></li>
        <li><a href="<?= $base ?>php/load.php">Load</a></li>
        <li><a href="<?= $base ?>php/savings.php">Savings</a></li>
        <li><a href="<?= $base ?>settings.php">Settings</a></li>
        <?php if ($userId): ?>
            <li><a class="text-danger" href="<?= $base ?>logout.php">Log out (<?= $userLabel ?>)</a></li>
        <?php else: ?>
            <li><a class="text-success" href="<?= $base ?>login.php">Log in</a></li>
        <?php endif; ?>
    </ul>
</div>
