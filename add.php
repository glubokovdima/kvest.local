<?php
require_once __DIR__ . '/db/db.php';

try {
    // Проверим, есть ли уже колонка progress
    $columns = $db->query("PRAGMA table_info(team_progress)")->fetchAll(PDO::FETCH_ASSOC);
    $hasColumn = false;

    foreach ($columns as $col) {
        if ($col['name'] === 'progress') {
            $hasColumn = true;
            break;
        }
    }

    if ($hasColumn) {
        echo "✅ Колонка 'progress' уже существует в 'team_progress'.\n";
    } else {
        $db->exec("ALTER TABLE team_progress ADD COLUMN progress TEXT");
        echo "✅ Колонка 'progress' успешно добавлена в 'team_progress'.\n";
    }
} catch (PDOException $e) {
    echo "❌ Ошибка: " . $e->getMessage();
}
