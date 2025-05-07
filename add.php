<?php
require_once __DIR__ . '/db/db.php';

$db->exec("ALTER TABLE game_questions ADD COLUMN text TEXT");

echo "Колонка 'text' успешно добавлена в таблицу game_questions.";
