<?php
require_once __DIR__ . '/../db/db.php';

$location_id = $_POST['location_id'];
$question = trim($_POST['question']);
$type = $_POST['type'];
$imagePath = null;

if ($type === 'photo' && isset($_FILES['image']) && $_FILES['image']['tmp_name']) {
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_') . '.' . $ext;
    $path = __DIR__ . '/../uploads/' . $filename;
    move_uploaded_file($_FILES['image']['tmp_name'], $path);
    $imagePath = '/uploads/' . $filename;
}

$stmt = $db->prepare("INSERT INTO game_questions (location_id, question, type, image, is_main)
                      VALUES (?, ?, ?, ?, 0)");
$stmt->execute([$location_id, $question, $type, $imagePath]);

header("Location: questions.php?game_id=" . $_GET['game_id']);
