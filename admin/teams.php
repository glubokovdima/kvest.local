<?php
require_once __DIR__ . '/../db/db.php';

// Загружаем команды с их играми
$teams = $db->query("SELECT * FROM teams ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Загружаем все team_games
$teamGames = $db->query("SELECT * FROM team_games")->fetchAll(PDO::FETCH_ASSOC);

// Сопоставим игры
$games = $db->query("SELECT id, name FROM games")->fetchAll(PDO::FETCH_KEY_PAIR);

// Привязка team_games к командам
$teamGameMap = [];
foreach ($teamGames as $tg) {
    $teamGameMap[$tg['team_id']][] = $tg;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Команды</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6 font-sans text-gray-900">
<div class="max-w-5xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">👥 Команды</h1>

    <?php foreach ($teams as $team): ?>
        <div class="bg-white rounded shadow p-4 mb-6">
            <div class="flex justify-between items-center mb-2">
                <div>
                    <h2 class="text-xl font-semibold">Команда #<?= htmlspecialchars($team['team_number']) ?></h2>
                    <p class="text-gray-600 text-sm"><?= htmlspecialchars($team['email']) ?> — старт: <?= $team['start_time'] ?></p>
                </div>
            </div>

            <?php if (!empty($teamGameMap[$team['id']])): ?>
                <div class="mt-2">
                    <h3 class="font-semibold text-sm text-gray-700 mb-1">Участия:</h3>
                    <ul class="list-disc pl-6 text-sm text-gray-800">
                        <?php foreach ($teamGameMap[$team['id']] as $g): ?>
                            <li>
                                <span class="font-medium"><?= htmlspecialchars($games[$g['game_id']] ?? '—') ?></span>
                                (<?= $g['status'] ?><?= $g['is_paid'] ? ', оплачено' : '' ?>)

                                <?php if ($g['answers']): ?>
                                    <details class="ml-2 inline-block">
                                        <summary class="cursor-pointer text-blue-600">Ответы</summary>
                                        <div class="mt-1 bg-gray-50 p-2 rounded text-xs text-gray-700 whitespace-pre-wrap">
                                            <?= nl2br(htmlspecialchars(json_encode(json_decode($g['answers'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) ?>
                                        </div>
                                    </details>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <p class="text-sm text-gray-500 mt-2">Нет квестов.</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="mt-6">
        <a href="index.php" class="text-blue-600 hover:underline">&larr; Назад</a>
    </div>
</div>
</body>
</html>
