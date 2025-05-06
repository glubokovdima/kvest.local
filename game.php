<?php
session_start();
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/includes/functions.php';

$gameId = isset($_GET['game_id']) ? (int)$_GET['game_id'] : 0;
if (!$gameId) die('Квест не найден');

$game = getGame($db, $gameId);
if (!$game) die('Квест не найден');

$teamId = $_SESSION['team_id'] ?? null;
$teamGame = $teamId ? getTeamGame($db, $teamId, $gameId) : null;

$startTime = strtotime($game['start_time']);
$currentTime = time();
$timeDifference = $startTime - $currentTime;
$showGameDetails = $timeDifference <= 2 * 60 * 60;

$isPlaying = ($showGameDetails && $teamGame);
$checkpoints = json_decode($game['checkpoints'], true);
if (!is_array($checkpoints)) $checkpoints = [];

$progress = [];
$currentStep = 0;
$step = null;

if ($isPlaying) {
    $progress = getProgress($db, $teamId, $gameId);
    if (!is_array($progress)) $progress = [];

    foreach ($checkpoints as $i => $cp) {
        $key = 'checkpoint_' . $i;
        if (empty($progress[$key]['main_answer'])) {
            $currentStep = $i;
            break;
        } else {
            $currentStep = $i + 1;
        }
    }
    $step = $checkpoints[$currentStep] ?? null;
}

$pageTitle = 'Квест: ' . htmlspecialchars($game['name']);
include('header.php');
?>

<div class="max-w-3xl mx-auto">
    <h1 class="text-2xl font-bold mb-4">🎮 Квест: <?= htmlspecialchars($game['name']) ?></h1>
    <p class="text-sm text-gray-500 mb-2">Старт: <?= htmlspecialchars($game['start_time']) ?></p>

    <?php if (!$showGameDetails): ?>
        <p class="text-yellow-600 font-semibold mb-4">Информация о квесте будет доступна за 2 часа до начала!</p>
        <p id="timer" class="text-lg text-gray-800"></p>

    <?php elseif (!$teamGame): ?>
        <p class="text-yellow-600 font-semibold mb-4">Вы ещё не зарегистрированы на этот квест.</p>

    <?php elseif ($currentStep >= count($checkpoints)): ?>
        <div class="bg-green-100 text-green-800 p-4 rounded">
            <p class="text-lg font-semibold">Поздравляем! Вы завершили квест 🎉</p>
        </div>

    <?php else: ?>
        <div class="mb-6 bg-white p-4 rounded shadow">
            <h2 class="text-xl font-semibold mb-2">Шаг <?= $currentStep + 1 ?>: <?= htmlspecialchars($step['title']) ?></h2>
            <p class="text-gray-700 mb-4">📍 Координаты: <?= $step['lat'] ?>, <?= $step['lng'] ?> | Радиус: <?= $step['radius'] ?> м</p>
            <p class="mb-4 text-gray-800">❓ <?= nl2br(htmlspecialchars($step['question'])) ?></p>

            <div id="geo-message" class="text-sm text-gray-500 mb-4">⏳ Определяем местоположение...</div>
            <div id="map" class="w-full h-72 rounded mb-6"></div>


            <form id="answer-form" class="hidden bg-white p-4 rounded shadow mt-6 max-w-xl mx-auto">
                <input type="hidden" name="game_id" value="<?= $gameId ?>">
                <input type="hidden" name="checkpoint_index" value="<?= $currentStep ?>">
                <label class="block mb-2 font-semibold text-gray-700">Ваш ответ:</label>
                <input type="text" name="answer" placeholder="Введите ответ" required class="w-full px-4 py-2 mb-4 border rounded focus:outline-none focus:ring-2">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded">Отправить</button>
                <div id="answer-message" class="mt-4 text-sm"></div>
            </form>
            <?php if (!empty($step['additional_questions'])): ?>
                <div class="mt-6 p-4 bg-blue-50 rounded shadow">
                    <h3 class="text-lg font-semibold mb-4">Дополнительные вопросы</h3>

                    <?php foreach ($step['additional_questions'] as $index => $extra):
                        $extraAnswered = isset($progress["checkpoint_$currentStep"]['extra_answers'][$index]);
                        $isPhoto = $extra['type'] === 'photo';
                        $auto = ($isPhoto && ($extra['answer'] ?? '') === 'auto');
                        ?>
                        <form method="post"
                              enctype="multipart/form-data"
                              class="extra-answer-form mb-6 p-4 rounded bg-white shadow"
                            <?= $auto ? 'data-auto="1"' : '' ?>>
                            <input type="hidden" name="game_id" value="<?= $gameId ?>">
                            <input type="hidden" name="checkpoint_index" value="<?= $currentStep ?>">
                            <input type="hidden" name="extra_index" value="<?= $index ?>">

                            <p class="font-semibold mb-2">❓ <?= htmlspecialchars($extra['question'] ?? 'Вопрос') ?></p>

                            <?php if ($extraAnswered): ?>
                                <p class="text-green-700 font-medium mb-1">✅ Ответ уже отправлен</p>
                            <?php else: ?>
                                <?php if ($isPhoto): ?>
                                    <input type="file" name="answer" accept="image/*" required class="mb-3">
                                <?php else: ?>
                                    <input type="text" name="answer" required placeholder="Ваш ответ" class="w-full p-2 border rounded mb-3">
                                <?php endif; ?>
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded">
                                    Отправить
                                </button>
                            <?php endif; ?>

                            <div class="extra-form-message mt-2 text-sm text-gray-600"></div>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    <?php endif; ?>

    <div class="mt-6">
        <a href="index.php" class="text-blue-600 hover:underline">← Назад к квестам</a>
    </div>
