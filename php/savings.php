<?php
session_start();
require_once 'config.php';
require_once 'auth.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header('Location: ../login.php');
    exit;
}

// load account id and savings record
$accId = $pdo->query('SELECT AccountID FROM Accounts WHERE UserID='.$userId)->fetchColumn();
$sav = $pdo->prepare('SELECT * FROM Savings WHERE AccountID = ?');
$sav->execute([$accId]);
$sav = $sav->fetch();
if (!$sav) {
    // initialize
    $pdo->prepare('INSERT INTO Savings (AccountID) VALUES (?)')->execute([$accId]);
    $sav = ['Travel'=>0,'Tuition'=>0,'Emergency'=>0];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $from = $_POST['from'] ?? 'main';
    $to = $_POST['to'] ?? 'travel';
    $amt = floatval($_POST['amount']);
    $pass = $_POST['passcode'] ?? '';

    $validSources = ['main','travel','tuition','emergency'];

    if (!verifyPasscode($userId, $pass)) {
        echo "<p style='color:red'>Invalid passcode</p>";
    } elseif ($amt <= 0) {
        echo "<p style='color:red'>Amount must be greater than 0</p>";
    } elseif (!in_array($from, $validSources, true) || !in_array($to, $validSources, true)) {
        echo "<p style='color:red'>Invalid transfer source or destination</p>";
    } elseif ($from === $to) {
        echo "<p style='color:red'>Source and destination cannot be the same</p>";
    } else {
        try {
            $pdo->beginTransaction();

            if ($from === 'main') {
                $balanceQuery = $pdo->prepare('SELECT Balance FROM Accounts WHERE AccountID = ?');
                $balanceQuery->execute([$accId]);
                $mainBalance = floatval($balanceQuery->fetchColumn());

                if ($mainBalance < $amt) {
                    throw new Exception('Transaction Failed: Insufficient funds in main wallet');
                }

                $pdo->exec("UPDATE Accounts SET Balance = Balance - $amt WHERE AccountID = $accId");
                $toField = ucfirst($to);
                $pdo->exec("UPDATE Savings SET $toField = $toField + $amt WHERE AccountID = $accId");
                echo "<p>Transferred ₱$amt from main wallet to $toField savings.</p>";
            } elseif ($to === 'main') {
                $fromField = ucfirst($from);
                $fromQuery = $pdo->prepare("SELECT $fromField FROM Savings WHERE AccountID = ?");
                $fromQuery->execute([$accId]);
                $fromBalance = floatval($fromQuery->fetchColumn());

                if ($fromBalance < $amt) {
                    throw new Exception('Transaction Failed: Insufficient funds in ' . $fromField . ' savings');
                }

                $pdo->exec("UPDATE Savings SET $fromField = $fromField - $amt WHERE AccountID = $accId");
                $pdo->exec("UPDATE Accounts SET Balance = Balance + $amt WHERE AccountID = $accId");
                echo "<p>Transferred ₱$amt from $fromField savings to main wallet.</p>";
            } else {
                $fromField = ucfirst($from);
                $toField = ucfirst($to);
                $fromQuery = $pdo->prepare("SELECT $fromField FROM Savings WHERE AccountID = ?");
                $fromQuery->execute([$accId]);
                $fromBalance = floatval($fromQuery->fetchColumn());

                if ($fromBalance < $amt) {
                    throw new Exception('Transaction Failed: Insufficient funds in ' . $fromField . ' savings');
                }

                $pdo->exec("UPDATE Savings SET $fromField = $fromField - $amt, $toField = $toField + $amt WHERE AccountID = $accId");
                echo "<p>Transferred ₱$amt from $fromField savings to $toField savings.</p>";
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<p style='color:red'>" . $e->getMessage() . "</p>";
        }
    }
}

// recompute totals
$sav = $pdo->query("SELECT Travel, Tuition, Emergency FROM Savings WHERE AccountID=$accId")->fetch();
$total = $sav['Travel']+$sav['Tuition']+$sav['Emergency'];
$mainBalance = $pdo->query("SELECT Balance FROM Accounts WHERE AccountID=$accId")->fetchColumn();
?>
<!DOCTYPE html>
<html><head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <title>Savings</title>
</head><body>
<div class="container mt-5" style="max-width:600px;">
    <h2>Savings</h2>
    <p>Main wallet balance: ₱<?= number_format($mainBalance,2) ?></p>
    <p>Total savings: ₱<?= number_format($total,2) ?></p>
    <ul class="list-group mb-4">
        <li class="list-group-item">Travel: ₱<?= number_format($sav['Travel'],2) ?></li>
        <li class="list-group-item">Tuition: ₱<?= number_format($sav['Tuition'],2) ?></li>
        <li class="list-group-item">Emergency: ₱<?= number_format($sav['Emergency'],2) ?></li>
    </ul>
    <h3>Transfer funds</h3>
    <form method="post" data-require-pass="true">
        <div class="mb-3">
            <label class="form-label" for="from">From</label>
            <select class="form-select" id="from" name="from">
                <option value="main">Main wallet</option>
                <option value="travel">Savings: Travel</option>
                <option value="tuition">Savings: Tuition</option>
                <option value="emergency">Savings: Emergency</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label" for="to">To</label>
            <select class="form-select" id="to" name="to">
                <option value="main">Main wallet</option>
                <option value="travel">Savings: Travel</option>
                <option value="tuition">Savings: Tuition</option>
                <option value="emergency">Savings: Emergency</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label" for="amount">Amount</label>
            <input class="form-control" type="number" step="0.01" name="amount" id="amount" required>
        </div>
        <button class="btn btn-success" type="submit">Transfer</button>
        <a href="../dashboard.php" class="btn btn-link">Back</a>
    </form>
</div>
<script src="../js/script.js"></script>
</body></html>