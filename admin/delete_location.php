<?php
require_once __DIR__ . '/../db/db.php';

$id = $_GET['id'] ?? null;
$gameId = $_GET['game_id'] ?? null;

if ($id) {
    $stmt = $db->prepare("DELETE FROM game_locations WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: locations.php?game_id=$gameId");
exit;
