<?php
session_start();
require_once __DIR__ . '/db/db.php';

// Загружаем квесты
$games = $db->query("SELECT * FROM games WHERE is_active = 1 ORDER BY start_time ASC")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Список квестов"; // Указываем название страницы

include('header.php');
?>

<!-- Основной контент страницы -->
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">🗺️ Доступные квесты</h1>
        <?php if (!isset($_SESSION['team_id'])): ?>
            <button onclick="document.getElementById('authModal').classList.remove('hidden')" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">Вход / Регистрация</button>
        <?php else: ?>
            <a href="dashboard.php" class="text-blue-600 hover:underline text-lg">Мой кабинет</a>
        <?php endif; ?>
    </div>

    <div class="space-y-6">
        <?php foreach ($games as $game): ?>
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($game['name']) ?></h2>
                <p class="text-sm text-gray-500 mb-3"><?= htmlspecialchars($game['start_time']) ?></p>
                <p class="text-gray-700 mb-4"><?= htmlspecialchars($game['description']) ?></p>

                <div class="flex justify-between items-center">
                    <?php if ($teamId && in_array($game['id'], $teamGames)): ?>
                        <a href="game-detail.php?game_id=<?= $game['id'] ?>" class="text-blue-600 hover:underline">Подробнее</a>
                    <?php else: ?>
                        <a href="<?= isset($_SESSION['team_id']) ? "join.php?game_id={$game['id']}" : '#' ?>" onclick="<?= !isset($_SESSION['team_id']) ? "document.getElementById('authModal').classList.remove('hidden'); return false;" : '' ?>" class="text-blue-600 hover:underline">Принять участие</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include('footer.php'); ?>
