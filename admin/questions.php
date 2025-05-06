<?php
require_once __DIR__ . '/../db/db.php';

// Получаем список квестов
$games = $db->query("SELECT id, name FROM games ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$gameId = $_GET['game_id'] ?? null;

$locations = [];
if ($gameId) {
    $stmt = $db->prepare("SELECT * FROM game_locations WHERE game_id = ? ORDER BY checkpoint_order ASC");
    $stmt->execute([$gameId]);
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getQuestion($locationId, $db) {
    $stmt = $db->prepare("SELECT * FROM game_questions WHERE location_id = ?");
    $stmt->execute([$locationId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вопросы к локациям</title>
    <link href="https://cdn.tailwindcss.com" rel="stylesheet">
</head>
<body class="bg-gray-100 p-6">
<div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-2xl font-bold mb-6">❓ Вопросы по точкам квеста</h1>

    <!-- Выбор квеста -->
    <form method="get" class="mb-6">
        <label class="block text-sm mb-2 font-medium">Выберите квест:</label>
        <select name="game_id" class="w-full p-2 border rounded" onchange="this.form.submit()">
            <option value="">-- выбрать --</option>
            <?php foreach ($games as $game): ?>
                <option value="<?= $game['id'] ?>" <?= $gameId == $game['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($game['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($gameId): ?>
        <?php foreach ($locations as $loc):
            $questions = getQuestion($loc['id'], $db);
            $main = $questions[0] ?? null;
            $extras = array_slice($questions, 1);
            ?>
            <div class="border-t pt-6 mt-6">
                <h2 class="text-xl font-semibold mb-2">📍 Точка <?= $loc['checkpoint_order'] ?> — <?= $loc['latitude'] ?>, <?= $loc['longitude'] ?></h2>

                <!-- Основной вопрос -->
                <form action="save_question.php" method="post" class="mb-4">
                    <input type="hidden" name="location_id" value="<?= $loc['id'] ?>">
                    <label class="block mb-1 text-sm font-medium">Основной вопрос:</label>
                    <input type="text" name="question" required class="w-full p-2 border rounded mb-2" value="<?= htmlspecialchars($main['question'] ?? '') ?>">

                    <label class="block mb-1 text-sm font-medium">Правильный ответ:</label>
                    <input type="text" name="correct_answer" required class="w-full p-2 border rounded mb-2" value="<?= htmlspecialchars($main['correct_answer'] ?? '') ?>">

                    <label class="block mb-1 text-sm font-medium">Подсказка:</label>
                    <input type="text" name="hint" class="w-full p-2 border rounded mb-2" value="<?= htmlspecialchars($main['hint'] ?? '') ?>">

                    <button class="bg-green-600 text-white px-4 py-2 rounded" type="submit">💾 Сохранить</button>
                </form>

                <!-- Доп. вопросы -->
                <h3 class="text-md font-medium mb-2">Дополнительные вопросы:</h3>
                <?php foreach ($extras as $extra): ?>
                    <div class="bg-gray-50 p-3 rounded mb-2 text-sm">
                        <strong><?= $extra['type'] === 'photo' ? '📷 Фото' : '💬 Текст' ?>:</strong>
                        <?= htmlspecialchars($extra['question'] ?? '') ?>
                        <?= $extra['image'] ? '<br><img src="' . htmlspecialchars($extra['image']) . '" class="mt-2 max-w-xs rounded">' : '' ?>
                    </div>
                <?php endforeach; ?>

                <form action="add_extra.php" method="post" enctype="multipart/form-data" class="mt-3 bg-gray-50 p-3 rounded">
                    <input type="hidden" name="location_id" value="<?= $loc['id'] ?>">
                    <label class="block text-sm mb-1">Тип:</label>
                    <select name="type" class="w-full p-2 border rounded mb-2">
                        <option value="text">💬 Текст</option>
                        <option value="photo">📷 Фото</option>
                    </select>

                    <label class="block text-sm mb-1">Вопрос:</label>
                    <input type="text" name="question" class="w-full p-2 border rounded mb-2">

                    <label class="block text-sm mb-1">Картинка:</label>
                    <input type="file" name="image" class="w-full p-2 border rounded mb-2">

                    <button class="bg-blue-600 text-white px-4 py-2 rounded" type="submit">➕ Добавить</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
