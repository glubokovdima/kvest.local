<?php
session_start();
require_once __DIR__ . '/db/db.php';

if (!isset($_SESSION['team_id'])) {
    header('Location: index.php');
    exit;
}

$teamId = $_SESSION['team_id'];
$gameId = isset($_GET['game_id']) ? (int)$_GET['game_id'] : 0;

// Проверка, что квест существует
$stmt = $db->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$gameId]);
$game = $stmt->fetch();

if (!$game) {
    die('Квест не найден');
}

// Проверка, есть ли уже регистрация на этот квест
$stmt = $db->prepare("SELECT * FROM team_games WHERE team_id = ? AND game_id = ?");
$stmt->execute([$teamId, $gameId]);
$existing = $stmt->fetch();

if ($existing) {
    header("Location: dashboard.php");
    exit;
}

// Получаем следующий номер участника для данного квеста
$stmt = $db->prepare("SELECT COUNT(*) as total FROM team_games WHERE game_id = ?");
$stmt->execute([$gameId]);
$nextNumber = $stmt->fetchColumn() + 1; // Номер участника начинается с 1

// Регистрируем команду в квесте
$stmt = $db->prepare("INSERT INTO team_games (team_id, game_id, status, is_paid, answers, team_number) VALUES (?, ?, 'active', 0, '{}', ?)");
$stmt->execute([$teamId, $gameId, $nextNumber]);

// Перенаправляем в личный кабинет
header("Location: dashboard.php");
exit;
