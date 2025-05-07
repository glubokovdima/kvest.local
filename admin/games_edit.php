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
                'question_image' => $c['question_image'] ?? '',
                'answers' => $c['answers'],
                'note' => $c['note'] ?? '',
                'lat' => $c['lat'],
                'lng' => $c['lng'],
                'radius' => $c['radius'],
                'additional_questions' => array_map(function ($extra) {
                    return [
                        'type' => $extra['type'] ?? 'text',
                        'question' => $extra['question'] ?? '',
                        'text' => $extra['text'] ?? '',
                        'answer' => $extra['answer'] ?? '',
                        'hint' => $extra['hint'] ?? '',
                        'image' => $extra['image'] ?? ''
                    ];
                }, $c['additional_questions'] ?? [])

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
        <div class="space-y-4 mb-8">
            <input name="name" placeholder="Название" value="<?= htmlspecialchars($game['name']) ?>" class="w-full p-2 border rounded" required>
            <input name="slug" placeholder="Слаг (URL)" value="<?= htmlspecialchars($game['slug']) ?>" class="w-full p-2 border rounded">
            <textarea name="description" placeholder="Описание" class="w-full p-2 border rounded"><?= htmlspecialchars($game['description']) ?></textarea>
            <input type="datetime-local" name="start_time" value="<?= htmlspecialchars($game['start_time']) ?>" class="w-full p-2 border rounded">
            <div>
                <input type="text" name="cover_image" id="cover_image" placeholder="Обложка (URL)" value="<?= htmlspecialchars($game['cover_image']) ?>" class="w-full p-2 border rounded mb-2">
                <input type="file" onchange="uploadImage(this, url => document.getElementById('cover_image').value = url)" class="text-sm">
            </div>            <label class="inline-flex items-center">
                <input type="checkbox" name="is_active" <?= $game['is_active'] ? 'checked' : '' ?> class="mr-2"> Активен
            </label>
        </div>

        <h2 class="text-lg font-semibold mb-2">📍 Шаги квеста</h2>
        <div id="checkpoints-container">
            <?php foreach ($checkpoints as $i => $cp): ?>
                <div class="cp-block">
                    <input name="checkpoints[<?= $i ?>][title]" placeholder="Название точки" value="<?= htmlspecialchars($cp['title']) ?>" class="w-full mb-2 p-2 border rounded">
                    <textarea name="checkpoints[<?= $i ?>][question]" placeholder="Вопрос" class="w-full mb-2 p-2 border rounded"><?= htmlspecialchars($cp['question']) ?></textarea>
                    <div>
                        <input name="checkpoints[<?= $i ?>][question_image]" id="question_image_<?= $i ?>" placeholder="Картинка для вопроса (URL)" value="<?= htmlspecialchars($cp['question_image'] ?? '') ?>" class="w-full mb-2 p-2 border rounded text-sm text-blue-700">
                        <input type="file" onchange="uploadImage(this, url => document.getElementById('question_image_<?= $i ?>').value = url)" class="text-sm">
                    </div>

                    <input name="checkpoints[<?= $i ?>][answers]" placeholder="Ответы (через | )" value="<?= htmlspecialchars($cp['answers']) ?>" class="w-full mb-2 p-2 border rounded">
                    <input name="checkpoints[<?= $i ?>][note]" placeholder="Подсказка" value="<?= htmlspecialchars($cp['note'] ?? '') ?>" class="w-full mb-2 p-2 border rounded">
                    <div class="flex gap-2 mb-2">
                        <input name="checkpoints[<?= $i ?>][lat]" placeholder="lat" value="<?= htmlspecialchars($cp['lat']) ?>" class="w-full p-2 border rounded">
                        <input name="checkpoints[<?= $i ?>][lng]" placeholder="lng" value="<?= htmlspecialchars($cp['lng']) ?>" class="w-full p-2 border rounded">
                        <input name="checkpoints[<?= $i ?>][radius]" placeholder="радиус" value="<?= htmlspecialchars($cp['radius']) ?>" class="w-full p-2 border rounded">
                    </div>

                    <div class="flex gap-2 mb-2">
                        <button type="button" onclick="moveUp(this)" class="text-sm text-gray-600 hover:text-black">🔼 Вверх</button>
                        <button type="button" onclick="moveDown(this)" class="text-sm text-gray-600 hover:text-black">🔽 Вниз</button>
                    </div>

                    <h4 class="text-sm font-semibold mb-1">Доп. вопросы:</h4>
                    <div class="extra-questions space-y-3 mb-2">
                        <?php foreach (($cp['additional_questions'] ?? []) as $j => $extra): ?>

                            <div class="bg-gray-100 p-2 rounded">
                                <select name="checkpoints[<?= $i ?>][additional_questions][<?= $j ?>][type]" class="mb-1 p-1 border rounded w-full">
                                    <option value="text" <?= $extra['type'] === 'text' ? 'selected' : '' ?>>Текст</option>
                                    <option value="photo" <?= $extra['type'] === 'photo' ? 'selected' : '' ?>>Фото</option>
                                </select>
                                <input name="checkpoints[<?= $i ?>][additional_questions][<?= $j ?>][question]" placeholder="Вопрос" value="<?= htmlspecialchars($extra['question'] ?? '') ?>" class="w-full mb-1 p-1 border rounded">
                                <input name="checkpoints[<?= $i ?>][additional_questions][<?= $j ?>][text]" placeholder="Текст под заголовком" value="<?= htmlspecialchars($extra['text'] ?? '') ?>" class="w-full mb-1 p-1 border rounded text-sm text-gray-700">

                                <input name="checkpoints[<?= $i ?>][additional_questions][<?= $j ?>][answer]" placeholder="Ответ (или 'auto')" value="<?= htmlspecialchars($extra['answer'] ?? '') ?>" class="w-full mb-1 p-1 border rounded">
                                <input name="checkpoints[<?= $i ?>][additional_questions][<?= $j ?>][hint]" placeholder="Подсказка" value="<?= htmlspecialchars($extra['hint'] ?? '') ?>" class="w-full mb-1 p-1 border rounded text-sm text-yellow-700">
                                <div>
                                    <input name="checkpoints[<?= $i ?>][additional_questions][<?= $j ?>][image]" id="aq_image_<?= $i ?>_<?= $j ?>" placeholder="Картинка (URL)" value="<?= htmlspecialchars($extra['image'] ?? '') ?>" class="w-full mb-1 p-1 border rounded text-sm text-blue-700" />
                                    <input type="file" onchange="uploadImage(this, url => document.getElementById('aq_image_<?= $i ?>_<?= $j ?>').value = url)" class="text-sm mb-1">
                                </div>                                <button type="button" onclick="this.parentElement.remove()" class="text-red-500 text-sm">Удалить</button>
                            </div>
                        <?php endforeach; ?>

                    </div>
                    <button type="button" class="text-sm text-blue-600 add-extra">➕ Добавить доп. вопрос</button>
                    <button type="button" onclick="this.parentElement.remove(); updateCheckpointNames();" class="text-red-600 text-sm mt-2 block">Удалить шаг</button>
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

    function updateCheckpointNames() {
        const blocks = document.querySelectorAll('.cp-block');
        blocks.forEach((block, i) => {
            block.querySelectorAll('[name]').forEach(el => {
                el.name = el.name.replace(/checkpoints\[\d+\]/g, `checkpoints[${i}]`);
            });

            const extras = block.querySelectorAll('.extra-questions > div');
            extras.forEach((div, j) => {
                div.querySelectorAll('[name]').forEach(el => {
                    el.name = el.name.replace(/\[additional_questions\]\[\d+\]/g, `[additional_questions][${j}]`);
                });
            });
        });
    }

    function moveUp(btn) {
        const block = btn.closest('.cp-block');
        const prev = block.previousElementSibling;
        if (prev) {
            block.parentNode.insertBefore(block, prev);
            updateCheckpointNames();
        }
    }

    function moveDown(btn) {
        const block = btn.closest('.cp-block');
        const next = block.nextElementSibling;
        if (next) {
            block.parentNode.insertBefore(next, block.nextSibling);
            updateCheckpointNames();
        }
    }

    function addCheckpoint() {
        const container = document.getElementById('checkpoints-container');
        const block = document.createElement('div');
        block.className = 'cp-block';
        block.innerHTML = `
    <input name="checkpoints[${checkpointIndex}][title]" placeholder="Название точки" class="w-full mb-2 p-2 border rounded">
    <textarea name="checkpoints[${checkpointIndex}][question]" placeholder="Вопрос" class="w-full mb-2 p-2 border rounded"></textarea>
    <input name="checkpoints[${checkpointIndex}][answers]" placeholder="Ответы (через | )" class="w-full mb-2 p-2 border rounded">
    <input name="checkpoints[${checkpointIndex}][note]" placeholder="Подсказка" class="w-full mb-2 p-2 border rounded">
    <input name="checkpoints[${checkpointIndex}][image]" placeholder="Картинка (URL)" class="w-full mb-2 p-2 border rounded text-sm text-blue-700">
    <div class="flex gap-2 mb-2">
        <input name="checkpoints[${checkpointIndex}][lat]" placeholder="lat" class="w-full p-2 border rounded">
        <input name="checkpoints[${checkpointIndex}][lng]" placeholder="lng" class="w-full p-2 border rounded">
        <input name="checkpoints[${checkpointIndex}][radius]" placeholder="радиус" class="w-full p-2 border rounded">
    </div>
    <div class="flex gap-2 mb-2">
        <button type="button" onclick="moveUp(this)" class="text-sm text-gray-600 hover:text-black">🔼 Вверх</button>
        <button type="button" onclick="moveDown(this)" class="text-sm text-gray-600 hover:text-black">🔽 Вниз</button>
    </div>
    <h4 class="text-sm font-semibold mb-1">Доп. вопросы:</h4>
    <div class="extra-questions space-y-3 mb-2"></div>
    <button type="button" class="text-sm text-blue-600 add-extra">➕ Добавить доп. вопрос</button>
    <button type="button" onclick="this.parentElement.remove(); updateCheckpointNames();" class="text-red-600 text-sm mt-2 block">Удалить шаг</button>
`;

        container.appendChild(block);
        checkpointIndex++;
        updateCheckpointNames();
    }

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-extra')) {
            const wrapper = e.target.previousElementSibling;
            const parentIndex = Array.from(document.querySelectorAll('.cp-block')).indexOf(e.target.closest('.cp-block'));
            const index = wrapper.querySelectorAll('div').length;

            const html = `
    <div class="bg-gray-100 p-2 rounded">
        <select name="checkpoints[${parentIndex}][additional_questions][${index}][type]" class="mb-1 p-1 border rounded w-full">
            <option value="text">Текст</option>
            <option value="photo">Фото</option>
        </select>
        <input name="checkpoints[${parentIndex}][additional_questions][${index}][question]" placeholder="Вопрос" class="w-full mb-1 p-1 border rounded">
<input name="checkpoints[${parentIndex}][additional_questions][${index}][question]" placeholder="Вопрос" class="w-full mb-1 p-1 border rounded">
        <input name="checkpoints[${parentIndex}][additional_questions][${index}][answer]" placeholder="Ответ (или 'auto')" class="w-full mb-1 p-1 border rounded">
        <input name="checkpoints[${parentIndex}][additional_questions][${index}][hint]" placeholder="Подсказка" class="w-full mb-1 p-1 border rounded text-sm text-yellow-700">
        <input name="checkpoints[${parentIndex}][additional_questions][${index}][image]" placeholder="Картинка (URL)" class="w-full mb-1 p-1 border rounded text-sm text-blue-700">
        <button type="button" onclick="this.parentElement.remove()" class="text-red-500 text-sm">Удалить</button>
    </div>`;

            wrapper.insertAdjacentHTML('beforeend', html);
        }
    });
</script>
<script>
    function uploadImage(input, callback) {
        const file = input.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('file', file);

        fetch('upload.php', {
            method: 'POST',
            body: formData
        }).then(res => res.json())
            .then(data => {
                if (data.success) {
                    callback(data.url);
                } else {
                    alert('Ошибка загрузки: ' + data.error);
                }
            }).catch(() => alert('Ошибка соединения'));
    }
</script>

</body>
</html>

