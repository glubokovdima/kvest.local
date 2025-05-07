<?php
// upload.php

$targetDir = __DIR__ . '/../uploads/';
$webPath = '/uploads/';

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$response = ['success' => false];

if (!empty($_FILES['file']) && $_FILES['file']['error'] === 0) {
    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($ext, $allowed)) {
        $response['error'] = 'Недопустимый тип файла';
    } else {
        $baseName = pathinfo($file['name'], PATHINFO_FILENAME);
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
        $filename = $safeName . '.' . $ext;
        $i = 1;

        while (file_exists($targetDir . $filename)) {
            $filename = $safeName . '-' . $i++ . '.' . $ext;
        }

        if (move_uploaded_file($file['tmp_name'], $targetDir . $filename)) {
            $response['success'] = true;
            $response['url'] = $webPath . $filename;
        } else {
            $response['error'] = 'Не удалось переместить файл';
        }
    }
} else {
    $response['error'] = 'Файл не загружен';
}

header('Content-Type: application/json');
echo json_encode($response);
