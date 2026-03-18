<?php
session_start();
require_once 'php/config.php';
require_once 'php/auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user = getUserById($_SESSION['user_id']);
// fetch balance
$stmt = $pdo->prepare('SELECT Balance FROM Accounts WHERE UserID = ?');
$stmt->execute([$_SESSION['user_id']]);
$balance = $stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - SwiftPay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include_once 'php/navbar.php'; ?>

<div class="container mt-4">
    <h1 class="mb-4">Welcome, <?= htmlspecialchars($user['Username']) ?></h1>
    <p>Your account ID: <?= $_SESSION['user_id'] ?></p>
    <p>Balance: ₱<?= number_format($balance,2) ?></p>
    <div class="mt-4">
        <a class="btn btn-primary me-2" href="deposit.php">Deposit Funds</a>
        <a class="btn btn-secondary" href="settings.php">Settings</a>
    </div>
<script src="js/script.js"></script>
</body>
</html>