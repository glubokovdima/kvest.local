<?php
session_start();
require_once __DIR__ . '/db/db.php';

$mode = $_POST['mode'] ?? 'login';
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$teamName = trim($_POST['team_name'] ?? '');

if (!$email || !$password) {
    die('Email и пароль обязательны');
}

if ($mode === 'register') {
    if (!$phone) {
        die('Номер телефона обязателен при регистрации');
    }

    // Проверка: уже есть такой email или номер телефона?
    $stmt = $db->prepare("SELECT id FROM teams WHERE email = ? OR phone = ?");
    $stmt->execute([$email, $phone]);
    if ($stmt->fetch()) {
        die('Этот email или номер телефона уже зарегистрирован');
    }

    // Создание команды
    $stmt = $db->prepare("INSERT INTO teams (email, password, phone, start_time) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $email,
        password_hash($password, PASSWORD_DEFAULT),
        $phone,
        date('Y-m-d H:i:s')
    ]);

    $_SESSION['team_id'] = $db->lastInsertId();
    header("Location: dashboard.php");
    exit;
}

if ($mode === 'login') {
    // Поиск команды
    $stmt = $db->prepare("SELECT id, password FROM teams WHERE email = ?");
    $stmt->execute([$email]);
    $team = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$team || !password_verify($password, $team['password'])) {
        die('Неверный email или пароль');
    }

    $_SESSION['team_id'] = $team['id'];
    header("Location: dashboard.php");
    exit;
}
