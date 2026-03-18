<?php
session_start();
require_once 'config.php';
require_once 'auth.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header('Location: ../login.php');
    exit;
}

// process submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiver = intval($_POST['receiver']);
    $amount   = floatval($_POST['amount']);
    $pass     = $_POST['passcode'] ?? '';

    if (!verifyPasscode($userId, $pass)) {
        echo "<p style='color:red'>Invalid passcode</p>";
    } else {
        $stmt = $pdo->prepare('CALL ProcessTransferWithSafety(?,?,?,@msg)');
        $stmt->execute([$userId, $receiver, $amount]);
        $msg = $pdo->query('SELECT @msg')->fetchColumn();
        echo "<p>$msg</p>";
    }
}

// form
?>
<!DOCTYPE html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <title>Fund Transfer</title>
</head>
<body>
<div class="container mt-5" style="max-width:500px;">
    <h2>Fund Transfer</h2>
    <form method="post" data-require-pass="true">
        <div class="mb-3">
            <label class="form-label" for="receiver">Recipient account ID</label>
            <input class="form-control" type="number" id="receiver" name="receiver" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="amount">Amount</label>
            <input class="form-control" type="number" step="0.01" id="amount" name="amount" required>
        </div>
        <button class="btn btn-success" type="submit">Send</button>
        <a href="../dashboard.php" class="btn btn-link">Back</a>
    </form>
</div>
<script src="../js/script.js"></script>
</body>
</html>