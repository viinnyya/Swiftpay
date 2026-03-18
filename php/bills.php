<?php
session_start();
require_once 'config.php';
require_once 'auth.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $biller = intval($_POST['biller']);
    $amount = floatval($_POST['amount']);
    $pass   = $_POST['passcode'] ?? '';
    if (!verifyPasscode($userId, $pass)) {
        echo "<p style='color:red'>Invalid passcode</p>";
    } else {
        // subtract from user balance and log transaction to the biller account
        try {
            $pdo->beginTransaction();
            // deduct from user
            $stmt = $pdo->prepare('UPDATE Accounts SET Balance=Balance-? WHERE UserID=?');
            $stmt->execute([$amount,$userId]);
            $accId = $pdo->query('SELECT AccountID FROM Accounts WHERE UserID='.$userId)->fetchColumn();
            // find biller account
            $stmt2 = $pdo->prepare('SELECT AccountID FROM Billers WHERE BillerID = ?');
            $stmt2->execute([$biller]);
            $rid = $stmt2->fetchColumn();
            // log transaction involving real receiver
            $stmt = $pdo->prepare('INSERT INTO TransactionLogs (SenderID, ReceiverID, Amount, Timestamp) VALUES (?,?,?,NOW())');
            $stmt->execute([$accId, $rid, $amount]);
            $pdo->commit();
            echo "<p>Bills payment successful</p>";
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
        }
    }
}

// fetch billers for dropdown
$billers = $pdo->query('SELECT BillerID, Name FROM Billers')->fetchAll();
?>
<!DOCTYPE html>
<html><head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <title>Bills Payment</title>
</head><body>
<div class="container mt-5" style="max-width:500px;">
    <h2>Bills Payment</h2>
    <form method="post" data-require-pass="true">
        <div class="mb-3">
            <label class="form-label" for="biller">Choose biller</label>
            <select class="form-select" name="biller" id="biller" required>
                <?php foreach ($billers as $b) : ?>
                    <option value="<?= $b['BillerID'] ?>"><?= htmlspecialchars($b['Name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label" for="amount">Amount</label>
            <input class="form-control" type="number" step="0.01" name="amount" id="amount" required>
        </div>
        <button class="btn btn-success" type="submit">Pay</button>
        <a href="../dashboard.php" class="btn btn-link">Back</a>
    </form>
</div>
<script src="../js/script.js"></script>
</body></html>