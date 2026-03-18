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
    $plan   = intval($_POST['plan']);
    $pass   = $_POST['passcode'] ?? '';
    if (!verifyPasscode($userId, $pass)) {
        echo "<p style='color:red'>Invalid passcode</p>";
    } else {
        // similar to bills: deduct cost and log as transaction
        $stmt = $pdo->prepare('SELECT Cost, Network, Description FROM LoadPlans WHERE PlanID = ?');
        $stmt->execute([$plan]);
        $info = $stmt->fetch();
        if ($info) {
            try {
                $pdo->beginTransaction();
                $pdo->exec('UPDATE Accounts SET Balance = Balance - ' . $info['Cost'] . ' WHERE UserID = ' . $userId);
                $accId = $pdo->query('SELECT AccountID FROM Accounts WHERE USERID='.$userId)->fetchColumn();
                // get provider account
                $stmtp = $pdo->prepare('SELECT AccountID FROM LoadPlans WHERE PlanID = ?');
                $stmtp->execute([$plan]);
                $rid = $stmtp->fetchColumn();
                $pdo->exec("INSERT INTO TransactionLogs (SenderID, ReceiverID, Amount, Timestamp) VALUES ($accId,$rid,{$info['Cost']},NOW())");
                $pdo->commit();
                echo "<p>Purchased {$info['Description']} ({$info['Network']})</p>";
            } catch (Exception $e) {
                $pdo->rollBack();
                echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
            }
        }
    }
}

$plans = $pdo->query('SELECT PlanID, Network, Cost, Description FROM LoadPlans')->fetchAll();
?>
<!DOCTYPE html>
<html><head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <title>Buy Load</title>
</head><body>
<div class="container mt-5" style="max-width:500px;">
    <h2>Buy Load</h2>
    <form method="post" data-require-pass="true">
        <div class="mb-3">
            <label class="form-label" for="plan">Select plan</label>
            <select class="form-select" name="plan" id="plan" required>
                <?php foreach ($plans as $p): ?>
                    <option value="<?= $p['PlanID'] ?>"><?= htmlspecialchars($p['Network'].' - '.$p['Description'].' (₱'.$p['Cost'].')') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-success" type="submit">Buy</button>
        <a href="../dashboard.php" class="btn btn-link">Back</a>
    </form>
</div>
<script src="../js/script.js"></script>
</body></html>