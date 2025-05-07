<?php
session_start();
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

$teamId = $_SESSION['team_id'] ?? null;
if (!$teamId) {
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

$gameId = (int)($_POST['game_id'] ?? 0);
$checkpointIndex = (int)($_POST['checkpoint_index'] ?? 0);
$userAnswer = trim($_POST['answer'] ?? '');

$game = getGame($db, $gameId);
if (!$game) {
    echo json_encode(['success' => false, 'error' => 'Квест не найден']);
    exit;
}

$checkpoints = json_decode($game['checkpoints'], true);
if (!isset($checkpoints[$checkpointIndex])) {
    echo json_encode(['success' => false, 'error' => 'Шаг не найден']);
    exit;
}

$cp = $checkpoints[$checkpointIndex];
$validAnswers = array_map('mb_strtolower', array_map('trim', explode('|', $cp['answers'])));

if (!in_array(mb_strtolower($userAnswer), $validAnswers)) {
    echo json_encode(['success' => false, 'error' => 'Неверный ответ']);
    exit;
}

// сохранить прогресс
$key = "checkpoint_$checkpointIndex";
$progress = getProgress($db, $teamId, $gameId) ?? [];
$progress[$key]['main_answer'] = $userAnswer;

saveProgress($db, $teamId, $gameId, $progress);

echo json_encode(['success' => true, 'message' => '✅ Ответ принят']);
