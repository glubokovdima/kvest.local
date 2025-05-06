<?php
session_start();
require_once __DIR__ . '/db/db.php';

// Проверяем, авторизована ли команда
$teamId = $_SESSION['team_id'] ?? null;
$team = null;
if ($teamId) {
    // Получаем информацию о команде
    $stmt = $db->prepare("SELECT * FROM teams WHERE id = ?");
    $stmt->execute([$teamId]);
    $team = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Получаем информацию о квестах для зарегистрированных команд
$teamGames = [];
if ($teamId) {
    $stmt = $db->prepare("SELECT game_id FROM team_games WHERE team_id = ?");
    $stmt->execute([$teamId]);
    $teamGames = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?php echo $pageTitle ?? 'Квесты'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 text-gray-900 p-6 font-sans">
<header class="bg-blue-600 text-white p-4 rounded mb-6">
    <div class="max-w-4xl mx-auto flex justify-between items-center">
        <a href="index.php" class="text-xl font-bold">🏆 Квесты</a>
        <nav>
            <a href="index.php" class="text-white hover:underline mr-4">Главная</a>
            <?php if (!$teamId): ?>
                <a href="index.php" onclick="document.getElementById('authModal').classList.remove('hidden')" class="text-white hover:underline">Вход / Регистрация</a>
            <?php else: ?>
                <a href="dashboard.php" class="text-white hover:underline">Мой кабинет</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<!-- Модальное окно для входа / регистрации -->
<div id="authModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded w-full max-w-md relative">
        <button onclick="this.parentElement.parentElement.classList.add('hidden')" class="absolute top-2 right-3 text-gray-500">✖</button>
        <h2 id="modalTitle" class="text-xl font-bold mb-4">Вход</h2>
        <form method="post" action="auth.php" class="space-y-4" onsubmit="return setAuthMode()">
            <input type="hidden" name="mode" id="authMode" value="login">
            <input type="email" name="email" required placeholder="Email" class="w-full p-2 border rounded">
            <input type="password" name="password" required placeholder="Пароль" class="w-full p-2 border rounded">
            <input type="text" name="phone" id="phone" placeholder="Номер телефона" class="w-full p-2 border rounded hidden">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded w-full">Продолжить</button>
        </form>
        <div class="text-center text-sm mt-4">
            <span id="switchText">Нет аккаунта?</span>
            <button onclick="toggleAuth()" class="text-blue-600 hover:underline" type="button" id="switchBtn">Зарегистрироваться</button>
        </div>
    </div>
</div>

<script>
    let isLogin = true;
    function toggleAuth() {
        isLogin = !isLogin;
        document.getElementById('authMode').value = isLogin ? 'login' : 'register';
        document.getElementById('modalTitle').innerText = isLogin ? 'Вход' : 'Регистрация';
        document.getElementById('phone').classList.toggle('hidden', isLogin);
        document.getElementById('switchText').innerText = isLogin ? 'Нет аккаунта?' : 'Уже есть аккаунт?';
        document.getElementById('switchBtn').innerText = isLogin ? 'Зарегистрироваться' : 'Войти';
    }
    function setAuthMode() {
        document.getElementById('authMode').value = isLogin ? 'login' : 'register';
        return true;
    }
</script>
