<?php
require_once __DIR__ . '/../db/db.php';

// Получаем список квестов
$games = $db->query("SELECT id, name FROM games ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Если выбран квест — загружаем его точки
$gameId = $_GET['game_id'] ?? null;
$locations = [];
if ($gameId) {
    $stmt = $db->prepare("SELECT * FROM game_locations WHERE game_id = ? ORDER BY checkpoint_order ASC");
    $stmt->execute([$gameId]);
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление локациями</title>
    <link href="https://cdn.tailwindcss.com" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
<div class="max-w-3xl mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-2xl font-bold mb-6">📍 Управление локациями</h1>

    <!-- Выбор квеста -->
    <form method="get" class="mb-4">
        <label class="block mb-2 text-sm font-medium text-gray-700">Выберите квест:</label>
        <select name="game_id" class="p-2 border rounded w-full" onchange="this.form.submit()">
            <option value="">-- выберите --</option>
            <?php foreach ($games as $game): ?>
                <option value="<?= $game['id'] ?>" <?= $gameId == $game['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($game['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($gameId): ?>
        <!-- Добавление новой точки -->
        <form action="save_location.php" method="post" class="bg-gray-50 p-4 rounded mb-6">
            <input type="hidden" name="game_id" value="<?= $gameId ?>">
            <div class="grid grid-cols-2 gap-4 mb-4">
                <input type="text" name="latitude" placeholder="Широта" required class="p-2 border rounded">
                <input type="text" name="longitude" placeholder="Долгота" required class="p-2 border rounded">
                <input type="number" name="radius" placeholder="Радиус (м)" value="100" class="p-2 border rounded">
                <input type="number" name="checkpoint_order" placeholder="Порядок" required class="p-2 border rounded">
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">➕ Добавить точку</button>
        </form>

        <!-- Таблица точек -->
        <table class="w-full table-auto border text-sm">
            <thead>
            <tr class="bg-gray-200 text-left">
                <th class="p-2">#</th>
                <th class="p-2">Координаты</th>
                <th class="p-2">Радиус</th>
                <th class="p-2">Порядок</th>
                <th class="p-2">Удалить</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($locations as $loc): ?>
                <tr class="border-t">
                    <td class="p-2"><?= $loc['id'] ?></td>
                    <td class="p-2"><?= $loc['latitude'] ?>, <?= $loc['longitude'] ?></td>
                    <td class="p-2"><?= $loc['radius'] ?> м</td>
                    <td class="p-2"><?= $loc['checkpoint_order'] ?></td>
                    <td class="p-2">
                        <a href="delete_location.php?id=<?= $loc['id'] ?>&game_id=<?= $gameId ?>"
                           onclick="return confirm('Удалить точку?')"
                           class="text-red-600 hover:underline">Удалить</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
