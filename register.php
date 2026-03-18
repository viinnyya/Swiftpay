<?php
session_start();
require_once 'php/config.php';
require_once 'php/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $passcode = $_POST['passcode'];
    if (getUserByUsername($username)) {
        $error = 'Username already taken';
    } else {
        $userId = createUser($username, $password, $passcode);
        createAccountForUser($userId, 0);
        $_SESSION['user_id'] = $userId;
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - SwiftPay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container mt-5" style="max-width:400px;">
    <h2>Register</h2>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label class="form-label" for="username">Username</label>
            <input class="form-control" type="text" name="username" id="username" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <input class="form-control" type="password" name="password" id="password" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="passcode">Transaction passcode</label>
            <input class="form-control" type="password" name="passcode" id="passcode" required maxlength="10">
        </div>
        <button class="btn btn-secondary" type="submit">Create account</button>
    </form>
    <p class="mt-3">Already have an account? <a href="login.php">Log in</a>.</p>
</div>
</body>
</html>