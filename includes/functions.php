<?php

function getGame(PDO $db, int $gameId) {
    $stmt = $db->prepare("SELECT * FROM games WHERE id = ?");
    $stmt->execute([$gameId]);
    return $stmt->fetch();
}

function getTeamGame(PDO $db, int $teamId, int $gameId) {
    $stmt = $db->prepare("SELECT * FROM team_games WHERE team_id = ? AND game_id = ?");
    $stmt->execute([$teamId, $gameId]);
    return $stmt->fetch();
}

function getProgress(PDO $db, int $teamId, int $gameId): array {
    $stmt = $db->prepare("SELECT progress FROM team_progress WHERE team_id = ? AND game_id = ?");
    $stmt->execute([$teamId, $gameId]);
    $row = $stmt->fetch();
    return $row ? json_decode($row['progress'], true) : [];
}

function saveProgress(PDO $db, int $teamId, int $gameId, array $progress) {
    $json = json_encode($progress, JSON_UNESCAPED_UNICODE);

    // 🟡 Безопасно получаем location_id (может быть 0 или шаг)
    $locationId = $progress['location_id'] ?? 0;

    // Проверяем, есть ли уже запись
    $stmt = $db->prepare("SELECT id FROM team_progress WHERE team_id = ? AND game_id = ?");
    $stmt->execute([$teamId, $gameId]);

    if ($stmt->fetchColumn()) {
        $stmt = $db->prepare("UPDATE team_progress SET progress = ?, location_id = ? WHERE team_id = ? AND game_id = ?");
        $stmt->execute([$json, $locationId, $teamId, $gameId]);
    } else {
        $stmt = $db->prepare("INSERT INTO team_progress (team_id, game_id, location_id, progress) VALUES (?, ?, ?, ?)");
        $stmt->execute([$teamId, $gameId, $locationId, $json]);
    }
}



function handleAnswer(PDO $db, int $teamId, array $post, array $files = []): array {
    $gameId = (int)($post['game_id'] ?? 0);
    $checkpointIndex = (int)($post['checkpoint_index'] ?? -1);
    $extraIndex = isset($post['extra_index']) ? (int)$post['extra_index'] : null;
    $answer = $post['answer'] ?? null;

    if (!$gameId || $checkpointIndex < 0) {
        return ['success' => false, 'error' => 'Неверные данные'];
    }

    $game = getGame($db, $gameId);
    if (!$game) return ['success' => false, 'error' => 'Квест не найден'];

    $checkpoints = json_decode($game['checkpoints'], true);
    if (!is_array($checkpoints) || !isset($checkpoints[$checkpointIndex])) {
        return ['success' => false, 'error' => 'Шаг не найден'];
    }

    $step = $checkpoints[$checkpointIndex];
    $progress = getProgress($db, $teamId, $gameId);
    $checkpointKey = "checkpoint_$checkpointIndex";
    $now = date('Y-m-d H:i:s');

    if (!isset($progress[$checkpointKey])) $progress[$checkpointKey] = [];

    // 🆕 Добавим location_id, если у шага есть id
    if (isset($step['id'])) {
        $progress['location_id'] = $step['id'];
    }

    // ✅ Основной ответ
    if ($extraIndex === null) {
        $variants = array_map('mb_strtolower', array_map('trim', explode('|', $step['answers'] ?? '')));
        $given = mb_strtolower(trim($answer ?? ''));

        if (!$given) return ['success' => false, 'error' => 'Введите ответ'];
        if (!in_array($given, $variants)) return ['success' => false, 'error' => 'Неверный ответ'];

        $progress[$checkpointKey]['main_answer'] = $answer;
        $progress[$checkpointKey]['answer_time'] = $now;
        $progress['location_id'] = $checkpointIndex;
        saveProgress($db, $teamId, $gameId, $progress);
        return ['success' => true, 'message' => '✅ Ответ принят'];
    }

    // ✅ Доп. вопрос
    $extra = $step['additional_questions'][$extraIndex] ?? null;
    if (!$extra) return ['success' => false, 'error' => 'Доп. вопрос не найден'];

    if (!isset($progress[$checkpointKey]['extra_answers'])) {
        $progress[$checkpointKey]['extra_answers'] = [];
    }

// 📸 Фото + AUTO
    if ($extra['type'] === 'photo' && ($extra['answer'] ?? '') === 'auto') {
        if (!isset($files['answer']) || $files['answer']['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Файл не загружен'];
        }

        $ext = pathinfo($files['answer']['name'], PATHINFO_EXTENSION);
        $base = pathinfo($files['answer']['name'], PATHINFO_FILENAME);
        $uploadDir = __DIR__ . '/../uploads/';
        $filename = $base . '.' . strtolower($ext);
        $i = 1;

        while (file_exists($uploadDir . $filename)) {
            $filename = $base . '-' . $i . '.' . strtolower($ext);
            $i++;
        }

        move_uploaded_file($files['answer']['tmp_name'], $uploadDir . $filename);

        $progress[$checkpointKey]['extra_answers'][$extraIndex] = [
            'answer' => 'Фото загружено',
            'answer_time' => $now,
            'filename' => $filename
        ];

        saveProgress($db, $teamId, $gameId, $progress);
        return ['success' => true, 'message' => '✅ Фото принято'];
    }




    // 📝 Текст
    $expected = trim($extra['answer'] ?? '');
    $given = trim($answer ?? '');
    if (!$given) return ['success' => false, 'error' => 'Введите ответ'];

    $correct = !$expected || mb_strtolower($expected) === mb_strtolower($given);
    if (!$correct) return ['success' => false, 'error' => 'Неверный ответ'];

    $progress[$checkpointKey]['extra_answers'][$extraIndex] = [
        'answer' => $given,
        'answer_time' => $now
    ];
    $progress['location_id'] = $checkpointIndex;
    saveProgress($db, $teamId, $gameId, $progress);
    return ['success' => true, 'message' => '✅ Ответ принят'];
}

