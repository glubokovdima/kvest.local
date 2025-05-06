<?php
require_once __DIR__ . '/../db/db.php';

try {
    $db->exec("ALTER TABLE games ADD COLUMN checkpoints TEXT");
    echo "✅ Поле checkpoints успешно добавлено.";
} catch (PDOException $e) {
    echo "⚠️ Ошибка: " . $e->getMessage();
}
