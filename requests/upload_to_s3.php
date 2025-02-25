<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/settings.php';
require '../vendor/autoload.php';  // Путь к автозагрузчику Composer

use Aws\S3\S3Client;
use Aws\S3\MultipartUploader;
use Aws\Exception\MultipartUploadException;

session_start();

header('Content-Type: application/json; charset=utf-8');

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Используйте POST-запрос для загрузки.']);
    exit;
}

// Проверяем наличие файла и пути
if (!isset($_FILES['file']) || empty($_POST['path'])) {
    echo json_encode(['success' => false, 'message' => 'Файл или путь не передан.']);
    exit;
}

$file = $_FILES['file'];
$path = $_POST['path'];

// Проверяем, был ли файл успешно загружен
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Ошибка загрузки файла на сервер.']);
    exit;
}

$s3 = getS3Client(); // Получаем экземпляр клиента S3

try {
    $source = $file['tmp_name']; // Временное местоположение загруженного файла
    $fileName = basename($file['name']); // Имя файла

    // Формируем полный путь на S3
    $fullPath = $path;

    $bucket = $_SESSION['bucket_modes'][$_SESSION['bucket']]; // Получаем имя bucket из сессии

    // Инициализация загрузчика
    $uploader = new MultipartUploader($s3, $source, [
        'bucket' => $bucket,
        'key' => $fullPath,
    ]);

    $result = null;

    // Попытка загрузки с обработкой прерываний
    do {
        try {
            $result = $uploader->upload();
        } catch (MultipartUploadException $e) {
            $uploader = new MultipartUploader($s3, $source, [
                'state' => $e->getState(),
            ]);
        }
    } while (!isset($result));

    // Возвращаем успешный результат
    echo json_encode(['success' => true, 'message' => 'Файл успешно загружен.', 'location' => $result['ObjectURL']]);
} catch (Exception $e) {
    // Обработка ошибок
    echo json_encode(['success' => false, 'message' => 'Ошибка загрузки файла: ' . $e->getMessage()]);
}