function authTeam(PDO $db, string $email, string $password): ?array {
    $stmt = $db->prepare("SELECT * FROM teams WHERE email = ?");
    $stmt->execute([$email]);
    $team = $stmt->fetch();
    return ($team && password_verify($password, $team['password'])) ? $team : null;
}

function registerTeam(PDO $db, string $email, string $password, string $phone): bool {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO teams (email, password, phone) VALUES (?, ?, ?)");
    return $stmt->execute([$email, $hash, $phone]);
}

function joinGame(PDO $db, int $teamId, int $gameId): bool {
    // Получаем номер участника
    $stmt = $db->prepare("SELECT COUNT(*) FROM team_games WHERE game_id = ?");
    $stmt->execute([$gameId]);
    $number = (int)$stmt->fetchColumn() + 1;

    $stmt = $db->prepare("INSERT INTO team_games (team_id, game_id, team_number, status) VALUES (?, ?, ?, 'active')");
    return $stmt->execute([$teamId, $gameId, $number]);
}

function handleAuth(PDO $db): array {
    $mode = $_POST['mode'] ?? 'login';
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (!$email || !$password) {
        return ['success' => false, 'error' => 'Заполните все поля'];
    }

    if ($mode === 'register') {
        if (!$phone) return ['success' => false, 'error' => 'Введите номер телефона'];
        if (registerTeam($db, $email, $password, $phone)) {
            $team = authTeam($db, $email, $password);
            if ($team) {
                $_SESSION['team_id'] = $team['id'];
                return ['success' => true];
            }
        }
        return ['success' => false, 'error' => 'Регистрация не удалась'];
    }

    // login
    $team = authTeam($db, $email, $password);
    if ($team) {
        $_SESSION['team_id'] = $team['id'];
        return ['success' => true];
    }

    return ['success' => false, 'error' => 'Неверный логин или пароль'];
}


function handleJoin(PDO $db): array {
    $teamId = $_SESSION['team_id'] ?? null;
    $gameId = isset($_POST['game_id']) ? (int)$_POST['game_id'] : 0;

    if (!$teamId || !$gameId) {
        return ['success' => false, 'error' => 'Недостаточно данных'];
    }

    // Проверка на дубликат
    $stmt = $db->prepare("SELECT id FROM team_games WHERE team_id = ? AND game_id = ?");
    $stmt->execute([$teamId, $gameId]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Вы уже участвуете'];
    }

    if (joinGame($db, $teamId, $gameId)) {
        return ['success' => true];
    }

    return ['success' => false, 'error' => 'Ошибка при вступлении'];
}


