<?php
session_start();
require_once 'php/config.php';
require_once 'php/auth.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header('Location: login.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        if (!verifyPassword($userId, $current)) {
            $message = '<div class="alert alert-danger">Current password incorrect.</div>';
        } elseif ($new !== $confirm) {
            $message = '<div class="alert alert-danger">New passwords do not match.</div>';
        } else {
            updatePassword($userId, $new);
            $message = '<div class="alert alert-success">Password updated.</div>';
        }
    } elseif (isset($_POST['change_passcode'])) {
        $current = $_POST['current_passcode'];
        $new = $_POST['new_passcode'];
        $confirm = $_POST['confirm_passcode'];
        if (!verifyPasscode($userId, $current)) {
            $message = '<div class="alert alert-danger">Current passcode incorrect.</div>';
        } elseif ($new !== $confirm) {
            $message = '<div class="alert alert-danger">New passcodes do not match.</div>';
        } else {
            updatePasscode($userId, $new);
            $message = '<div class="alert alert-success">Passcode updated.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <title>Settings</title>
</head>
<body>
<div class="container mt-5" style="max-width:600px;">
    <h2>Account Settings</h2>
    <?= $message ?>

    <h4>Change password</h4>
    <form method="post">
        <input type="hidden" name="change_password" value="1">
        <div class="mb-3">
            <label class="form-label" for="current_password">Current password</label>
            <input class="form-control" type="password" name="current_password" id="current_password" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="new_password">New password</label>
            <input class="form-control" type="password" name="new_password" id="new_password" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="confirm_password">Confirm new</label>
            <input class="form-control" type="password" name="confirm_password" id="confirm_password" required>
        </div>
        <button class="btn btn-primary" type="submit">Update password</button>
    </form>

    <hr>
    <h4>Change transaction passcode</h4>
    <form method="post">
        <input type="hidden" name="change_passcode" value="1">
        <div class="mb-3">
            <label class="form-label" for="current_passcode">Current passcode</label>
            <input class="form-control" type="password" name="current_passcode" id="current_passcode" required maxlength="10">
        </div>
        <div class="mb-3">
            <label class="form-label" for="new_passcode">New passcode</label>
            <input class="form-control" type="password" name="new_passcode" id="new_passcode" required maxlength="10">
        </div>
        <div class="mb-3">
            <label class="form-label" for="confirm_passcode">Confirm new</label>
            <input class="form-control" type="password" name="confirm_passcode" id="confirm_passcode" required maxlength="10">
        </div>
        <button class="btn btn-primary" type="submit">Update passcode</button>
    </form>

    <a href="dashboard.php" class="btn btn-link mt-3">Back to dashboard</a>
</div>
</body>
</html>