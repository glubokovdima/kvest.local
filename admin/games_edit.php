<?php
require_once __DIR__ . '/../db/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editing = false;

if ($id) {
    $stmt = $db->prepare("SELECT * FROM games WHERE id = ?");
    $stmt->execute([$id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($game) {
        $editing = true;
        $checkpoints = json_decode($game['checkpoints'] ?? '[]', true);
    } else {
        die('Квест не найден');
    }
} else {
    $game = [
        'name' => '',
        'slug' => '',
        'description' => '',
        'start_time' => '',
        'is_active' => 1,
        'cover_image' => '',
        'checkpoints' => ''
    ];
    $checkpoints = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $slug = trim($_POST['slug']);
    $description = trim($_POST['description']);
    $start_time = trim($_POST['start_time']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $cover_image = trim($_POST['cover_image']);

    $cp = $_POST['checkpoints'] ?? [];
    $checkpoints = [];

    foreach ($cp as $c) {
        if (!empty($c['title'])) {
            $checkpoints[] = [
                'title' => $c['title'],
                'question' => $c['question'],
                'answers' => $c['answers'],
                'lat' => $c['lat'],
                'lng' => $c['lng'],
                'radius' => $c['radius']
            ];
        }
    }

    $checkpointsJson = json_encode($checkpoints, JSON_UNESCAPED_UNICODE);

    if ($editing) {
        $stmt = $db->prepare("UPDATE games SET name = ?, slug = ?, description = ?, start_time = ?, is_active = ?, cover_image = ?, checkpoints = ? WHERE id = ?");
        $stmt->execute([$name, $slug, $description, $start_time, $is_active, $cover_image, $checkpointsJson, $id]);
    } else {
        $stmt = $db->prepare("INSERT INTO games (name, slug, description, start_time, is_active, cover_image, checkpoints) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $slug, $description, $start_time, $is_active, $cover_image, $checkpointsJson]);
    }

    header('Location: games.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $editing ? 'Редактировать' : 'Добавить' ?> квест</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .cp-block { background: #f9fafb; padding: 1rem; margin-bottom: 1rem; border-radius: 0.5rem; border: 1px solid #ddd; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 p-6 font-sans">
<div class="max-w-3xl mx-auto">
    <h1 class="text-2xl font-bold mb-4"><?= $editing ? '✏️ Редактировать' : '➕ Добавить' ?> квест</h1>

    <form method="post">
        <!-- основные поля квеста -->
        <div class="space-y-4 mb-8">
            <input name="name" placeholder="Название" value="<?= htmlspecialchars($game['name']) ?>" class="w-full p-2 border rounded" required>
            <input name="slug" placeholder="Слаг (URL)" value="<?= htmlspecialchars($game['slug']) ?>" class="w-full p-2 border rounded">
            <textarea name="description" placeholder="Описание" class="w-full p-2 border rounded"><?= htmlspecialchars($game['description']) ?></textarea>
            <input type="datetime-local" name="start_time" value="<?= htmlspecialchars($game['start_time']) ?>" class="w-full p-2 border rounded">
            <input name="cover_image" placeholder="Обложка" value="<?= htmlspecialchars($game['cover_image']) ?>" class="w-full p-2 border rounded">
            <label class="inline-flex items-center">
                <input type="checkbox" name="is_active" <?= $game['is_active'] ? 'checked' : '' ?> class="mr-2"> Активен
            </label>
        </div>

        <!-- шаги -->
        <h2 class="text-lg font-semibold mb-2">📍 Шаги квеста</h2>
        <div id="checkpoints-container">
            <?php foreach ($checkpoints as $i => $cp): ?>
                <div class="cp-block">
                    <input name="checkpoints[<?= $i ?>][title]" placeholder="Название точки" value="<?= htmlspecialchars($cp['title']) ?>" class="w-full mb-2 p-2 border rounded">
                    <textarea name="checkpoints[<?= $i ?>][question]" placeholder="Вопрос" class="w-full mb-2 p-2 border rounded"><?= htmlspecialchars($cp['question']) ?></textarea>
                    <input name="checkpoints[<?= $i ?>][answers]" placeholder="Ответы (через | )" value="<?= htmlspecialchars($cp['answers']) ?>" class="w-full mb-2 p-2 border rounded">
                    <div class="flex gap-2 mb-2">
                        <input name="checkpoints[<?= $i ?>][lat]" placeholder="lat" value="<?= htmlspecialchars($cp['lat']) ?>" class="w-full p-2 border rounded">
                        <input name="checkpoints[<?= $i ?>][lng]" placeholder="lng" value="<?= htmlspecialchars($cp['lng']) ?>" class="w-full p-2 border rounded">
                        <input name="checkpoints[<?= $i ?>][radius]" placeholder="радиус" value="<?= htmlspecialchars($cp['radius']) ?>" class="w-full p-2 border rounded">
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-red-600 text-sm">Удалить шаг</button>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" onclick="addCheckpoint()" class="bg-gray-200 px-4 py-2 rounded hover:bg-gray-300 mb-6">➕ Добавить шаг</button>

        <div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">💾 Сохранить</button>
            <a href="games.php" class="ml-4 text-blue-600 hover:underline">← Назад</a>
        </div>
    </form>
</div>

<script>
    let checkpointIndex = <?= count($checkpoints) ?>;

    function addCheckpoint() {
        const container = document.getElementById('checkpoints-container');
        const block = document.createElement('div');
        block.className = 'cp-block';
        block.innerHTML = `
            <input name="checkpoints[\${checkpointIndex}][title]" placeholder="Название точки" class="w-full mb-2 p-2 border rounded">
            <textarea name="checkpoints[\${checkpointIndex}][question]" placeholder="Вопрос" class="w-full mb-2 p-2 border rounded"></textarea>
            <input name="checkpoints[\${checkpointIndex}][answers]" placeholder="Ответы (через | )" class="w-full mb-2 p-2 border rounded">
            <div class="flex gap-2 mb-2">
                <input name="checkpoints[\${checkpointIndex}][lat]" placeholder="lat" class="w-full p-2 border rounded">
                <input name="checkpoints[\${checkpointIndex}][lng]" placeholder="lng" class="w-full p-2 border rounded">
                <input name="checkpoints[\${checkpointIndex}][radius]" placeholder="радиус" class="w-full p-2 border rounded">
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-red-600 text-sm">Удалить шаг</button>
        `;
        container.appendChild(block);
        checkpointIndex++;
    }
</script>
</body>
</html>
