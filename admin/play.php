<?php
require_once __DIR__ . '/../db/db.php';

// 1. Получение команды и квеста
$teamId = $_GET['team_id'] ?? 1;
$gameId = $_GET['game_id'] ?? 1;

$stmt = $db->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$gameId]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);
$checkpoints = json_decode($game['checkpoints'] ?? '[]', true);

// получаем team_game
$stmt = $db->prepare("SELECT * FROM team_games WHERE team_id = ? AND game_id = ?");
$stmt->execute([$teamId, $gameId]);
$teamGame = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$teamGame) {
    // создаём запись, если не существует
    $stmt = $db->prepare("INSERT INTO team_games (team_id, game_id, status, is_paid, answers) VALUES (?, ?, 'active', 0, '{}')");
    $stmt->execute([$teamId, $gameId]);
    $teamGame = [
        'id' => $db->lastInsertId(),
        'answers' => '{}'
    ];
}

$answers = json_decode($teamGame['answers'] ?? '{}', true);
$currentStep = 0;

// найти первый непройденный шаг
foreach ($checkpoints as $i => $cp) {
    if (!isset($answers[$i])) {
        $currentStep = $i;
        break;
    }
}
$isFinished = $currentStep >= count($checkpoints);

// Обработка ответа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answer = trim($_POST['answer']);
    $answers[$currentStep] = $answer;
    $stmt = $db->prepare("UPDATE team_games SET answers = ? WHERE id = ?");
    $stmt->execute([json_encode($answers, JSON_UNESCAPED_UNICODE), $teamGame['id']]);
    header("Location: play.php?team_id={$teamId}&game_id={$gameId}");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Прохождение квеста</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white text-gray-900 p-6 font-sans">
<div class="max-w-xl mx-auto">
    <h1 class="text-2xl font-bold mb-4">▶️ Квест: <?= htmlspecialchars($game['name']) ?></h1>
    <p class="mb-4 text-gray-600">Команда #<?= $teamId ?> — шаг <?= $isFinished ? count($checkpoints) : $currentStep + 1 ?> из <?= count($checkpoints) ?></p>

    <?php if ($isFinished): ?>
        <div class="p-6 bg-green-100 text-green-800 rounded">✅ Все шаги пройдены. Спасибо!</div>
    <?php else: ?>
        <?php $cp = $checkpoints[$currentStep]; ?>
        <div class="mb-4 p-4 bg-gray-100 rounded shadow">
            <h2 class="font-semibold mb-2"><?= htmlspecialchars($cp['title']) ?></h2>
            <p class="mb-2"><?= htmlspecialchars($cp['question']) ?></p>
            <form method="post">
                <input name="answer" required placeholder="Введите ответ" class="w-full p-2 border rounded mb-3">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">📤 Отправить</button>
            </form>
        </div>
    <?php endif; ?>

    <a href="index.php" class="text-blue-600 hover:underline">← Назад</a>
</div>
</body>
</html>
