<?php
session_start();
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/includes/functions.php';

$result = handleAuth($db);
if ($result['success']) {
    header('Location: dashboard.php');
    exit;
}

$_SESSION['error'] = $result['error'] ?? 'Ошибка';
header('Location: index.php');
