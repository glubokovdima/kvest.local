<?php
session_start();
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/includes/functions.php';

$teamId = $_SESSION['team_id'] ?? null;

// Загружаем список активных квестов
$stmt = $db->query("SELECT * FROM games WHERE is_active = 1 ORDER BY start_time ASC");
$games = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем список game_id, в которых участвует команда
$teamGames = [];
if ($teamId) {
    $stmt = $db->prepare("SELECT game_id FROM team_games WHERE team_id = ?");
    $stmt->execute([$teamId]);
    $teamGames = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'game_id');
}

$pageTitle = "Список квестов";
include('header.php');
?>

<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">🗺️ Доступные квесты</h1>
    </div>

    <?php if (empty($games)): ?>
        <p class="text-gray-600">Квестов пока нет.</p>
    <?php endif; ?>

    <div class="space-y-6">
        <?php foreach ($games as $game): ?>
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($game['name']) ?></h2>
                <p class="text-sm text-gray-500 mb-3"><?= htmlspecialchars($game['start_time']) ?></p>
                <p class="text-gray-700 mb-4"><?= htmlspecialchars($game['description']) ?></p>

                <div class="flex justify-between items-center">
                    <?php if ($teamId && in_array($game['id'], $teamGames)): ?>
                        <a href="game.php?game_id=<?= $game['id'] ?>" class="text-blue-600 hover:underline">Подробнее</a>
                    <?php else: ?>
                        <form method="post" action="<?= $teamId ? 'join.php' : '#' ?>">
                            <input type="hidden" name="game_id" value="<?= $game['id'] ?>">
                            <button type="submit"
                                    onclick="<?= !$teamId ? "document.getElementById('authModal').classList.remove('hidden'); return false;" : '' ?>"
                                    class="text-blue-600 hover:underline">
                                Принять участие
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include('footer.php'); ?>
