<?php
session_start();
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');
$teamId = $_SESSION['team_id'] ?? null;
if (!$teamId) exit(json_encode(['success' => false, 'error' => 'Неавторизован']));

echo json_encode(handleAnswer($db, $teamId, $_POST, $_FILES));
