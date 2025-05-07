<?php
session_start();
require_once __DIR__ . '/db/db.php';

// Установка языка (по параметру или сессии)
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'] === 'pl' ? 'pl' : 'ru';
}
$lang = $_SESSION['lang'] ?? 'ru';

// Функция перевода
function __($ru, $pl = '') {
    global $lang;
    return $lang === 'pl' && $pl !== '' ? $pl : $ru;
}

// Проверка авторизации команды
$teamId = $_SESSION['team_id'] ?? null;
$team = null;
if ($teamId) {
    $stmt = $db->prepare("SELECT * FROM teams WHERE id = ?");
    $stmt->execute([$teamId]);
    $team = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Игры команды
$teamGames = [];
if ($teamId) {
    $stmt = $db->prepare("SELECT game_id FROM team_games WHERE team_id = ?");
    $stmt->execute([$teamId]);
    $teamGames = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?? __('Квесты', 'Questy') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.js"></script>
</head>
<body class="bg-gray-50 text-gray-900 p-6 font-sans">


<header class="bg-blue-600 text-white p-4 rounded mb-6 relative z-50">
    <div class="max-w-4xl mx-auto flex justify-between items-center">
        <a href="index.php" class="text-xl font-bold">🏆 <?= __('Квесты', 'Questy') ?></a>
        <button id="menu-toggle" class="md:hidden text-white focus:outline-none text-2xl">
            ☰
        </button>
        <nav class="hidden md:flex gap-6 items-center text-sm">
            <a href="index.php" class="hover:underline"><?= __('Главная', 'Strona główna') ?></a>
            <?php if (!$teamId): ?>
                <a href="#" onclick="event.preventDefault(); document.getElementById('authModal').classList.remove('hidden')" class="hover:underline">


                    <?= __('Вход / Регистрация', 'Logowanie / Rejestracja') ?>
                </a>
            <?php else: ?>
                <a href="dashboard.php" class="hover:underline"><?= __('Мой кабинет', 'Moje konto') ?></a>
            <?php endif; ?>

            <?php
            $params = $_GET;
            unset($params['lang']);
            $query = http_build_query($params);
            $base = strtok($_SERVER["REQUEST_URI"], '?');
            ?>
            <div class="ml-4 space-x-2">
                <a href="<?= $base . '?' . $query . '&lang=ru' ?>" class="<?= $lang === 'ru' ? 'underline font-bold' : 'opacity-75' ?>">RU</a>
                <a href="<?= $base . '?' . $query . '&lang=pl' ?>" class="<?= $lang === 'pl' ? 'underline font-bold' : 'opacity-75' ?>">PL</a>
            </div>
        </nav>
    </div>

    <!-- Мобильное меню: раскрывается ниже на всю ширину -->
    <div id="mobile-menu" class="hidden flex-col bg-blue-700 text-white w-full mt-4 p-4 space-y-3 md:hidden transition-all duration-300">
        <a href="index.php" class="block hover:underline"><?= __('Главная', 'Strona główna') ?></a>
        <?php if (!$teamId): ?>
            <a href="#" onclick="event.preventDefault(); document.getElementById('authModal').classList.remove('hidden')" class="block hover:underline">
                <?= __('Вход / Регистрация', 'Logowanie / Rejestracja') ?>
            </a>
        <?php else: ?>
            <a href="dashboard.php" class="block hover:underline"><?= __('Мой кабинет', 'Moje konto') ?></a>
        <?php endif; ?>

        <div class="space-x-2 text-sm">
            <a href="<?= $base . '?' . $query . '&lang=ru' ?>" class="<?= $lang === 'ru' ? 'underline font-bold' : 'opacity-75' ?>">RU</a>
            <a href="<?= $base . '?' . $query . '&lang=pl' ?>" class="<?= $lang === 'pl' ? 'underline font-bold' : 'opacity-75' ?>">PL</a>
        </div>
    </div>
</header>

<script>
    const toggleBtn = document.getElementById('menu-toggle');
    const menu = document.getElementById('mobile-menu');

    toggleBtn.addEventListener('click', () => {
        menu.classList.toggle('hidden');
    });
</script>




<!-- Модальное окно авторизации -->
<div id="authModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded w-full max-w-md relative">
        <button onclick="this.parentElement.parentElement.classList.add('hidden')" class="absolute top-2 right-3 text-gray-500">✖</button>
        <h2 id="modalTitle" class="text-xl font-bold mb-4"><?= __('Вход', 'Logowanie') ?></h2>
        <form method="post" action="auth.php" class="space-y-4" onsubmit="return setAuthMode()">
            <input type="hidden" name="mode" id="authMode" value="login">
            <input type="email" name="email" required placeholder="Email" class="w-full p-2 border rounded">
            <input type="password" name="password" required placeholder="<?= __('Пароль', 'Hasło') ?>" class="w-full p-2 border rounded">
            <input type="text" name="phone" id="phone" placeholder="<?= __('Номер телефона', 'Numer telefonu') ?>" class="w-full p-2 border rounded hidden">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded w-full"><?= __('Продолжить', 'Dalej') ?></button>
        </form>
        <div class="text-center text-sm mt-4">
            <span id="switchText"><?= __('Нет аккаунта?', 'Nie masz konta?') ?></span>
            <button onclick="toggleAuth()" class="text-blue-600 hover:underline" type="button" id="switchBtn">
                <?= __('Зарегистрироваться', 'Zarejestruj się') ?>
            </button>
        </div>
    </div>
</div>

<script>
    let isLogin = true;
    function toggleAuth() {
        isLogin = !isLogin;
        document.getElementById('authMode').value = isLogin ? 'login' : 'register';
        document.getElementById('modalTitle').innerText = isLogin ? '<?= __('Вход', 'Logowanie') ?>' : '<?= __('Регистрация', 'Rejestracja') ?>';
        document.getElementById('phone').classList.toggle('hidden', isLogin);
        document.getElementById('switchText').innerText = isLogin ? '<?= __('Нет аккаунта?', 'Nie masz konta?') ?>' : '<?= __('Уже есть аккаунт?', 'Masz już konto?') ?>';
        document.getElementById('switchBtn').innerText = isLogin ? '<?= __('Зарегистрироваться', 'Zarejestruj się') ?>' : '<?= __('Войти', 'Zaloguj się') ?>';
    }

    function setAuthMode() {
        document.getElementById('authMode').value = isLogin ? 'login' : 'register';
        return true;
    }
</script>
