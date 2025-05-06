<?php
session_start();
require_once __DIR__ . '/db/db.php';

// Получаем ID квеста
$gameId = isset($_GET['game_id']) ? (int)$_GET['game_id'] : 0;
if (!$gameId) {
    die('Квест не найден');
}

// Загружаем информацию о квесте
$stmt = $db->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$gameId]);
$game = $stmt->fetch();

if (!$game) {
    die('Квест не найден');
}

// Преобразуем время старта квеста в формат timestamp
$startTime = strtotime($game['start_time']);
$currentTime = time(); // Текущее время
$timeDifference = $startTime - $currentTime; // Разница во времени между стартом и текущим моментом

// Проверяем, осталось ли меньше 2 часов до начала квеста
$showGameDetails = $timeDifference <= 2 * 60 * 60; // 2 часа (в секундах)

// Если мы уже зарегистрированы на квест, загружаем данные команды
$teamId = $_SESSION['team_id'] ?? null;
$teamGame = null;
if ($teamId) {
    $stmt = $db->prepare("SELECT * FROM team_games WHERE team_id = ? AND game_id = ?");
    $stmt->execute([$teamId, $gameId]);
    $teamGame = $stmt->fetch();
}

$pageTitle = "Подробнее о квесте: " . htmlspecialchars($game['name']);
include('header.php');
?>

<!-- Основной контент страницы -->
<div class="max-w-3xl mx-auto">
    <h1 class="text-2xl font-bold mb-4">🎮 Квест: <?= htmlspecialchars($game['name']) ?></h1>
    <p class="text-sm text-gray-500 mb-2"><?= htmlspecialchars($game['start_time']) ?></p>

    <?php if ($showGameDetails): ?>
        <p class="mb-6"><?= htmlspecialchars($game['description']) ?></p>
        <p class="text-gray-500">Информация о квесте доступна!</p>

        <?php if ($teamGame): ?>
            <div class="bg-white p-4 rounded shadow">
                <h2 class="text-xl font-semibold mb-2">Информация о вашем участии:</h2>
                <p class="text-sm text-gray-700">Номер участника: <strong><?= $teamGame['team_number'] ?></strong></p>
                <p class="text-sm text-gray-700">Статус: <strong><?= htmlspecialchars($teamGame['status']) ?></strong></p>
                <form method="post" action="game-detail.php?game_id=<?= $gameId ?>" class="mt-4">
                    <input type="hidden" name="cancel_game_id" value="<?= $gameId ?>">
                    <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Отменить участие</button>
                </form>
            </div>
        <?php else: ?>
            <p class="text-gray-500">Вы ещё не зарегистрированы на этот квест.</p>
        <?php endif; ?>
    <?php else: ?>
        <p class="text-yellow-600 font-semibold mb-4">
            Информация о квесте доступна за 2 часа до начала!
        </p>
        <p id="timer" class="text-lg text-gray-800"></p>
    <?php endif; ?>

    <div class="mt-6">
        <a href="index.php" class="text-blue-600 hover:underline">← Вернуться к квестам</a>
    </div>
</div>

<script>
    // Таймер до начала квеста
    var startTime = <?= $startTime ?> * 1000; // Время старта квеста в миллисекундах
    var currentTime = new Date().getTime(); // Текущее время в миллисекундах
    var timeDifference = startTime - currentTime;

    // Функция для обновления таймера
    function updateTimer() {
        var remainingTime = startTime - new Date().getTime();

        if (remainingTime <= 0) {
            document.getElementById('timer').innerHTML = "Квест начнется сейчас!";
        } else {
            var hours = Math.floor(remainingTime / (1000 * 60 * 60));
            var minutes = Math.floor((remainingTime % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((remainingTime % (1000 * 60)) / 1000);

            document.getElementById('timer').innerHTML = `До начала квеста: ${hours}ч ${minutes}мин ${seconds}сек`;
        }
    }

    // Обновляем таймер каждую секунду
    setInterval(updateTimer, 1000);
</script>

<?php include('footer.php'); ?>
