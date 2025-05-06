<?php
session_start();
require_once __DIR__ . '/db/db.php';

// Защита
if (!isset($_SESSION['team_id'])) {
    header('Location: index.php');
    exit;
}

$teamId = $_SESSION['team_id'];

// Получаем информацию о команде
$stmt = $db->prepare("SELECT * FROM teams WHERE id = ?");
$stmt->execute([$teamId]);
$team = $stmt->fetch(PDO::FETCH_ASSOC);

// Получаем список квестов, на которые зарегистрирована команда
$stmt = $db->prepare("
    SELECT tg.*, g.name, g.slug, g.start_time 
    FROM team_games tg
    JOIN games g ON g.id = tg.game_id
    WHERE tg.team_id = ?
    ORDER BY g.start_time ASC
");
$stmt->execute([$teamId]);
$games = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Личный кабинет"; // Указываем название страницы

include('header.php');
?>

<!-- Основной контент страницы -->
<div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold mb-4">👋 Привет, <?= htmlspecialchars($team['team_number']) ?>!</h1>
    <p class="text-sm text-gray-600 mb-6">Email: <?= htmlspecialchars($team['email']) ?></p>

    <h2 class="text-xl font-semibold mb-3">🎮 Мои квесты</h2>
    <?php if (count($games) === 0): ?>
        <p class="text-gray-500">Вы ещё не зарегистрированы ни на один квест.</p>
    <?php endif; ?>

    <div class="space-y-6">
        <?php foreach ($games as $game): ?>
            <div class="bg-white shadow-md rounded-lg p-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="font-semibold text-xl"><?= htmlspecialchars($game['name']) ?></h3>
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($game['start_time']) ?></p>
                        <p class="text-sm text-gray-700 mt-1">Статус: <?= htmlspecialchars($game['status']) ?><?= $game['is_paid'] ? ', оплачено' : '' ?></p>
                        <p class="text-sm text-gray-700">Номер участника: <strong><?= $game['team_number'] ?? '?' ?></strong></p>
                    </div>
                    <div>
                        <a href="game-detail.php?game_id=<?= $game['game_id'] ?>" class="text-blue-600 hover:underline">Подробнее</a>
                    </div>
                </div>
                <form method="post" action="dashboard.php" class="mt-4">
                    <input type="hidden" name="cancel_game_id" value="<?= $game['game_id'] ?>">
                    <button type="submit" class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 transition mt-4">Отменить участие</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-6">
        <a href="index.php" class="text-blue-600 hover:underline">← Вернуться к квестам</a>
    </div>
</div>

<?php
// Обработка отмены участия
if (isset($_POST['cancel_game_id'])) {
    $cancelGameId = (int)$_POST['cancel_game_id'];
    $stmt = $db->prepare("DELETE FROM team_games WHERE team_id = ? AND game_id = ?");
    $stmt->execute([$teamId, $cancelGameId]);

    header("Location: dashboard.php");
    exit;
}

include('footer.php');
?>
