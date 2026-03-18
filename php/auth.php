<?php
require_once 'config.php';

function getUserById($id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM Users WHERE UserID = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getUserByUsername($username) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM Users WHERE Username = ?');
    $stmt->execute([$username]);
    return $stmt->fetch();
}

function verifyPasscode($userId, $passcode) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT Passcode FROM Users WHERE UserID = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!$row) {
        return false;
    }
    return $row['Passcode'] === $passcode; // plaintext for simplicity
}

function verifyPassword($userId, $password) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT Password FROM Users WHERE UserID = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!$row) {
        return false;
    }
    return $row['Password'] === $password;
}

function createUser($username, $password, $passcode) {
    global $pdo;
    $stmt = $pdo->prepare('INSERT INTO Users (Username, Password, Passcode) VALUES (?,?,?)');
    $stmt->execute([$username, $password, $passcode]);
    return $pdo->lastInsertId();
}

function createAccountForUser($userId, $initialBalance = 0) {
    global $pdo;
    $stmt = $pdo->prepare('INSERT INTO Accounts (UserID, Balance) VALUES (?,?)');
    $stmt->execute([$userId, $initialBalance]);
    return $pdo->lastInsertId();
}

function updatePassword($userId, $newPassword) {
    global $pdo;
    $stmt = $pdo->prepare('UPDATE Users SET Password = ? WHERE UserID = ?');
    $stmt->execute([$newPassword, $userId]);
}

function updatePasscode($userId, $newPasscode) {
    global $pdo;
    $stmt = $pdo->prepare('UPDATE Users SET Passcode = ? WHERE UserID = ?');
    $stmt->execute([$newPasscode, $userId]);
}
?>