</div>

<?php if (!$showGameDetails): ?>
    <script>
        const startTime = <?= $startTime ?> * 1000;
    </script>
    <script src="js/timer.js"></script>

<?php elseif ($isPlaying): ?>


    <script>
        const checkpointLat = <?= floatval($step['lat'] ?? 0) ?>;
        const checkpointLng = <?= floatval($step['lng'] ?? 0) ?>;
        const checkpointRadius = <?= intval($step['radius'] ?? 50) ?>;

        let map = null;
        let userMarker = null;
        let finishMarker = null;
        let routeControl = null;

        if ('geolocation' in navigator) {
            navigator.geolocation.watchPosition(
                pos => {
                    const userLat = pos.coords.latitude;
                    const userLng = pos.coords.longitude;

                    // Показ координат
                    const latEl = document.getElementById('coord-lat');
                    const lngEl = document.getElementById('coord-lng');
                    if (latEl && lngEl) {
                        latEl.textContent = userLat.toFixed(5);
                        lngEl.textContent = userLng.toFixed(5);
                    }

                    // Инициализация карты
                    if (!map) {
                        map = L.map('map', {
                            zoomControl: false,
                            attributionControl: false
                        }).setView([userLat, userLng], 15);

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '',
                            interactive: false
                        }).addTo(map);

                        // Машинка
                        userMarker = L.marker([userLat, userLng], {
                            icon: L.divIcon({
                                html: "🚗",
                                className: "text-2xl",
                                iconSize: [24, 24]
                            })
                        }).addTo(map);

                        // Финиш
                        finishMarker = L.marker([checkpointLat, checkpointLng], {
                            icon: L.divIcon({
                                html: "🏁",
                                className: "text-2xl",
                                iconSize: [24, 24]
                            })
                        }).addTo(map);

                        // Маршрут
                        routeControl = L.Routing.control({
                            waypoints: [
                                L.latLng(userLat, userLng),
                                L.latLng(checkpointLat, checkpointLng)
                            ],
                            routeWhileDragging: false,
                            draggableWaypoints: false,
                            addWaypoints: false,
                            createMarker: () => null,
                            show: false,
                            fitSelectedRoutes: true,
                            lineOptions: {
                                styles: [{ color: '#007BFF', weight: 5, opacity: 0.8 }]
                            }
                        }).addTo(map);
                    } else {
                        userMarker.setLatLng([userLat, userLng]);
                        routeControl.setWaypoints([
                            L.latLng(userLat, userLng),
                            L.latLng(checkpointLat, checkpointLng)
                        ]);
                    }

                    const dist = map.distance([userLat, userLng], [checkpointLat, checkpointLng]);
                    const form = document.getElementById('answer-form');
                    const message = document.getElementById('geo-message');

                    if (dist <= checkpointRadius) {
                        form.classList.remove('hidden');
                        message.textContent = "✅ Вы на месте!";
                        message.className = "text-green-600 mt-4";
                    } else {
                        form.classList.add('hidden');
                        message.textContent = `🚗 До цели: ${Math.round(dist)} м`;
                        message.className = "text-gray-600 mt-4";
                    }
                },
                err => {
                    const message = document.getElementById('geo-message');
                    if (message) message.textContent = '⚠️ Не удалось получить геолокацию';
                },
                { enableHighAccuracy: true }
            );
        }
    </script>




    <script src="script.js"></script>
<?php endif; ?>

<?php include('footer.php'); ?>
