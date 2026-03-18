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
        // similar to bills: deduct cost and credit provider account
        $stmt = $pdo->prepare('SELECT Cost, Network, Description, AccountID FROM LoadPlans WHERE PlanID = ?');
        $stmt->execute([$plan]);
        $info = $stmt->fetch();
        if ($info) {
            try {
                $pdo->beginTransaction();

                $userAcc = $pdo->prepare('SELECT AccountID, Balance FROM Accounts WHERE UserID = ?');
                $userAcc->execute([$userId]);
                $userRow = $userAcc->fetch();
                if (!$userRow) {
                    throw new Exception('User account not found');
                }
                if ($userRow['Balance'] < $info['Cost']) {
                    throw new Exception('Transaction Failed: Insufficient funds');
                }

                $providerAccId = $info['AccountID'];
                if (!$providerAccId) {
                    throw new Exception('Provider account not configured');
                }

                $stmt1 = $pdo->prepare('UPDATE Accounts SET Balance = Balance - ? WHERE AccountID = ?');
                $stmt1->execute([$info['Cost'], $userRow['AccountID']]);

                $stmt2 = $pdo->prepare('UPDATE Accounts SET Balance = Balance + ? WHERE AccountID = ?');
                $stmt2->execute([$info['Cost'], $providerAccId]);

                $stmt3 = $pdo->prepare('INSERT INTO TransactionLogs (SenderID, ReceiverID, Amount, Timestamp) VALUES (?,?,?,NOW())');
                $stmt3->execute([$userRow['AccountID'], $providerAccId, $info['Cost']]);

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
<?php include_once '../php/navbar.php'; ?>
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