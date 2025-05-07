<?php
session_start();
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/includes/functions.php';

// Логируем всё
file_put_contents(__DIR__ . '/logs/upload_debug.txt', print_r($_POST, true) . "\n\n" . print_r($_FILES, true), FILE_APPEND);

$teamId = $_SESSION['team_id'] ?? null;
if (!$teamId) {
    echo json_encode(['success' => false, 'error' => 'Вы не авторизованы']);
    exit;
}

$gameId = (int)($_POST['game_id'] ?? 0);
$checkpointIndex = (int)($_POST['checkpoint_index'] ?? -1);
$extraIndex = isset($_POST['extra_index']) ? (int)$_POST['extra_index'] : null;

if (!$gameId || $checkpointIndex < 0 || $extraIndex === null) {
    echo json_encode(['success' => false, 'error' => 'Некорректные данные']);
    exit;
}

$game = getGame($db, $gameId);
if (!$game) {
    echo json_encode(['success' => false, 'error' => 'Квест не найден']);
    exit;
}

$checkpoints = json_decode($game['checkpoints'], true);
$step = $checkpoints[$checkpointIndex] ?? null;
$extra = $step['additional_questions'][$extraIndex] ?? null;

if (!$step || !$extra) {
    echo json_encode(['success' => false, 'error' => 'Вопрос не найден']);
    exit;
}

$progress = getProgress($db, $teamId, $gameId);
$checkpointKey = "checkpoint_$checkpointIndex";
$now = date('Y-m-d H:i:s');

if (!isset($progress[$checkpointKey])) {
    $progress[$checkpointKey] = [];
}
if (!isset($progress[$checkpointKey]['extra_answers'])) {
    $progress[$checkpointKey]['extra_answers'] = [];
}

// ФОТО-вопрос
if ($extra['type'] === 'photo') {
    if (!isset($_FILES['answer']) || $_FILES['answer']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Файл не загружен']);
        exit;
    }

    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $originalName = pathinfo($_FILES['answer']['name'], PATHINFO_FILENAME);
    $extension = strtolower(pathinfo($_FILES['answer']['name'], PATHINFO_EXTENSION));
    $filename = $originalName . '.' . $extension;
    $counter = 1;

    // Уникальное имя
    while (file_exists($uploadDir . $filename)) {
        $filename = $originalName . '-' . $counter . '.' . $extension;
        $counter++;
    }

    if (!move_uploaded_file($_FILES['answer']['tmp_name'], $uploadDir . $filename)) {
        echo json_encode(['success' => false, 'error' => 'Ошибка при сохранении файла']);
        exit;
    }

    $progress[$checkpointKey]['extra_answers'][$extraIndex] = [
        'answer' => 'Фото загружено',
        'filename' => $filename,
        'answer_time' => $now
    ];

    saveProgress($db, $teamId, $gameId, $progress);
    echo json_encode(['success' => true, 'message' => '✅ Фото принято: ' . $filename]);
    exit;
}

// ТЕКСТ-вопрос
$given = trim($_POST['answer'] ?? '');
if ($given === '') {
    echo json_encode(['success' => false, 'error' => 'Введите ответ']);
    exit;
}

$expected = trim($extra['answer'] ?? '');
$correct = !$expected || mb_strtolower($expected) === mb_strtolower($given);

if (!$correct) {
    echo json_encode(['success' => false, 'error' => 'Неверный ответ']);
    exit;
}

$progress[$checkpointKey]['extra_answers'][$extraIndex] = [
    'answer' => $given,
    'answer_time' => $now
];

saveProgress($db, $teamId, $gameId, $progress);
echo json_encode(['success' => true, 'message' => '✅ Ответ принят']);
