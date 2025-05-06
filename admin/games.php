<?php
require_once __DIR__ . '/../db/db.php';

// Обработка удаления
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $db->prepare("DELETE FROM games WHERE id = ?")->execute([$id]);
    header("Location: games.php");
    exit;
}

// Получаем список квестов
$games = $db->query("SELECT * FROM games ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Квесты</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900 p-6 font-sans">
<div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold mb-4">🗺️ Квесты</h1>
    <a href="games_edit.php" class="mb-4 inline-block bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">➕ Добавить квест</a>

    <div class="space-y-4 mt-4">
        <?php foreach ($games as $game): ?>
            <div class="bg-white shadow p-4 rounded">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-semibold"><?= htmlspecialchars($game['name']) ?></h2>
                        <p class="text-gray-600 text-sm"><?= htmlspecialchars($game['description']) ?></p>
                    </div>
                    <div class="space-x-2">
                        <a href="games_edit.php?id=<?= $game['id'] ?>" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">✏️</a>
                        <a href="games.php?delete=<?= $game['id'] ?>" onclick="return confirm('Удалить квест?')" class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700">🗑️</a>
                    </div>
                </div>
                <div class="text-sm text-gray-500 mt-1">Старт: <?= $game['start_time'] ?> — <?= $game['is_active'] ? '✅ Активен' : '❌ Неактивен' ?></div>
            </div>
        <?php endforeach; ?>
    </div
