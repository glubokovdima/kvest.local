<?php
session_start();
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/includes/functions.php';

$result = handleJoin($db);
if ($result['success']) {
    header('Location: game.php?game_id=' . $_POST['game_id']);
    exit;
}

$_SESSION['error'] = $result['error'] ?? 'Ошибка регистрации на квест';
header('Location: index.php');
