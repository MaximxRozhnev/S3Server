<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/settings.php';
require '../vendor/autoload.php'; // Путь к автозагрузчику Composer

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

header('Content-Type: application/json; charset=utf-8');
session_start();

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Используйте POST-запрос для получения подписанного URL.']);
    exit;
}

// Получаем данные из тела запроса
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['filePath'])) {
    echo json_encode(['success' => false, 'message' => 'Не передан путь к файлу.']);
    exit;
}

$filePath = $data['filePath']; // Путь к файлу на S3

// Получаем S3 клиент
try {
    $s3 = getS3Client();
    $bucket = $_SESSION['bucket_modes'][$_SESSION['bucket']];

    // Генерация подписанного URL для скачивания
    $cmd = $s3->getCommand('GetObject', [
        'Bucket' => $bucket,
        'Key' => $filePath,
    ]);

    // Подписываем URL с истечением времени (например, на 15 минут)
    $request = $s3->createPresignedRequest($cmd, '+15 minutes');
    $signedUrl = (string) $request->getUri();

    echo json_encode(['success' => true, 'signedUrl' => $signedUrl]);
} catch (AwsException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка при получении подписанного URL: ' . $e->getMessage()]);
}
