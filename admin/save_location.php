<?php
require_once __DIR__ . '/../db/db.php';

$id = $_POST['location_id'];
$question = trim($_POST['question']);
$answer = trim($_POST['correct_answer']);
$hint = trim($_POST['hint'] ?? '');

// Удаляем основной вопрос (если был)
$db->prepare("DELETE FROM game_questions WHERE location_id = ? AND is_main = 1")->execute([$id]);

// Сохраняем
$db->prepare("INSERT INTO game_questions (location_id, question, correct_answer, hint, is_main)
              VALUES (?, ?, ?, ?, 1)")->execute([$id, $question, $answer, $hint]);

header("Location: questions.php?game_id=" . $_GET['game_id']);
