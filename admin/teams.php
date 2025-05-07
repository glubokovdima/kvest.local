<?php
require_once __DIR__ . '/../db/db.php';

// 📤 Сохранение редактированного прогресса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['team_id'], $_POST['game_id'], $_POST['progress'])) {
    $teamId = (int)$_POST['team_id'];
    $gameId = (int)$_POST['game_id'];
    $progressJson = json_encode(json_decode($_POST['progress'], true), JSON_UNESCAPED_UNICODE);

    $stmt = $db->prepare("UPDATE team_progress SET progress = ? WHERE team_id = ? AND game_id = ?");
    $stmt->execute([$progressJson, $teamId, $gameId]);

    header("Location: ".$_SERVER['REQUEST_URI']);
    exit;
}

// 📥 Загрузка данных
$teams = $db->query("SELECT * FROM teams ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$teamGames = $db->query("SELECT * FROM team_games")->fetchAll(PDO::FETCH_ASSOC);
$games = $db->query("SELECT id, name FROM games")->fetchAll(PDO::FETCH_KEY_PAIR);
$progressRaw = $db->query("SELECT * FROM team_progress")->fetchAll(PDO::FETCH_ASSOC);

// Сопоставления
$teamGameMap = [];
foreach ($teamGames as $tg) {
    $teamGameMap[$tg['team_id']][] = $tg;
}

$progressMap = [];
foreach ($progressRaw as $p) {
    $progressMap[$p['team_id']][$p['game_id']] = $p['progress'];
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
                            <li class="mb-3">
                                <span class="font-medium"><?= htmlspecialchars($games[$g['game_id']] ?? '—') ?></span>
                                (<?= $g['status'] ?><?= $g['is_paid'] ? ', оплачено' : '' ?>)

                                <?php
                                $progressJson = $progressMap[$team['id']][$g['game_id']] ?? null;
                                if ($progressJson):
                                    $decoded = json_decode($progressJson, true);
                                    $pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                                    ?>
                                    <details class="ml-2 inline-block w-full">
                                        <summary class="cursor-pointer text-blue-600">Ответы (редактировать)</summary>
                                        <form method="POST" class="mt-1 bg-gray-50 p-2 rounded text-xs text-gray-700">
                                            <input type="hidden" name="team_id" value="<?= $team['id'] ?>">
                                            <input type="hidden" name="game_id" value="<?= $g['game_id'] ?>">
                                            <textarea name="progress" rows="10" class="w-full border p-1 rounded bg-white"><?= htmlspecialchars($pretty) ?></textarea>
                                            <div class="mt-2 text-right">
                                                <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">💾 Сохранить</button>
                                            </div>
                                        </form>
                                    </details>
                                <?php else: ?>
                                    <p class="text-gray-500 text-xs ml-2">Ответов пока нет</p>
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
