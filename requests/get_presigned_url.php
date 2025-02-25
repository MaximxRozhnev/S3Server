<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/settings.php';
require '../vendor/autoload.php';  // Путь к автозагрузчику Composer
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

header('Content-Type: application/json');
session_start();

// Получаем данные из запроса
$data = json_decode(file_get_contents('php://input'), true);
$filename = $data['filename'] ?? null;
$patientPath = $data['patientPath'] ?? null;  // Получаем путь пациента

if (!$filename || !$patientPath) {
    echo json_encode(['error' => 'Имя файла или путь пациента не переданы.']);
    exit;
}

// Настройка S3 клиента
$s3Client = getS3Client();

$bucketName = $_SESSION['bucket_modes'][$_SESSION['bucket']]; // Имя бакета
$expiry = 3600; // Время действия ссылки в секундах (1 час)

// Генерация ключа для объекта в S3 (с использованием пути пациента)
$key = $patientPath;  // Формируем ключ, который будет включать путь пациента

// Генерация pre-signed URL для загрузки файла
try {
    $command = $s3Client->getCommand('PutObject', [
        'Bucket' => $bucketName,
        'Key'    => $key,  // Используем сформированный ключ
    ]);

    // Создаем pre-signed URL с истечением срока действия через 1 час
    $request = $s3Client->createPresignedRequest($command, '+' . $expiry . ' seconds');
    
    // Получаем URL
    $preSignedUrl = (string) $request->getUri();

    // Возвращаем URL в формате JSON
    echo json_encode(['url' => $preSignedUrl]);
} catch (AwsException $e) {
    echo json_encode(['error' => 'Ошибка при генерации pre-signed URL: ' . $e->getMessage()]);
}
?>
