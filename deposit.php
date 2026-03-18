<?php
session_start();
require_once 'php/config.php';
require_once 'php/auth.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $pass = $_POST['passcode'] ?? '';
    if (!verifyPasscode($userId, $pass)) {
        $msg = '<p class="text-danger">Invalid passcode</p>';
    } else {
        // add to balance and log as deposit (receiver self)
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('UPDATE Accounts SET Balance = Balance + ? WHERE UserID = ?');
            $stmt->execute([$amount, $userId]);
            $accId = $pdo->query('SELECT AccountID FROM Accounts WHERE UserID='.$userId)->fetchColumn();
            // sender is NULL for external deposit
            $stmt = $pdo->prepare('INSERT INTO TransactionLogs (SenderID, ReceiverID, Amount, Timestamp) VALUES (NULL,?,?,NOW())');
            $stmt->execute([$accId, $amount]);
            $pdo->commit();
            $msg = '<p class="text-success">Deposit successful</p>';
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = '<p class="text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <title>Deposit Funds</title>
</head>
<body>
<div class="container mt-5" style="max-width:500px;">
    <h2>Deposit Funds</h2>
    <?= $msg ?? '' ?>
    <form method="post" data-require-pass="true">
        <div class="mb-3">
            <label class="form-label" for="amount">Amount</label>
            <input class="form-control" type="number" step="0.01" name="amount" id="amount" required>
        </div>
        <button class="btn btn-success" type="submit">Deposit</button>
        <a href="dashboard.php" class="btn btn-link">Back</a>
    </form>
</div>
<script src="js/script.js"></script>
</body>
</html>