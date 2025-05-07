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
        $key = "checkpoint_$i";
        $hasMain = !empty($progress[$key]['main_answer']);

        $hasExtras = true;
        $additional = $cp['additional_questions'] ?? [];
        foreach ($additional as $extraIndex => $extra) {
            if (empty($progress[$key]['extra_answers'][$extraIndex])) {
                $hasExtras = false;
                break;
            }
        }

        $stepComplete = $hasMain && $hasExtras;

        if (!$stepComplete) {
            $currentStep = $i;
            break;
        }

        $currentStep = $i + 1;
    }

    if ($currentStep < count($checkpoints)) {
        $step = $checkpoints[$currentStep];
    } else {
        $step = null;
    }
}

$pageTitle = 'Квест: ' . htmlspecialchars($game['name']);
include('header.php');
?>

<div class="max-w-3xl mx-auto px-4">
    <h1 class="text-2xl font-bold mb-4">🎮 Квест: <?= htmlspecialchars($game['name']) ?></h1>
    <p class="text-sm text-gray-500 mb-4">Старт: <?= htmlspecialchars($game['start_time']) ?></p>

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
        <div class="mb-6 bg-white p-4 rounded shadow <?= !$teamGame ? 'hidden' : '' ?>">
            <!-- Карта -->
            <div id="map" class="w-full h-64 rounded mb-4 border"></div>
        <h2 class="text-xl font-bold mb-1">📍 <?= htmlspecialchars($step['title']) ?></h2>
        <p class="text-sm text-gray-600 mb-2">Координаты: <?= $step['lat'] ?>, <?= $step['lng'] ?> | Радиус: <?= $step['radius'] ?> м</p>
        <div id="geo-distance" class="text-sm text-blue-700 font-medium mb-4 hidden">🚗 До цели: ...</div>


        <div id="step-content" class="hidden">

        <!-- Информация о шаге -->
        <div>

            <?php if (!empty($step['question_image'])): ?>
                <img src="<?= htmlspecialchars($step['question_image']) ?>" alt="Картинка к вопросу" class="w-full max-w-md rounded shadow mb-4">
            <?php endif; ?>

            <p class="text-lg text-gray-900 leading-relaxed mb-4">
                ❓ <?= nl2br(htmlspecialchars($step['question'])) ?>
            </p>
        </div>

        <div id="geo-message" class="text-sm text-gray-500 mb-4">⏳ Определяем местоположение...</div>

        <!-- Ответ на основной вопрос -->
        <?php if (!empty($progress["checkpoint_$currentStep"]['main_answer'])): ?>
            <div class="bg-green-50 text-green-800 p-4 rounded shadow text-sm mb-4">
                ✅ Принят ответ: <strong><?= htmlspecialchars($progress["checkpoint_$currentStep"]['main_answer']) ?></strong>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    document.getElementById('extra-section')?.classList.remove('hidden');
                });
            </script>
        <?php else: ?>
            <form id="answer-form" class="hidden bg-white p-4 rounded shadow mt-4">
                <input type="hidden" name="game_id" value="<?= $gameId ?>">
                <input type="hidden" name="checkpoint_index" value="<?= $currentStep ?>">
                <label class="block mb-2 font-semibold text-gray-700">Ваш ответ:</label>
                <?php if (!empty($step['note'])): ?>
                    <div class="hint-message-main hidden mt-2 text-sm text-yellow-700">
                        💡 <?= htmlspecialchars($step['note']) ?>
                    </div>
                <?php endif; ?>
                <input type="text" name="answer" placeholder="Введите ответ" required class="w-full px-4 py-2 mb-4 border rounded focus:outline-none focus:ring-2">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded">Отправить</button>
                <div id="answer-message" class="mt-4 text-sm"></div>
            </form>
        <?php endif; ?>


        <!-- Блок с дополнительными вопросами -->
        <?php if (!empty($step['additional_questions'])): ?>
            <div id="extra-section" class="mt-6 p-4 bg-blue-50 rounded shadow <?= empty($progress["checkpoint_$currentStep"]['main_answer']) ? 'hidden' : '' ?>">
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

                        <?php if (!empty($extra['image'])): ?>
                            <div class="mb-3">
                                <img src="<?= htmlspecialchars($extra['image']) ?>" alt="Картинка к доп. вопросу" class="rounded shadow max-w-full">
                            </div>
                        <?php endif; ?>

                        <p class="font-semibold mb-1 text-lg">❓ <?= htmlspecialchars($extra['question'] ?? 'Вопрос') ?></p>

                        <?php if (!empty($extra['text'])): ?>
                            <p class="text-gray-700 mb-3"><?= nl2br(htmlspecialchars($extra['text'])) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($extra['hint'])): ?>
                            <div class="hint-message mt-2 text-sm text-yellow-700">
                                💡 <?= htmlspecialchars($extra['hint']) ?>
                            </div>
                        <?php endif; ?>

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
    </div>
    <?php endif; ?>


    <script>
        const checkpointLat = <?= floatval($step['lat'] ?? 0) ?>;
        const checkpointLng = <?= floatval($step['lng'] ?? 0) ?>;
        const checkpointRadius = <?= intval($step['radius'] ?? 50) ?>;

        let map = null, userMarker = null, finishMarker = null, routeControl = null;


        if ('geolocation' in navigator) {
            navigator.geolocation.watchPosition(pos => {
                const userLat = pos.coords.latitude;
                const userLng = pos.coords.longitude;
                if (!map) {
                    map = L.map('map', { zoomControl: false, attributionControl: false }).setView([userLat, userLng], 15);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                    userMarker = L.marker([userLat, userLng], { icon: L.divIcon({ html: "🚗", className: "text-2xl" }) }).addTo(map);
                    finishMarker = L.marker([checkpointLat, checkpointLng], { icon: L.divIcon({ html: "🏁", className: "text-2xl" }) }).addTo(map);
                    routeControl = L.Routing.control({
                        serviceUrl: 'https://router.project-osrm.org/route/v1',
                        waypoints: [L.latLng(userLat, userLng), L.latLng(checkpointLat, checkpointLng)],
                        createMarker: () => null, show: false, fitSelectedRoutes: true,
                        lineOptions: { styles: [{ color: '#007BFF', weight: 5, opacity: 0.8 }] }
                    }).addTo(map);
                } else {
                    userMarker.setLatLng([userLat, userLng]);
                    routeControl.setWaypoints([L.latLng(userLat, userLng), L.latLng(checkpointLat, checkpointLng)]);
                }

                const dist = map.distance([userLat, userLng], [checkpointLat, checkpointLng]);
                const distanceEl = document.getElementById('geo-distance');
                if (distanceEl) {
                    distanceEl.textContent = `🚗 До цели: ${Math.round(dist)} м`;
                    distanceEl.classList.remove('hidden');
                }
                const form = document.getElementById('answer-form');
                const msg = document.getElementById('geo-message');
                if (form && msg) {
                    if (msg) {
                        if (dist <= checkpointRadius) {
                            msg.textContent = "✅ Вы на месте! Шаг разблокирован.";
                            msg.className = "text-green-600 mt-4";

                            // Показываем шаг, если он был скрыт
                            const stepContent = document.getElementById('step-content');
                            if (stepContent) stepContent.classList.remove('hidden');

                            // Показываем форму ответа
                            if (form) form.classList.remove('hidden');
                        } else {
                            msg.textContent = `🚗 До точки: ${Math.round(dist)} м`;
                            msg.className = "text-gray-600 mt-4";

                            // Скрываем шаг
                            const stepContent = document.getElementById('step-content');
                            if (stepContent) stepContent.classList.add('hidden');

                            // И форму тоже
                            if (form) form.classList.add('hidden');
                        }
                    }

                }
            }, () => {
                const msg = document.getElementById('geo-message');
                if (msg) msg.textContent = '⚠️ Не удалось получить геолокацию';
            }, { enableHighAccuracy: true });
        }


    </script>

    <script>
        document.getElementById('answer-form')?.addEventListener('submit', async function (e) {
            e.preventDefault();
            const form = e.target;
            const fd = new FormData(form);

            const submitBtn = form.querySelector('button[type="submit"]');
            const messageBox = document.getElementById('answer-message');
            const extraBlock = document.getElementById('extra-section');

            submitBtn.disabled = true;
            messageBox.innerText = '⏳ Проверка ответа...';
            messageBox.className = 'text-gray-600 mt-2';

            try {
                const res = await fetch('submit_main.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();

                if (data.success) {
                    messageBox.innerText = '✅ Ответ принят!';
                    messageBox.className = 'text-green-600 mt-2';
                    form.classList.add('hidden');

                    // Показываем блок с дополнительными вопросами
                    if (extraBlock) {
                        extraBlock.classList.remove('hidden');
                    }
                } else {
                    messageBox.innerText = '❌ Неверный ответ. Попробуйте ещё раз.';
                    messageBox.className = 'text-red-600 mt-2';

                    // Подсказка, если есть
                    const hint = form.querySelector('.hint-message-main');
                    if (hint) {
                        hint.classList.remove('hidden');
                    }
                }
            } catch (error) {
                messageBox.innerText = '⚠️ Ошибка отправки';
                messageBox.className = 'text-red-600 mt-2';
            } finally {
                submitBtn.disabled = false;
            }
        });
    </script>

<?php include('footer.php'); ?>