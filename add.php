<?php
require_once __DIR__ . '/db/db.php';

try {
    // Добавляем колонку phone
    $db->exec("ALTER TABLE teams ADD COLUMN phone TEXT");
    echo "✅ Столбец 'phone' успешно добавлен в таблицу 'teams'.";
} catch (PDOException $e) {
    echo "❌ Ошибка: " . $e->getMessage();
}