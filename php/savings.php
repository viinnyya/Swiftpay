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
    $action = $_POST['action'] ?? 'save';
    $bucket = $_POST['bucket'] ?? 'travel';
    $amt = floatval($_POST['amount']);
    $pass = $_POST['passcode'] ?? '';

    if (!verifyPasscode($userId, $pass)) {
        echo "<p style='color:red'>Invalid passcode</p>";
    } elseif ($amt <= 0) {
        echo "<p style='color:red'>Amount must be greater than 0</p>";
    } else {
        $field = ($bucket === 'travel' ? 'Travel' : ($bucket === 'tuition' ? 'Tuition' : 'Emergency'));
        try {
            $pdo->beginTransaction();

            if ($action === 'withdraw') {
                $current = $pdo->prepare("SELECT $field FROM Savings WHERE AccountID = ?");
                $current->execute([$accId]);
                $currentValue = floatval($current->fetchColumn());

                if ($currentValue < $amt) {
                    throw new Exception('Transaction Failed: Not enough savings in ' . $field);
                }

                $pdo->exec("UPDATE Savings SET $field = $field - $amt WHERE AccountID = $accId");
                $pdo->exec("UPDATE Accounts SET Balance = Balance + $amt WHERE AccountID = $accId");
                echo "<p>Moved ₱$amt from $field savings to main wallet.</p>";
            } else {
                $balanceQuery = $pdo->prepare('SELECT Balance FROM Accounts WHERE AccountID = ?');
                $balanceQuery->execute([$accId]);
                $mainBalance = floatval($balanceQuery->fetchColumn());

                if ($mainBalance < $amt) {
                    throw new Exception('Transaction Failed: Insufficient funds in main wallet');
                }

                $pdo->exec("UPDATE Accounts SET Balance = Balance - $amt WHERE AccountID = $accId");
                $pdo->exec("UPDATE Savings SET $field = $field + $amt WHERE AccountID = $accId");
                echo "<p>Added ₱$amt to $field savings.</p>";
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
    <h3>Allocate funds</h3>
    <form method="post" data-require-pass="true">
        <div class="mb-3">
            <label class="form-label" for="action">Action</label>
            <select class="form-select" id="action" name="action">
                <option value="save">Add to savings (main → bucket)</option>
                <option value="withdraw">Withdraw to main wallet (bucket → main)</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label" for="bucket">Savings bucket</label>
            <select class="form-select" id="bucket" name="bucket">
                <option value="travel">Travel</option>
                <option value="tuition">Tuition</option>
                <option value="emergency">Emergency</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label" for="amount">Amount</label>
            <input class="form-control" type="number" step="0.01" name="amount" id="amount" required>
        </div>
        <button class="btn btn-success" type="submit">Submit</button>
        <a href="../dashboard.php" class="btn btn-link">Back</a>
    </form>
</div>
<script src="../js/script.js"></script>
</body></html>